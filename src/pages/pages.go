package pages

import (
	"net/http"
	"net/url"
	"strings"
	"html/template"
	"bytes"
	"log"
)

type HandlePage interface {
	Content() template.HTML
	Title() string
}

type Page struct {
	content string
	title string
}

func newPage () Page {
	return Page{"",""}
}

func HandleAction (req *http.Request) (html string) {
	path := strings.Split(req.URL.Path, "/")
	action := ""
	if len(path) >= 2 {
		action = path[1]
	}
	if action == "" {
		values, err := url.ParseQuery(req.URL.RawQuery)
		if err != nil {
			log.Println(err)
		} else {
			action = values.Get("action")
		}
	}
	var page HandlePage
	switch action {
		case "meeting":
		case "møde":
			page = meetingPage(req, path)
		default:
			page = frontPage(req, path)
	}
	
	mainhtml, err := template.ParseFiles("./template.html")
	if err != nil {
		log.Fatal(err)
	}
	out := bytes.NewBuffer([]byte(``))
	mainhtml.Execute(out, struct {
		Title string
		Style string
		Script string
		Topmenu string
		Content template.HTML
	}{page.Title(), "", "", "", page.Content()})
	html = out.String()
	return
}

func (p Page) Content() template.HTML {
	return template.HTML(p.content)
}

func (p Page) Title() string {
	return p.title
}
