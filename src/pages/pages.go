package pages

import (
	"fmt"
	"net/http"
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

func HandleAction (action string, req *http.Request) (html string) {
	var page HandlePage
	switch action {
		case "meeting":
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
