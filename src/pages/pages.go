package pages

import (
	"bytes"
	"conf"
	"db"
	"fmt"
	"html/template"
	"log"
	"msg"
	"net/http"
	"net/url"
	"strconv"
	"strings"
	"time"
)

type HandlePage interface {
	Content() template.HTML
	Title() string
	Redirect() string
}

type Page struct {
	content  string
	title    string
	redirect string
}

func newPage() Page {
	return Page{"", "", ""}
}

var ServerConfiguration conf.ServerConfig

func init() {
	ServerConfiguration, _ = conf.LoadConfiguration()
}

type session struct {
	w        http.ResponseWriter
	req      *http.Request
	m        *msg.Container
	language string
}

func newSession(w http.ResponseWriter, req *http.Request) *session {
	language := msg.GetDefaultLanguage()
	/* Should we ever want to support multiple languages...
	for _, cookie := range req.Cookies() {
		if cookie.Name == "language" {
			language = msg.GetLegalLanguage(cookie.Value)
		}
	}*/
	c := msg.NewContainer(language, ServerConfiguration.MessageDirectory)
	return &session{w, req, c, language}
}

func (s *session) msg(msg string, a ...interface{}) string {
	return s.m.Msg(msg, a...)
}

func (s *session) tmsg(msg string, input interface{}) string {
	return s.m.MsgTemplate(msg, input)
}

func (s *session) createFooter() template.HTML {
	return template.HTML("")
}

func (s *session) createHeader() template.HTML {
	return template.HTML("")
}

func templatePath(filename string) string {
	return fmt.Sprintf("%s%s", ServerConfiguration.TemplateDirectory, filename)
}

func (s *session) convertLegacyQueryValues() string {
	page := ""
	values, err := url.ParseQuery(s.req.URL.RawQuery)
	if err != nil {
		log.Println(err)
	} else {
		switch {
		case values.Get("meeting") != "":
			page = "meeting"
		case values.Get("do") != "":
			page = "do"
		case values.Get("admin") != "":
			page = "admin"
		default:
			page = "front"
		}
	}
	return page
}

func (s *session) getPage() string {
	page := strings.Split(s.req.URL.Path, "/")[1]
	if page == "" {
		page = s.convertLegacyQueryValues()
	}
	switch page {
	case "meeting", "møde":
		page = "meeting"
	default:
		page = "front"
	}
	return page
}

func htmlMsg(base string, templ string, input interface{}) string {
	out := bytes.NewBufferString(base)
	t, err := template.New("html").Parse(templ)
	if err != nil {
		log.Fatal("Template ", templ, " could not parse: ", err)
	}
	err = t.Execute(out, input)
	if err != nil {
		log.Print("Template failed to execute: ", err)
		return fmt.Sprintf("Template failed to execute: %s", err.Error())
	}
	return out.String()
}

func (s *session) displayDate(date time.Time) string {
	return date.Format(s.msg("time-format"))
}

func (s *session) writeHourStamp(h db.HourStamp, d db.Date, showDay bool) template.HTML {
	str := strings.Split(string(h), " ")
	if len(str) == 1 {
		return template.HTML(str[0])
	}
	if !showDay {
		return template.HTML(str[1])
	}
	days, _ := strconv.Atoi(str[0])
	dt, err := d.Add(days)
	if err != nil {
		log.Print(err)
		return template.HTML(str[1])
	}
	t, _ := template.New("t").Parse(`<b>{{.Day}}</b><br />
{{.HourStamp}}`)
	out := bytes.NewBuffer([]byte(""))
	t.Execute(out, struct {
		Day, HourStamp string
	}{s.displayDate(dt), str[1]})
	return template.HTML(out.String())
}

