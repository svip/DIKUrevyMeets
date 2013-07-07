package db

import (
	"encoding/json"
	"log"
	"fmt"
	"io/ioutil"
	"sort"
	"regexp"
)

type scheduleItemType string
type timeStamp int
type userType string

type Tag string

type ScheduleItem struct {
	Id scheduleItemId
	Title string
	Type scheduleItemType
	Start hourStamp
	End hourStamp
	Unique bool
	IcalUnique bool
	Nojoin bool
	CostPerPerson float32
	Spend float32
	Open bool
	Closedby UserId
}

type UserScheduleItem struct {
	Attending bool
	Eating bool
	Cooking bool
	Foodhelp bool
	Paid float32
}

type UserSchedule struct {
	Id UserId
	Schedule map[string]UserScheduleItem
	Usertype userType
	Comment string
	Modified timeStamp
	Name string
}

type Meeting struct {
	Title string
	Schedule map[string]ScheduleItem
	Comment string
	Users map[string]UserSchedule
	Hidden bool
	Locked bool
	Tags []Tag
	Days int
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

func GetMeeting(date string) (Meeting, error) {
	if !meetingsLoaded {
		loadMeetings()
	}
	meeting, ok := meetings[date]
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

func (s ScheduleSorter) Len() int { return len(s.schedule) }
func (s ScheduleSorter) Swap(i, j int) { s.schedule[i], s.schedule[j] = s.schedule[j], s.schedule[i] }
func (s ScheduleSorter) Less(i, j int) bool {
	return s.schedule[i].Start.ToInt() < s.schedule[j].Start.ToInt()
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
	return sorted
}
