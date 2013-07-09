package pages

import (
	"net/http"
	"msg"
	"db"
	"html/template"
	"bytes"
	"auth"
)

type MeetingPage struct {
	Page Page
	req *http.Request
	auth *auth.UserAuth
	date string
}

func meetingPage (req *http.Request) HandlePage {
	page := &MeetingPage{Page: newPage(), req: req, auth: auth.GetAuth(req)}
	page.Render()
	return page.Page
}

func (p *MeetingPage) GetMeeting() (db.Meeting, error) {
	date := getPathSegment(p.req, 2, "meeting")
	p.date = date
	return db.GetMeeting(date)
}

func (p *MeetingPage) returnYesNo(value bool) string {
	if value {
		return "yes"
	}
	return "no"
}

func (p *MeetingPage) returnTick(value bool) string {
	if value {
		return msg.Msg("tick-yes")
	}
	return msg.Msg("tick-no")
}

func (p *MeetingPage) createUserSchedule(userSchedule db.UserSchedule, schedule map[string]db.ScheduleItem) template.HTML {
	diningCells, _ := template.New("diningCells").Parse(`<td class="centre {{.EatingYesNo}}">{{.Eating}}</td><td class="centre {{.CookingYesNo}}">{{.Cooking}}</td><td class="centre {{.FoodhelpYesNo}}">{{.Foodhelp}}</td>`)
	meetingCell, _ := template.New("meetingCell").Parse(`<td class="centre {{.AttendingYesNo}}">{{.Attending}}</td>`)
	content := ""
	for _, sItem := range db.SortSchedule(schedule) {
		itemId := sItem.Id.String()
		item := userSchedule.Schedule[itemId]
		switch sItem.Type {
			case "meet":
				out := bytes.NewBufferString(content)
				meetingCell.Execute(out, map[string]interface{}{
					"AttendingYesNo": p.returnYesNo(item.Attending),
					"Attending":      p.returnTick(item.Attending),
				})
				content = out.String()
			case "eat":
				out := bytes.NewBufferString(content)
				diningCells.Execute(out, map[string]interface{}{
					"EatingYesNo":   p.returnYesNo(item.Eating),
					"Eating":        p.returnTick(item.Eating),
					"CookingYesNo":  p.returnYesNo(item.Cooking),
					"Cooking":       p.returnTick(item.Cooking),
					"FoodhelpYesNo": p.returnYesNo(item.Foodhelp),
					"Foodhelp":      p.returnTick(item.Foodhelp),
				})
				content = out.String()
		}
	}
	return template.HTML(content)
}

func (p *MeetingPage) makeTableScheduleTop(schedule []db.ScheduleItem) template.HTML {
	diningCell, _ := template.New("diningCell").Parse(`<th colspan="3">{{.Title}}{{if .Closed}} ({{.LabelClosed}}){{end}}</th>`)
	meetingCell, _ := template.New("meetingCell").Parse(`<th>{{.Title}}</th>`)
	content := ""
	for _, item := range schedule {
		out := bytes.NewBufferString(content)
		switch item.Type {
			case "eat":
				diningCell.Execute(out, map[string]interface{}{
					"Title":       item.Title,
					"LabelClosed": msg.Msg("meeting-table-foodclosed"),
					"Closed":      !item.Open,
				})
			case "meet":
				meetingCell.Execute(out, map[string]interface{}{
					"Title": item.Title,
				})
		}
		content = out.String()
	}
	return template.HTML(content)
}

func (p *MeetingPage) makeTableScheduleMiddle(schedule []db.ScheduleItem) template.HTML {
	diningCell, _ := template.New("diningCell").Parse(`<th colspan="3">{{.Time}}</th>`)
	meetingCell, _ := template.New("meetingCell").Parse(`<th>{{.Time}}</th>`)
	content := ""
	for _, item := range schedule {
		out := bytes.NewBufferString(content)
		switch item.Type {
			case "eat":
				diningCell.Execute(out, map[string]interface{}{
					"Time":item.Start.HtmlWrite(),
				})
			case "meet":
				meetingCell.Execute(out, map[string]interface{}{
					"Time": item.Start.HtmlWrite(),
				})
		}
		content = out.String()
	}
	return template.HTML(content)
}

func (p *MeetingPage) makeTableScheduleBottom(schedule []db.ScheduleItem) template.HTML {
	diningCells, _ := template.New("diningCells").Parse(`<th>{{.LabelEating}}</th><th>{{.LabelCooking}}</th><th>{{.LabelFoodhelp}}</th>`)
	meetingCell, _ := template.New("meetingCell").Parse(`<th>{{.LabelAttending}}</th>`)
	content := ""
	for _, item := range schedule {
		out := bytes.NewBufferString(content)
		switch item.Type {
			case "eat":
				diningCells.Execute(out, map[string]interface{}{
					"LabelEating":   msg.Msg("meeting-table-eating"),
					"LabelCooking":  msg.Msg("meeting-table-cooking"),
					"LabelFoodhelp": msg.Msg("meeting-table-foodhelp"),
				})
			case "meet":
				meetingCell.Execute(out, map[string]interface{}{
					"LabelAttending": msg.Msg("meeting-table-attending"),
				})
		}
		content = out.String()
	}
	return template.HTML(content)
}

