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

type Page struct {
	content  string
	Styles   []string
	Scripts  []string
	Title    string
	Redirect string
}

func newPage() Page {
	return Page{"", []string{}, []string{}, "", ""}
}

func (p *Page) SetRedirect(path ...string) {
	p.Redirect = "/"
	for i := 0; i < len(path); i++ {
		p.Redirect = fmt.Sprintf("%s%s/", p.Redirect, path[i])
	}
}

func (p Page) Content() template.HTML {
	return template.HTML(p.content)
}

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

type menuItem struct {
	Url string
	Msg string
}

func (s *session) topMenu() template.HTML {
	var menu []menuItem
	if s.auth.IsAdmin {
		menu = append(menu, menuItem{"/admin/", "mainmenu-admin"})
	}
	
	menu = append(menu, []menuItem{
		menuItem{"/ical/", "mainmenu-ical"},
		menuItem{"/", "mainmenu-frontpage"},
		menuItem{"http://dikurevy.dk/", "mainmenu-mainsite"},
	}...)
	
	var content string
	t, _ := template.New("menuitem").Parse(`<a href="{{.Url}}" title="{{.Title}}">{{.Title}}</a>`)
	m, _ := template.New("middot").Parse(` &middot; `)
	
	for i, item := range menu {
		out := bytes.NewBufferString(content)
		if i > 0 {
			m.Execute(out, nil)
		}
		t.Execute(out, struct{
			Url string
			Title string
		}{
			item.Url,
			s.msg(item.Msg),
		})
		content = out.String()
	}
	return template.HTML(content)
}

func PageEntry(w http.ResponseWriter, req *http.Request) {
	s := newSession(w, req)
	var page Page
	switch s.getPage() {
	case "meeting":
		page = meetingPage(req, s)
	default:
		page = frontPage(req, s)
	}

	// If redirect is set, we should redirect there.
	if page.Redirect != "" {
		http.Redirect(w, req, page.Redirect, http.StatusFound)
		return
	}

	mainhtml, err := template.ParseFiles(fmt.Sprintf("%s%s", ServerConfiguration.TemplateDirectory, "template.html"))
	if err != nil {
		log.Fatal(err)
	}
	page.Styles = append(page.Styles, "/media/styles.css")
	out := bytes.NewBuffer([]byte(``))
	mainhtml.Execute(out, map[string]interface{}{
		"Title":   page.Title,
		"Style":   page.Styles,
		"Script":  page.Scripts,
		"Topmenu": s.topMenu(),
		"Content": page.Content(),
	})
	html := out.String()
	io.WriteString(w, html)
}
