package db

func CloseEating(date Date, scheduleId int, closer UserId, spent float64) error {
	for mdate, meeting := range meetings {
		if meeting.Date == date {
			for sid, sitem := range meeting.Schedule {
				if sitem.Id.Int() == scheduleId {
					if !sitem.Open {
						return &DbError{"Already closed."}
					}
					sitem.Spend = spent
					sitem.Open = false
					sitem.Closedby = closer
					meeting.Schedule[sid] = sitem
					meetings[mdate] = meeting
					return nil
				}
			}
			return &DbError{"No such schedule id."}
		}
	}
	return &DbError{"No such meeting."}
}

func OpenEating(date Date, scheduleId int) error {
	for mdate, meeting := range meetings {
		if meeting.Date == date {
			for sid, sitem := range meeting.Schedule {
				if sitem.Id.Int() == scheduleId {
					if sitem.Open {
						return &DbError{"Already open."}
					}
					sitem.Spend = 0.0
					sitem.Open = true
					sitem.Closedby = 0
					meeting.Schedule[sid] = sitem
					meetings[mdate] = meeting
					return nil
				}
			}
			return &DbError{"No such schedule id."}
		}
	}
	return &DbError{"No such meeting."}
}
