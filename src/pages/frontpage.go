package pages

import (
	"net/http"
	"msg"
	"db"
)

type FrontPage struct {
	Page Page
}

func frontPage (req *http.Request) HandlePage {
	page := &FrontPage{newPage()}
	page.Render()
	return page.Page
}

func (p *FrontPage) Render() {
	db.GetMeetings()
	p.Page.title = msg.Msg("front-title")
	p.Page.content = msg.RawMsg("$1, $2, $1, $3, $5, $6, $8, $11",
		"A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K",
		11, "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V")
	//msg.Msg("front-title")
}
