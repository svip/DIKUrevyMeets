package pages

import (
	"net/http"
	"msg"
	"db"
)

type MeetingPage struct {
	Page Page
	req *http.Request
}

func meetingPage (req *http.Request, path []string) HandlePage {
	page := &MeetingPage{newPage(), req}
	page.Render()
	return page.Page
}

func (p *MeetingPage) GetMeeting() (db.Meeting, error) {
	date := getPathSegment(p.req, 2, "meeting")
	return db.GetMeeting(date)
}

func (p *MeetingPage) Render() {
	meeting, err := p.GetMeeting()
	if err != nil {
		p.Page.redirect = "/"
		return
	}
	p.Page.content = "hvad"
	p.Page.title = msg.Msg("meeting-title", meeting.Title)
}
