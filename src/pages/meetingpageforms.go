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
</form>`, map[string]interface{}{
					"Id": item.Id.Int(),
					"LabelCloseEatingLegend": msg.Msg("meeting-closeeating-title"),
					"LabelSpent":             msg.Msg("meeting-closeeating-spent"),
					"LabelCloseEatingSubmit": msg.Msg("meeting-closeeating-submit"),
					"SpendValue": item.Spend,
				})
			} else if item.Closedby.IsEqual(p.auth.Uid) {
				output, _ = msg.HtmlMsg(output, `<form method="post">
	<fieldset>
		<legend>{{.LabelOpenEatingLegend}}</legend>
		<input type="submit" name="openeating-{{.Id}}-submit" value="{{.LabelOpenEatingSubmit}}" />
	</fieldset>
</form>`, map[string]interface{}{
					"Id": item.Id.Int(),
					"LabelOpenEatingLegend": msg.Msg("meeting-openeating-title"),
					"LabelOpenEatingSubmit": msg.Msg("meeting-openeating-submit"),
				})
			} else {
				closedByUser := db.GetUser(item.Closedby)
				output, _ = msg.HtmlMsg(output, `<p>{{.LabelEatingClosedBy}}</p>`,
				map[string]interface{}{
					"LabelEatingClosedBy": template.HTML(msg.Msg("meeting-eatclosedby", map[string]interface{}{"Name":closedByUser.Name})),
				})
			}
		}
	}
	return output
}

func (p *MeetingPage) commitmentForm(output string, meeting db.Meeting, responded bool) string {
	var form string
	diningForm := `<span class="scheduleform-item">{{.ItemTitle}}:</span>
<input type="checkbox" name="meeting-{{.Id}}-eating" id="meeting-{{.Id}}-eating"{{if .Closed}} disabled="true"{{end}}{{if .EatingChecked}} checked="true"{{end}} />
	<label for="meeting-{{.Id}}-eating">{{.LabelEating}}</label>
	<input type="checkbox" name="meeting-{{.Id}}-cooking" id="meeting-{{.Id}}-cooking"{{if .Closed}} disabled="true"{{end}}{{if .CookingChecked}} checked="true"{{end}} />
	<label for="meeting-{{.Id}}-cooking">{{.LabelCooking}}</label>
	<input type="checkbox" name="meeting-{{.Id}}-foodhelp" id="meeting-{{.Id}}-foodhelp"{{if .Closed}} disabled="true"{{end}}{{if .FoodhelpChecked}} checked="true"{{end}} />
	<label for="meeting-{{.Id}}-foodhelp">{{.LabelFoodhelp}}</label><br />`
	meetingForm := `<span class="scheduleform-item">{{.ItemTitle}}:</span>
<input type="checkbox" name="meeting-{{.Id}}-attending" id="meeting-{{.Id}}-attending"{{if .Closed}} disabled="true"{{end}}{{if .AttendingChecked}} checked="true"{{end}} />
	<label for="meeting-{{.Id}}-attending">{{.LabelAttending}}</label><br />`
	for _, item := range db.SortSchedule(meeting.Schedule) {
		if item.Nojoin {
			continue
		}
		if item.Type == "eat" {
			// Per default we assume people will be eating, but not
			// cooking or helping.
			eating, cooking, foodhelp := true, false, false
			if _, ok := meeting.Users[p.auth.Uid]; ok {
				// Oh, they have already committed to this event?
				// Then let's get their values.
				eating = meeting.Users[p.auth.Uid].Schedule[item.Id.String()].Eating
				cooking = meeting.Users[p.auth.Uid].Schedule[item.Id.String()].Cooking
				foodhelp = meeting.Users[p.auth.Uid].Schedule[item.Id.String()].Foodhelp
			} else if !item.Open {
				// But if there are no commitment and the meeting is
				// closed, then 
				eating, cooking, foodhelp = false, false, false
			}
			form, _ = msg.HtmlMsg(form, diningForm,
				map[string]interface{}{
					"LabelEating":   msg.Msg("meeting-form-eating"),
					"LabelCooking":  msg.Msg("meeting-form-cooking"),
					"LabelFoodhelp": msg.Msg("meeting-form-foodhelp"),
					"Id":  item.Id.Int(),
					"EatingChecked": eating,
					"CookingChecked": cooking,
					"FoodhelpChecked": foodhelp,
					"Closed":        !item.Open,
					"ItemTitle":     item.Title,
			})
		} else {
			attending := true
			if _, ok := meeting.Users[p.auth.Uid]; ok {
				attending = meeting.Users[p.auth.Uid].Schedule[item.Id.String()].Attending
			} else if !item.Open {
				attending = false
			}
			form, _ = msg.HtmlMsg(form, meetingForm,
				map[string]interface{}{
					"LabelAttending":   msg.Msg("meeting-form-attending"),
					"Id":  item.Id.Int(),
					"AttendingChecked": attending,
					"Closed":        !item.Open,
					"ItemTitle":     item.Title,
			})
		}
	}
	
	var meetingFormTitle string
	var meetingFormSubmit string
	if responded {
		meetingFormTitle = "meeting-form-title-change"
		meetingFormSubmit = "meeting-form-submit-change"
	} else {
		meetingFormTitle = "meeting-form-title-new"
		meetingFormSubmit = "meeting-form-submit-new"
	}
	output, _ = msg.HtmlMsg(output, `<form method="post">
<fieldset>
	<legend>{{.LabelMeetingForm}}</legend>
	<input type="hidden" name="meeting-usertype" value="{{.UserType}}" />
	{{.Form}}
	<label for="meeting-comment">{{.LabelComment}}</label>
	<input type="text" name="meeting-comment" id="meeting-comment" />
	<input type="submit" name="meeting-submit" value="{{.LabelSubmit}}" />
</fieldset>
</form>`, map[string]interface{}{
		"LabelMeetingForm":   template.HTML(msg.Msg(meetingFormTitle, struct{Name string}{p.auth.Name})),
		"LabelComment":       msg.Msg("meeting-form-comment"),
		"LabelSubmit":        msg.Msg(meetingFormSubmit),
		"UserType":           "self",
		"Form":               template.HTML(form),
	})
	return output
}

func (p *MeetingPage) UserForms(content string, meeting db.Meeting) string {
	var output string
	if !meeting.Locked {
		responded := false
		for _, user := range meeting.Users {
			if user.Id.IsEqual(p.auth.Uid) {
				responded = true
			}
		}
		if responded {
			output = p.closeOpenMeetingForms(output, meeting)
		}
		output = p.commitmentForm(output, meeting, responded)
	} else {
		output, _ = msg.HtmlMsg(output, `<p>{{.LabelMeetingClosed}}</p>`, map[string]interface{}{
			"LabelMeetingClosed": msg.Msg("meeting-isclosed"),
		})
	}
	
	out := bytes.NewBufferString(content)
	out.WriteString(output)
	return out.String()
}
