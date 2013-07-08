package pages

import (
	"msg"
	"db"
	"bytes"
	"html/template"
)

// These are specifically the forms on the meeting page, as the other file
// was getting a big too big.

func (p *MeetingPage) closeOpenMeetingForms(output string, meeting db.Meeting) string {
	for _, item := range db.SortSchedule(meeting.Schedule) {
		if item.Nojoin {
			continue
		}
		if item.Type == "eat" {
			if item.Open {
				output, _ = msg.HtmlMsg(output, `<form method="post">
	<fieldset>
		<legend>{{.LabelCloseEatingLegend}}</legend>
		<label for="closeeating-{{.Id}}-spend">{{.LabelSpent}}</label>
		<input type="text" name="closeeating-{{.Id}}-spend" id="closeeating-{{.Id}}-spend" value="{{.SpendValue}}" />
		<input type="submit" name="closeeating-{{.Id}}-submit" value="{{.LabelCloseEatingSubmit}}" />
	</fieldset>
</form>`, struct {
					Id int
					LabelCloseEatingLegend, LabelSpent, LabelCloseEatingSubmit string
					SpendValue float32
				}{
					Id: item.Id.Int(),
					LabelCloseEatingLegend: msg.Msg("meeting-closeeating-title"),
					LabelSpent:             msg.Msg("meeting-closeeating-spent"),
					LabelCloseEatingSubmit: msg.Msg("meeting-closeeating-submit"),
					SpendValue: item.Spend,
				})
			} else if item.Closedby.IsEqual(p.auth.Uid) {
				output, _ = msg.HtmlMsg(output, `<form method="post">
	<fieldset>
		<legend>{{.LabelOpenEatingLegend}}</legend>
		<input type="submit" name="openeating-{{.Id}}-submit" value="{{.LabelOpenEatingSubmit}}" />
	</fieldset>
</form>`, struct {
					Id int
					LabelOpenEatingLegend, LabelOpenEatingSubmit string
				}{
					Id: item.Id.Int(),
					LabelOpenEatingLegend: msg.Msg("meeting-openeating-title"),
					LabelOpenEatingSubmit: msg.Msg("meeting-openeating-submit"),
				})
			} else {
				closedByUser := db.GetUser(item.Closedby)
				output, _ = msg.HtmlMsg(output, `<p>{{.LabelEatingClosedBy}}</p>`,
				struct {
					LabelEatingClosedBy string
				}{
					LabelEatingClosedBy: msg.Msg("meeting-eatclosedby", closedByUser.Name),
				})
			}
		}
	}
	return output
}

func (p *MeetingPage) UserForms(content string, meeting db.Meeting) string {
	var output string
	responded := false
	for _, user := range meeting.Users {
		if user.Id.IsEqual(p.auth.Uid) {
			responded = true
		}
	}
	if responded {
		output = p.closeOpenMeetingForms(output, meeting)
	}
	var form string
	diningForm := `<span class="scheduleform-item">{{.ItemTitle}}:</span>
<input type="checkbox" name="meeting-{{.Id}}-eating" id="meeting-{{.Id}}-eating"{{if .Closed}} disabled="true"{{else}}{{if .EatingChecked}} checked="true"{{end}}{{end}} />
	<label for="meeting-{{.Id}}-eating">{{.LabelEating}}</label>
	<input type="checkbox" name="meeting-{{.Id}}-cooking" id="meeting-{{.Id}}-cooking"{{if .Closed}} disabled="true"{{else}}{{if .CookingChecked}} checked="true"{{end}}{{end}} />
	<label for="meeting-{{.Id}}-cooking">{{.LabelCooking}}</label>
	<input type="checkbox" name="meeting-{{.Id}}-foodhelp" id="meeting-{{.Id}}-foodhelp"{{if .Closed}} disabled="true"{{else}}{{if .FoodhelpChecked}} checked="true"{{end}}{{end}} />
	<label for="meeting-{{.Id}}-foodhelp">{{.LabelFoodhelp}}</label>`
	meetingForm := `<span class="scheduleform-item">{{.ItemTitle}}:</span>
<input type="checkbox" name="meeting-{{.Id}}-attending" id="meeting-{{.Id}}-attending"{{if .Closed}} disabled="true"{{else}}{{if .AttendingChecked}} checked="true"{{end}}{{end}} />
	<label for="meeting-{{.Id}}-attending">{{.LabelAttending}}</label>`
	for _, item := range db.SortSchedule(meeting.Schedule) {
		if item.Nojoin {
			continue
		}
		if item.Type == "eat" {
			form, _ = msg.HtmlMsg(form, diningForm,
				struct {
					LabelEating, LabelCooking, LabelFoodhelp string
					Id int
					EatingChecked, CookingChecked, FoodhelpChecked bool
					Closed bool
					ItemTitle string
				}{
					LabelEating:   msg.Msg("meeting-form-eating"),
					LabelCooking:  msg.Msg("meeting-form-cooking"),
					LabelFoodhelp: msg.Msg("meeting-form-foodhelp"),
					Id:  item.Id.Int(),
					EatingChecked: meeting.Users[p.auth.Uid].Schedule[item.Id.String()].Eating,
					CookingChecked: meeting.Users[p.auth.Uid].Schedule[item.Id.String()].Cooking,
					FoodhelpChecked: meeting.Users[p.auth.Uid].Schedule[item.Id.String()].Foodhelp,
					Closed:        !item.Open,
					ItemTitle:     item.Title,
			})
		} else {
			form, _ = msg.HtmlMsg(form, meetingForm,
				struct {
					LabelAttending string
					Id int
					AttendingChecked bool
					Closed bool
					ItemTitle string
				}{
					LabelAttending:   msg.Msg("meeting-form-attending"),
					Id:  item.Id.Int(),
					AttendingChecked: meeting.Users[p.auth.Uid].Schedule[item.Id.String()].Attending,
					Closed:        !item.Open,
					ItemTitle:     item.Title,
			})
		}
	}
	output, _ = msg.HtmlMsg(output, `<form method="post">
<fieldset>
	<legend>{{.LabelMeetingForm}}</legend>
	<input type="hidden" name="meeting-usertype" value="{{.UserType}}" />
	{{.Form}}<br />
	<label for="meeting-comment">{{.LabelComment}}</label>
	<input type="text" name="meeting-comment" id="meeting-comment" />
	<input type="submit" name="meeting-submit" value="{{.LabelSubmit}}" />
</fieldset>
</form>`, struct {
		LabelMeetingForm, LabelComment, LabelSubmit string
		UserType string
		Form template.HTML
	}{
		LabelMeetingForm:   msg.Msg("meeting-form-title"),
		LabelComment:       msg.Msg("meeting-form-comment"),
		LabelSubmit:        msg.Msg("meeting-form-submit"),
		UserType:           "self",
		Form:               template.HTML(form),
	})
	out := bytes.NewBufferString(content)
	out.WriteString(output)
	return out.String()
}
