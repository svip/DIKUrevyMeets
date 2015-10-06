package pages

import (
	"db"
	"fmt"
	"log"
	"strconv"
	"strings"
)

func (p *MeetingPage) commitToMeeting() {
	vals := p.s.req.PostForm
	log.Println(vals)
	meeting := p.GetMeeting()
	comment := vals.Get("meeting-comment")
	userType := vals.Get("meeting-usertype")
	extraId := vals.Get("meeting-extraid")
	if userType == "self" {
		db.CommitUserToSchedule(meeting.Date, p.s.auth.Uid, comment)
	} else {
		if extraId == "" {
			extraId = db.GenerateExtraId(meeting)
		}
		name := vals.Get("meeting-name")
		db.CommitPersonToSchedule(meeting.Date, p.s.auth.Uid, extraId, name, comment)
	}
	for _, item := range meeting.Schedule {
		if item.Type == "eat" {
			eating := vals.Get(fmt.Sprintf("meeting-%d-eating", item.Id)) == "on"
			cooking := vals.Get(fmt.Sprintf("meeting-%d-cooking", item.Id)) == "on"
			foodhelp := vals.Get(fmt.Sprintf("meeting-%d-foodhelp", item.Id)) == "on"
			if userType == "self" {
				db.CommitUserToEatingItem(meeting.Date, p.s.auth.Uid, item.Id.Int(), eating, cooking, foodhelp)
			} else {
				db.CommitPersonToEatingItem(meeting.Date, p.s.auth.Uid, extraId, item.Id.Int(), eating, cooking, foodhelp)
			}
		} else {
			attending := vals.Get(fmt.Sprintf("meeting-%d-attending", item.Id)) == "on"
			if userType == "self" {
				db.CommitUserToMeetingItem(meeting.Date, p.s.auth.Uid, item.Id.Int(), attending)
			} else {
				db.CommitPersonToMeetingItem(meeting.Date, p.s.auth.Uid, extraId, item.Id.Int(), attending)
			}
		}
	}
}

func (p *MeetingPage) closeEating(id int) {
	formid := fmt.Sprintf("closeeating-%d-spend", id)
	val := p.s.req.PostForm.Get(formid)
	val = strings.Replace(val, ",", ".", 1)
	spend, err := strconv.ParseFloat(val, 64)
	if err != nil {
		// Future feature:  Tell users what went wrong
		//p.formMessage(formid, "meeting-closeeating-spent-error-invalid")
		log.Println(err)
		return
	}
	log.Println(spend)
	meeting := p.GetMeeting()
	db.CloseEating(meeting.Date, id, p.s.auth.Uid, spend)
}

func (p *MeetingPage) openEating(id int) {
	meeting := p.GetMeeting()
	db.OpenEating(meeting.Date, id)
}

func (p *MeetingPage) handlePost() {
	p.s.req.ParseForm()
	vals := p.s.req.PostForm
	if vals.Get("meeting-submit") != "" {
		p.commitToMeeting()
		return
	}
	meeting := p.GetMeeting()
	for _, item := range meeting.Schedule {
		if item.Type == "eat" {
			if vals.Get(fmt.Sprintf("closeeating-%d-submit", item.Id)) != "" {
				p.closeEating(item.Id.Int())
				return
			}
			if vals.Get(fmt.Sprintf("openeating-%d-submit", item.Id)) != "" {
				p.openEating(item.Id.Int())
				return
			}
		}
	}
	for id := range meeting.Users {
		sid := strings.Split(id, "-")
		if sid[0] == p.s.auth.Uid.String() && len(sid) > 1 {
			
		}
	}
}

