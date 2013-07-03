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

type meeting struct {
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

type Meetings map[string]meeting

var meetings Meetings
//var meetings map[string]interface{}
var meetingsLoaded bool

func loadMeetings() {
	data, err := ioutil.ReadFile("../data/meetings.json")
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
	fmt.Println(meetings)
}

func GetMeetings() Meetings {
	loadMeetings()
	return Meetings{}
}
