package pages

import (
	"net/http"
	"net/url"
	"strings"
	"html/template"
	"bytes"
	"log"
	"io"
)

type HandlePage interface {
	Content() template.HTML
	Title() string
	Redirect() string
}

type Page struct {
	content string
	title string
	redirect string
}

func newPage () Page {
	return Page{"","",""}
}

// Gets the path segment (e.g. /1/2/3) or - if that fails - it tries to
// get the specific query value defined (or none if not).
// Blank should be treated as not set rather than an error.
func getPathSegment(req *http.Request, segment int, queryValue string) string {
	path := strings.Split(req.URL.Path, "/")
	action := ""
	if len(path) >= segment + 1 {
		action = path[segment]
	}
	if action == "" && queryValue != "" {
		values, err := url.ParseQuery(req.URL.RawQuery)
		if err != nil {
			log.Println(err)
		} else {
			action = values.Get(queryValue)
		}
	}
	return action
}

func convertLegacyQueryValues(req *http.Request) string {
	action := ""
	values, err := url.ParseQuery(req.URL.RawQuery)
	if err != nil {
		log.Println(err)
	} else {
		switch {
			case values.Get("meeting") != "":
				action = "meeting"
			case values.Get("do") != "":
				action = "do"
			case values.Get("admin") != "":
				action = "admin"
			default:
				action = "front"
		}
	}
	return action
}

func HandleAction (w http.ResponseWriter, req *http.Request) {
	path := strings.Split(req.URL.Path, "/")
	action := ""
	if len(path) >= 2 {
		action = path[1]
	}
	if action == "" {
		action = convertLegacyQueryValues(req)
	}
	var page HandlePage
	switch action {
		case "meeting":
		case "møde":
			page = meetingPage(req)
		default:
			page = frontPage(req)
	}
	
	// If redirect is set, we should redirect there.
	if page.Redirect() != "" {
		http.Redirect(w, req, page.Redirect(), http.StatusFound)
		return
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
	html := out.String()
	io.WriteString(w, html)
}

func (p Page) Content() template.HTML {
	return template.HTML(p.content)
}

func (p Page) Title() string {
	return p.title
}

func (p Page) Redirect() string {
	return p.redirect
}
