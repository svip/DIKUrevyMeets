package db

import (
	"encoding/json"
	"log"
	"fmt"
	"io/ioutil"
)

type scheduleItemType string
type hourStamp string
type timeStamp int
type userType string
type itemId string

type scheduleItem struct {
	Id int
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
}

type userScheduleItem struct {
	Attending bool
	Eating bool
	Cooking bool
	Foodhelp bool
	Paid float32
}

type userSchedule struct {
	Schedule map[string]userScheduleItem
	Usertype userType
	Comment string
	Modified timeStamp
	Name string
}

type Tag string
type UserId string

type Meeting struct {
	Title string
	Schedule map[string]scheduleItem
	Comment string
	Users map[string]userSchedule
	Hidden bool
	Locked bool
	Tags []Tag
	Days int
}

type meetingDate string

type Meetings map[string]Meeting

var meetings Meetings
//var meetings map[string]interface{}
var meetingsLoaded bool

var readyToWriteMeetings = make(chan bool)
var readyToWriteUsers = make(chan bool)

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

func WriteUsers() {
	if !<-readyToWriteUsers {
		return
	}
//	data, err := json.Marshal(users)
//	ioutil.WriteFile(usersFile, data, 0777)
//	log.Println("Users file written.")
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
