package pages

import (
	"bytes"
	"db"
	"fmt"
	"html/template"
	"log"
	"net/http"
)

type FrontPage struct {
	Page Page
	s    *session
}

func frontPage(req *http.Request, s *session) HandlePage {
	page := &FrontPage{newPage(), s}
	page.Render()
	return page.Page
}

func (p *FrontPage) Render() {
	meetings := db.GetAvailableMeetings()
	p.Page.title = p.s.msg("front-title")

	var content string

	meetingRow, err := template.New("meetingRow").Parse(`	<tr>
		<td>{{.Dayname}}</td><td>{{.WrittenDate}}</td><td><a href="/møde/{{.Date}}">{{.MeetingName}}</a></td><td>{{.HourStamp}}</td>
	</tr>
`)
	if err != nil {
		log.Fatal(err)
	}

	dates := meetings.GetSortedDates()

	for _, date := range dates {
		meeting, _ := meetings.GetMeeting(date)
		dayname := p.s.msg(fmt.Sprintf("time-dayweek-%d", date.DayOfTheWeek()))
		datet := date.Time()
		writtendate := datet.Format(p.s.msg("time-format"))
		if meeting.Days > 1 {
			enddate := meeting.GetEndDate()
			enddayname := p.s.msg(fmt.Sprintf("time-dayweek-%d", enddate.DayOfTheWeek()))
			dayname = p.s.msg("time-tofromday", dayname, enddayname)
			enddatet := enddate.Time()
			writtendate = p.s.msg("time-tofromday", writtendate, enddatet.Format(p.s.msg("time-format")))
		}
		out := bytes.NewBufferString(content)
		meetingRow.Execute(out, map[string]interface{}{
			"Dayname":     template.HTML(dayname),
			"WrittenDate": template.HTML(writtendate),
			"Date":        date,
			"MeetingName": meeting.Title,
			"HourStamp":   p.s.writeHourStamp(meeting.StartTime()),
		})
		content = out.String()
	}

	meetingTable, err := template.New("meetingTable").Parse(`<h1>{{.Title}}</h1>
<table>
{{.Table}}</table>
{{if .UserNote}}<p>{{.UserNote}}</p>{{end}}`)
	if err != nil {
		log.Fatal(err)
	}

	out := bytes.NewBuffer([]byte(``))
	meetingTable.Execute(out, map[string]interface{}{
		"Title":    p.s.msg("frontpage-h1"),
		"Table":    template.HTML(content),
		"UserNote": "",
	})
	content = out.String()

	p.Page.content = content
}

