package db

import (
	"encoding/json"
	"fmt"
	"io/ioutil"
	"log"
	"regexp"
	"sort"
	"time"
)

type scheduleItemType string
type timeStamp int64
type userType string

type Tag string

type ScheduleItem struct {
	Id            scheduleItemId
	Title         string
	Type          scheduleItemType
	Start         HourStamp
	End           HourStamp
	Unique        bool
	IcalUnique    bool
	Nojoin        bool
	CostPerPerson float64
	Spend         float64
	Open          bool
	Closedby      UserId
}

type UserScheduleItem struct {
	Attending bool
	Eating    bool
	Cooking   bool
	Foodhelp  bool
	Paid      float64
}

type UserSchedule struct {
	Id       UserId
	Schedule map[string]UserScheduleItem
	Usertype userType
	Comment  string
	Modified timeStamp
	Name     string
}

type Date string

func (d Date) String() string {
	return string(d)
}

func (d Date) Time() (time.Time, error) {
	t, err := time.Parse("2006-01-02", d.String())
	if err != nil {
		return t, err
	}
	return t, nil
}

func (d Date) Add(days int) (time.Time, error) {
	t, err := d.Time()
	if err != nil {
		return t, err
	}
	return t.AddDate(0, 0, days), nil
}

func (d Date) DayOfTheWeek() (int, error) {
	t, err := d.Time()
	if err != nil {
		return 0, err
	}
	wd := t.Weekday()
	if wd == time.Sunday {
		wd = 7
	}
	return int(wd), nil
}

type Meeting struct {
	Date     Date
	Title    string
	Schedule map[string]ScheduleItem
	Comment  string
	Users    map[string]UserSchedule
	Hidden   bool
	Locked   bool
	Tags     []Tag
	Days     int
}

type Meetings map[string]Meeting

var meetings Meetings

var meetingsLoaded bool

var readyToWriteMeetings = make(chan bool)

const meetingsFile = "../data/meetings.json"

type DbError struct {
	error string
}

func (e *DbError) Error() string {
	return e.error
}

func loadMeetings() {
	data, err := ioutil.ReadFile(meetingsFile)
	if err != nil {
		log.Fatal(err)
	}
	err = json.Unmarshal(data, &meetings)
	if err != nil {
		// Not matching our strict data types?  Might be some legacy data
		// lying around.
		err = parseLegacyData(data)
		if err != nil {
			log.Fatal(err)
		}
	}
	for date, meeting := range meetings {
		meeting.Date = Date(date)
		meetings[date] = meeting
	}
	fmt.Println("Meetings loaded.")
	meetingsLoaded = true
}

func WriteMeetings() {
	if !<-readyToWriteMeetings {
		return
	}
	data, err := json.Marshal(meetings)
	if err != nil {
		return
	}
	ioutil.WriteFile(meetingsFile, data, 0777)
	log.Println("Meetings file written.")
}

func GetAvailableMeetings() Meetings {
	if !meetingsLoaded {
		loadMeetings()
	}
	m := Meetings{}
	for date := range meetings {
		if !meetings[date].Hidden && !meetings[date].Locked {
			m[date] = meetings[date]
		}
	}
	return m
}

func GetMeetings() Meetings {
	if !meetingsLoaded {
		loadMeetings()
	}
	return Meetings{}
}

func GetMeeting(date Date) (Meeting, error) {
	if !meetingsLoaded {
		loadMeetings()
	}
	meeting, ok := meetings[date.String()]
	if !ok {
		return Meeting{}, &DbError{"No such meeting"}
	}
	return meeting, nil
}

func (us UserSchedule) CleanComment() string {
	// Clean out all tags.  Pun detection was fun but useless.
	r, _ := regexp.Compile("<[^>]+>")
	return r.ReplaceAllString(us.Comment, "")
}

type ScheduleSorter struct {
	schedule []ScheduleItem
}

func (s ScheduleSorter) Len() int      { return len(s.schedule) }
func (s ScheduleSorter) Swap(i, j int) { s.schedule[i], s.schedule[j] = s.schedule[j], s.schedule[i] }
func (s ScheduleSorter) Less(i, j int) bool {
	return s.schedule[i].Start.Int()+s.schedule[i].Id.Int() < s.schedule[j].Start.Int()+s.schedule[j].Id.Int()
}

func SortSchedule(schedule map[string]ScheduleItem) (sorted []ScheduleItem) {
	for itemId := range schedule {
		sorted = append(sorted, schedule[itemId])
		//if sorted[len(sorted)-1].Id == "" {
		//	sorted[len(sorted)-1].Id = itemId
		//}
	}
	ss := &ScheduleSorter{sorted}
	sort.Sort(ss)
	return 
}

type DateSorter struct {
	dates []Date
}

func (s DateSorter) Len() int      { return len(s.dates) }
func (s DateSorter) Swap(i, j int) { s.dates[i], s.dates[j] = s.dates[j], s.dates[i] }
func (s DateSorter) Less(i, j int) bool {
	ti, err := time.Parse("2006-01-02", s.dates[i].String())
	if err != nil {
		log.Fatal("Bad time: ", err)
	}
	tj, err := time.Parse("2006-01-02", s.dates[j].String())
	if err != nil {
		log.Fatal("Bad time: ", err)
	}
	return ti.Before(tj)
}

func (ms Meetings) GetSortedDates() (sorted []Date) {
	for date := range ms {
		sorted = append(sorted, Date(date))
	}
	ds := &DateSorter{sorted}
	sort.Sort(ds)
	return 
}

func (ms Meetings) GetMeeting(date Date) (Meeting, error) {
	meeting, ok := ms[date.String()]
	if !ok {
		return Meeting{}, &DbError{"No such meeting"}
	}
	return meeting, nil
}

// This function returns true if there are no events in its schedule that
// have the nojoin set to false.  Otherwise false.
func (m *Meeting) Nojoin() bool {
	tojoin := false
	for _, item := range m.Schedule {
		if !item.Nojoin {
			tojoin = true
			break
		}
	}
	return !tojoin
}

func (m *Meeting) StartTime() HourStamp {
	schedule := SortSchedule(m.Schedule)
	return schedule[0].Start
}

func (m *Meeting) GetEndDate() (Date, error) {
	nt, err := m.Date.Add(m.Days)
	if err != nil {
		return m.Date, err
	}
	return Date(nt.Format("2006-01-02")), nil
}
