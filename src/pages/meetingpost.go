package pages

import (
	"db"
	"fmt"
	"log"
	"strconv"
	"strings"
)

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

func (p *MeetingPage) handlePost() {
	p.s.req.ParseForm()
	vals := p.s.req.PostForm
	if vals.Get("meeting-submit") != "" {
		log.Println(vals)
		return
	}
	meeting := p.GetMeeting()
	for _, item := range meeting.Schedule {
		if item.Type == "eat" {
			if vals.Get(fmt.Sprintf("closeeating-%d-submit", item.Id)) != "" {
				p.closeEating(item.Id.Int())
				return
			}
		}
	}
}

