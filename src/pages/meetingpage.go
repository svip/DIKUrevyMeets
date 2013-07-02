package pages

import (
	"net/http"
	"msg"
)

type MeetingPage struct {
	Page Page
}

func meetingPage (req *http.Request) HandlePage {
	page := &MeetingPage{newPage()}
	page.Render()
	return page.Page
}

func (p *MeetingPage) Render() {
	p.Page.content = "hvad"
	p.Page.title = msg.Msg("meeting-title", "Eksempel")
}
