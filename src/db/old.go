package db

import (
	"encoding/json"
	"strconv"
	"log"
	"fmt"
)

// This file handles the old legacy JSON data format.
// Once this code is no longer in use (that is when the JSON data is
// sane (i.e. matches the strict data types) and when it is updated, it
// remains sane), this file should be deleted.

type oldScheduleItem struct {
	Id interface{}
	Title string
	Type scheduleItemType
	Start hourStamp
	End hourStamp
	Unique bool
	IcalUnique bool
	Nojoin bool
	CostPerPerson float32
	Spend interface{}
	Open bool
}

type oldUserScheduleItem struct {
	Attending bool
	Eating interface{}
	Cooking interface{}
	Foodhelp interface{}
	Paid float32
}

type oldUserSchedule struct {
	Schedule map[string]oldUserScheduleItem
	Usertype userType
	Comment string
	Modified timeStamp
	Name string
}

type oldMeeting struct {
	Title string
	Schedule map[string]oldScheduleItem
	Comment string
	Users map[string]oldUserSchedule
	Hidden bool
	Locked bool
	Tags map[string]string
	Days interface{}
}

// This is a hack, don't bother understanding it
func parseLegacyData(data []byte) error {
	var f map[string]oldMeeting
	r := make(Meetings)
	err := json.Unmarshal(data, &f)
	if err != nil {
		log.Println("Attempt failed")
		return err
	}
	for date := range f {
		tags := f[date].Tags
		var newtags []Tag
		for tag := range tags {
			newtags = append(newtags, Tag(tag))
		}
		var newdays int
		switch f[date].Days.(type) {
			case int:
				newdays = f[date].Days.(int)
			default:
				newdays = 0
		}
		newschedule := make(map[string]scheduleItem)
		for itemId := range f[date].Schedule {
			item := f[date].Schedule[itemId]
			var newid int
			switch item.Id.(type) {
				case string:
					newid, _ = strconv.Atoi(item.Id.(string))
				case int:
					newid = item.Id.(int)
			}
			var newspend float32
			switch item.Spend.(type) {
				case string:
					tmp, _ := strconv.Atoi(item.Spend.(string))
					newspend = float32(tmp)
				case int:
					newspend = float32(item.Spend.(int))
			}
			strid := fmt.Sprintf("%d", newid)
			newschedule[strid] = scheduleItem{newid, item.Title, item.Type, item.Start, item.End, item.Unique, item.IcalUnique, item.Nojoin, item.CostPerPerson, newspend, item.Open}
		}
		newusers := make(map[string]userSchedule)
		for userId := range f[date].Users {
			newuserschedule := make(map[string]userScheduleItem)
			for userItemId := range f[date].Users[userId].Schedule {
				item := f[date].Users[userId].Schedule[userItemId]
				neweating := false
				newcooking := false
				newfoodhelp := false
				switch item.Eating.(type) {
					case bool:
						if item.Eating.(bool) {
							neweating = true
						}
				}
				switch item.Cooking.(type) {
					case bool:
						if item.Cooking.(bool) {
							newcooking = true
						}
				}
				switch item.Foodhelp.(type) {
					case bool:
						if item.Foodhelp.(bool) {
							newfoodhelp = true
						}
				}
				newuserschedule[userItemId] = userScheduleItem{item.Attending, neweating, newcooking, newfoodhelp, item.Paid}
			}
			newusers[userId] = userSchedule{newuserschedule, f[date].Users[userId].Usertype, f[date].Users[userId].Comment, f[date].Users[userId].Modified, f[date].Users[userId].Name}
		}
		r[date] = Meeting{f[date].Title, newschedule, f[date].Comment, newusers, f[date].Hidden, f[date].Locked, newtags, newdays}
	}
	meetings = r
	return nil
}