func (p *MeetingPage) makeTableScheduleTotals(meeting db.Meeting)  template.HTML {
	diningCells, _ := template.New("diningCells").Parse(`<td>{{.Eating}}</td><td>{{.Cooking}}</td><td>{{.Foodhelp}}</td>`)
	meetingCell, _ := template.New("meetingCell").Parse(`<td>{{.Attending}}</td>`)
	content := ""
	type count struct {
		Eating int
		Cooking int
		Foodhelp int
		Attending int
	}
	counts := make(map[string]*count, len(meeting.Schedule))
	for itemId, _ := range meeting.Schedule {
		counts[itemId] = &count{0,0,0,0}
	}
	for _, user := range meeting.Users {
		for itemId, item := range user.Schedule {
			if item.Eating {
				counts[itemId].Eating++
			}
			if item.Cooking {
				counts[itemId].Cooking++
			}
			if item.Foodhelp {
				counts[itemId].Foodhelp++
			}
			if item.Attending {
				counts[itemId].Attending++
			}
		}
	}
	for _, item := range db.SortSchedule(meeting.Schedule) {
		out := bytes.NewBufferString(content)
		switch item.Type {
			case "eat":
				diningCells.Execute(out, counts[item.Id.String()])
			case "meet":
				meetingCell.Execute(out, counts[item.Id.String()])
		}
		content = out.String()
	}
	return template.HTML(content)
}

func (p *MeetingPage) createScheduleTable(meeting db.Meeting) string {
	userRow, _ := template.New("userRow").Parse(`	<tr>
		<td class="user"><span title="{{.UserRealName}}" class="username">{{.UserNickName}}</span></td>{{.UserSchedule}}<td class="comment">{{.Comment}}</td>
	</tr>`)
	schedule := meeting.Schedule
	table := ""
	users := db.GetUsers()
	for _, meetingItem := range db.SortUsersByName(meeting.Users) {
		userId := meetingItem.Id.String()
		var user *db.User
		if meetingItem.Usertype == "extra" {
			user = &db.User{
				Name: meetingItem.Name,
				Nickname: meetingItem.Name,
			}
		} else {
			user = users[userId]
		}
		out := bytes.NewBufferString(table)
		userRow.Execute(out, map[string]interface{}{
			"UserRealName": user.Name,
			"UserNickName": user.Nickname,
			"Comment":      meetingItem.CleanComment(),
			"UserSchedule": p.createUserSchedule(meetingItem, schedule)})
		table = out.String()
	}
	
	sortedSchedule := db.SortSchedule(schedule)
	content, _ := msg.HtmlMsg("", `<table>
	<tr>
		<th rowspan="2">{{.LabelSchedule}}</th>{{.ScheduleTop}}<th rowspan="3" class="comment">{{.LabelComment}}</th>
	</tr>
	<tr>
		{{.ScheduleMiddle}}
	</tr>
	<tr>
		<th>{{.LabelUser}}<br /><a href="javascript://" onclick="toggleNames();">{{.LabelRealNameToggle}}</a></th>{{.ScheduleBottom}}
	</tr>
{{.Table}}
	<tr>
		<th>{{.LabelUser}}</th>{{.ScheduleBottom}}<th rowspan="2">{{.LabelComment}}</th>
	</tr>
	<tr class="total">
		<td>{{.UsersTotal}}</td>{{.ScheduleTotals}}
	</tr>
</table>`, map[string]interface{}{
		"LabelSchedule": msg.Msg("meeting-table-schedule"), // LabelSchedule
		"LabelComment":  msg.Msg("meeting-table-comment"),  // LabelComment
		"LabelUser":     msg.Msg("meeting-table-user"),     // LabelUser
		"LabelRealNameToggle": msg.Msg("meeting-table-realnametoggle"),
		"ScheduleTop":    p.makeTableScheduleTop(sortedSchedule),
		"ScheduleMiddle": p.makeTableScheduleMiddle(sortedSchedule),
		"ScheduleBottom": p.makeTableScheduleBottom(sortedSchedule),
		"ScheduleTotals": p.makeTableScheduleTotals(meeting),
		"UsersTotal":     len(meeting.Users),
		"Table":          template.HTML(table),
	})
	return content
}

func (p *MeetingPage) Render() {
	meeting, err := p.GetMeeting()
	if err != nil {
		p.Page.redirect = "/"
		return
	}
	tojoin := false
	for _, item := range meeting.Schedule {
		if !item.Nojoin {
			tojoin = true
			break
		}
	}
	var content string
	if !tojoin {
		content, _ = msg.HtmlMsg("", `<p>{{.LabelNoProgramme}}</p>`,
			map[string]interface{}{
				"LabelNoProgramme": msg.Msg("meeting-noprogramme"),
			})
	} else {
		content = p.createScheduleTable(meeting)
		if p.auth.LoggedIn {
			content = p.UserForms(content, meeting)
		} else {
			content, _ = msg.HtmlMsg(content, `<p>{{.LabelNotLoggedInMessage}}</p>`, map[string]interface{}{
				"LabelNotLoggedInMessage": msg.Msg("meeting-notloggedin")})
		}
	}
	content, _ = msg.HtmlMsg("", `<h1>{{.Title}}</h1>
{{if .SubTitle}}<h3>{{.SubTitle}}</h3>{{end}}<h2>{{.WrittenDate}}</h2>
{{.Content}}`,
		map[string]interface{}{
		"Title":         meeting.Title,
		"SubTitle":      meeting.Comment,
		"WrittenDate":   p.date,
		"Content":       template.HTML(content),
	})
	
	p.Page.content = content
	p.Page.title = msg.Msg("meeting-title", map[string]interface{}{"Title":meeting.Title})
}
