package db

import (
	"fmt"
	"time"
)

func getMeetingAndScheduleItem(date Date, scheduleId int) (string, Meeting, string, ScheduleItem, error) {
	for mdate, meeting := range meetings {
		if meeting.Date == date {
			for sid, sitem := range meeting.Schedule {
				if sitem.Id.Int() == scheduleId {
					return mdate, meeting, sid, sitem, nil
				}
			}
			return "", Meeting{}, "", ScheduleItem{}, &DbError{"No such schedule id."}
		}
	}
	return "", Meeting{}, "", ScheduleItem{}, &DbError{"No such meeting."}
}

func CloseEating(date Date, scheduleId int, closer UserId, spent float64) error {
	mdate, meeting, sid, item, err := getMeetingAndScheduleItem(date, scheduleId)
	if err != nil {
		return err
	}
	if !item.Open {
		return &DbError{"Already closed."}
	}
	item.Spend = spent
	item.Open = false
	item.Closedby = closer
	meeting.Schedule[sid] = item
	meetings[mdate] = meeting
	return nil
}

func OpenEating(date Date, scheduleId int) error {
	mdate, meeting, sid, item, err := getMeetingAndScheduleItem(date, scheduleId)
	if err != nil {
		return err
	}
	if item.Open {
		return &DbError{"Already open."}
	}
	item.Spend = 0.0
	item.Open = true
	item.Closedby = 0
	meeting.Schedule[sid] = item
	meetings[mdate] = meeting
	return nil
}

func newUserSchedule(userId UserId, utype userType, comment string) UserSchedule {
	return UserSchedule{
		userId,
		map[string]UserScheduleItem{},
		utype,
		comment,
		timeStamp(time.Now().Unix()),
		userId.GetUser().Name,
	}
}

func CommitPersonToSchedule(date Date, userId UserId, extraId string, comment string) error {
	id := userId.String()
	if extraId != "" {
		id = fmt.Sprintf("%s-%s", id, extraId)
	}
	for mdate, meeting := range meetings {
		if meeting.Date == date {
			schedule, ok := meeting.Users[id]
			if !ok {
				schedule = newUserSchedule(userId, "self", comment)
			} else {
				schedule.Comment = comment
			}
			meeting.Users[id] = schedule
			meetings[mdate] = meeting
			return nil
		}
	}
	return &DbError{"No such meeting."}
}

func CommitUserToSchedule(date Date, userId UserId, comment string) error {
	return CommitPersonToSchedule(date, userId, "", comment)
}

func CommitPersonToEatingItem(date Date, userId UserId, extraId string, scheduleId int, eating bool, cooking bool, foodhelp bool) error {
	mdate, meeting, sid, _, err := getMeetingAndScheduleItem(date, scheduleId)
	if err != nil {
		return err
	}
	id := userId.String()
	if extraId != "" {
		id = fmt.Sprintf("%s-%s", id, extraId)
	}
	schedule, ok := meeting.Users[id]
	if !ok {
		return &DbError{"Call CommitPersonToSchedule() first."}
	} else {
		item := schedule.Schedule[sid]
		item.Eating = eating
		item.Cooking = cooking
		item.Foodhelp = foodhelp
		schedule.Schedule[sid] = item
	}
	meeting.Users[id] = schedule
	meetings[mdate] = meeting
	return nil
}

func CommitUserToEatingItem(date Date, userId UserId, scheduleId int, eating bool, cooking bool, foodhelp bool) error {
	return CommitPersonToEatingItem(date, userId, "", scheduleId, eating, cooking, foodhelp)
}

func CommitPersonToMeetingItem(date Date, userId UserId, extraId string, scheduleId int, attending bool) error {
	mdate, meeting, sid, _, err := getMeetingAndScheduleItem(date, scheduleId)
	if err != nil {
		return err
	}
	id := userId.String()
	if extraId != "" {
		id = fmt.Sprintf("%s-%s", id, extraId)
	}
	schedule, ok := meeting.Users[id]
	if !ok {
		return &DbError{"Call CommitPersonToSchedule() first."}
	} else {
		item := schedule.Schedule[sid]
		item.Attending = attending
		schedule.Schedule[sid] = item
	}
	meeting.Users[id] = schedule
	meetings[mdate] = meeting
	return nil
}

func CommitUserToMeetingItem(date Date, userId UserId, scheduleId int, attending bool) error {
	return CommitPersonToMeetingItem(date, userId, "", scheduleId, attending)
}

