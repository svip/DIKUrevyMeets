package pages

import (
	"fmt"
	"net/http"
	"net/url"
	"strings"
)

type HandlePage interface {
	Content() string
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
			html = "500"
			return
		}
		action = values.Get("action")
	}
	var page HandlePage
	switch action {
		case "meeting":
		case "møde":
			page = meetingPage(req)
		default:
			page = frontPage(req)
	}
	
	html = fmt.Sprintf("<html><head><title>%s</title></head><body>%s</body></html>", page.Title(), page.Content())
	return
}

func (p Page) Content() string {
	return p.content
}

func (p Page) Title() string {
	return p.title
}
