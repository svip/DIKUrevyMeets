package pages

import (
	"bytes"
	"db"
	"html/template"
	"log"
	"msg"
	"net/http"
)

type FrontPage struct {
	Page Page
}

func frontPage(req *http.Request) HandlePage {
	page := &FrontPage{newPage()}
	page.Render()
	return page.Page
}

func (p *FrontPage) Render() {
	meetings := db.GetAvailableMeetings()
	p.Page.title = msg.Msg("front-title")

	var content string

	meetingRow, err := template.New("meetingRow").Parse(`	<tr>
		<td>{{.Dayname}}</td><td><a href="/møde/{{.Date}}">{{.Writtendate}}</a></td>
	</tr>
`)
	if err != nil {
		log.Fatal(err)
	}

	for date := range meetings {
		out := bytes.NewBufferString(content)
		meetingRow.Execute(out, map[string]interface{}{
			"Dayname":     "x",
			"Date":        date,
			"Writtendate": date,
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
		"Title":    msg.Msg("frontpage-h1"),
		"Table":    template.HTML(content),
		"UserNote": "",
	})
	content = out.String()

	p.Page.content = content
}
