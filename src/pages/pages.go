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

func GetPathSegment(req *http.Request, segment int) string {
	splits := strings.Split(req.URL.Path, "/")
	if len(splits) - 1 < segment {
		return ""
	}
	return splits[segment]
}

func HandleAction (req *http.Request) (html string) {
	action := GetPathSegment(req, 1)
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
			page = meetingPage(req)
		default:
			page = frontPage(req)
	}
	
	mainhtml, err := template.ParseFiles("./template.html")
	if err != nil {
		log.Fatal(err)
	}
	data := struct {
		Title string
		Style string
		Script string
		Topmenu string
		Content template.HTML
	}{page.Title(), "", "", "", page.Content()}
	out := bytes.NewBuffer([]byte(``))
	mainhtml.Execute(out, data)
	html = out.String()
	return
}

func (p Page) Content() template.HTML {
	return template.HTML(p.content)
}

func (p Page) Title() string {
	return p.title
}
