package db

import "time"

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

func CommitUserToSchedule(date Date, userId UserId, comment string) error {
	for mdate, meeting := range meetings {
		if meeting.Date == date {
			schedule, ok := meeting.Users[userId]
			if !ok {
				schedule = newUserSchedule(userId, "self", comment)
			} else {
				schedule.Comment = comment
			}
			meeting.Users[userId] = schedule
			meetings[mdate] = meeting
			return nil
		}
	}
	return &DbError{"No such meeting."}
}

func CommitUserToEatingItem(date Date, userId UserId, scheduleId int, eating bool, cooking bool, foodhelp bool) error {
	mdate, meeting, sid, _, err := getMeetingAndScheduleItem(date, scheduleId)
	if err != nil {
		return err
	}
	schedule, ok := meeting.Users[userId]
	if !ok {
		return &DbError{"Call CommitUserToSchedule() first."}
	} else {
		item := schedule.Schedule[sid]
		item.Eating = eating
		item.Cooking = cooking
		item.Foodhelp = foodhelp
		schedule.Schedule[sid] = item
	}
	meeting.Users[userId] = schedule
	meetings[mdate] = meeting
	return nil
}

func CommitUserToMeetingItem(date Date, userId UserId, scheduleId int, attending bool) error {
	mdate, meeting, sid, _, err := getMeetingAndScheduleItem(date, scheduleId)
	if err != nil {
		return err
	}
	schedule, ok := meeting.Users[userId]
	if !ok {
		return &DbError{"Call CommitUserToSchedule() first."}
	} else {
		item := schedule.Schedule[sid]
		item.Attending = attending
		schedule.Schedule[sid] = item
	}
	meeting.Users[userId] = schedule
	meetings[mdate] = meeting
	return nil
}

