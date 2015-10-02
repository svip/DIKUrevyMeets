package pages

import (
	"bytes"
	"fmt"
	"html/template"
	"io"
	"log"
	"net/http"
	"net/url"
	"strings"
)

// Gets the path segment (e.g. /1/2/3) or - if that fails - it tries to
// get the specific query value defined (or none if not).
// Blank should be treated as not set rather than an error.
func getPathSegment(req *http.Request, segment int, queryValue string) string {
	path := strings.Split(req.URL.Path, "/")
	action := ""
	if len(path) >= segment+1 {
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

func PageEntry(w http.ResponseWriter, req *http.Request) {
	s := newSession(w, req)
	var page HandlePage
	switch s.getPage() {
	case "meeting":
		page = meetingPage(req, s)
	default:
		page = frontPage(req, s)
	}

	// If redirect is set, we should redirect there.
	if page.Redirect() != "" {
		http.Redirect(w, req, page.Redirect(), http.StatusFound)
		return
	}

	mainhtml, err := template.ParseFiles(fmt.Sprintf("%s%s", ServerConfiguration.TemplateDirectory, "template.html"))
	if err != nil {
		log.Fatal(err)
	}
	out := bytes.NewBuffer([]byte(``))
	mainhtml.Execute(out, map[string]interface{}{
		"Title":   page.Title(),
		"Style":   "",
		"Script":  "",
		"Topmenu": "",
		"Content": page.Content(),
	})
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

