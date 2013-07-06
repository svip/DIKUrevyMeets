package db

import (
	"strings"
	"strconv"
	"html/template"
	"bytes"
	"msg"
)

type hourStamp string
type scheduleItemId int
type systemUserId int

func (h hourStamp) ToInt() int {
	i, err := strconv.Atoi(strings.Replace(strings.Replace(string(h), " ", "", -1), ":", "", -1))
	if err != nil {
		i = 0
	}
	return i
}

func (h hourStamp) HtmlWrite() template.HTML {
	s := strings.Split(string(h), " ")
	if len(s) == 1 {
		return template.HTML(s[0])
	}
	if s[0] == "0" {
		return template.HTML(s[1])
	}
	t, _ := template.New("t").Parse(`<b>{{.LabelDay}} {{.Day}}</b><br />
{{.HourStamp}}`)
	out := bytes.NewBuffer([]byte(""))
	t.Execute(out, struct {
		LabelDay, Day, HourStamp string
	}{msg.Msg("meeting-table-day"), s[0], s[1]})
	return template.HTML(out.String())
}

func (i scheduleItemId) String() string {
	return strconv.Itoa(int(i))
}

func (i systemUserId) String() string {
	return strconv.Itoa(int(i))
}
