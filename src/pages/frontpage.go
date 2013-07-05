package pages

import (
	"net/http"
	"msg"
	"db"
	"log"
	"html/template"
	"bytes"
	"fmt"
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
	meetings := db.GetAvailableMeetings()
	p.Page.title = msg.Msg("front-title")
	
	var content string
	
	meetingRow, err := template.New("meetingRow").Parse(`<tr>
	<td>{{.Dayname}}</td><td><a href="/møde/{{.Date}}">{{.Writtendate}}</a></td>
</tr>
`)
	if err != nil {
		log.Fatal(err)
	}
	for date := range meetings {
		meetingData := struct {
			Dayname,Date,Writtendate string
		}{"x", date, date}
		out := bytes.NewBufferString(content)
		meetingRow.Execute(out, meetingData)
		out.WriteString(content)
	}
	
	p.Page.content = content
	//msg.Msg("front-title")
}
