package pages

import (
	"bytes"
	"db"
	"fmt"
	"html/template"
	"strings"
)

// These are specifically the forms on the meeting page, as the other file
// was getting a bit too big.

func (p *MeetingPage) closeOpenMeetingForms(output string) string {
	meeting := p.GetMeeting()
	for _, item := range db.SortSchedule(meeting.Schedule) {
		if item.Nojoin {
			continue
		}
		if item.Type == "eat" {
			if item.Open {
				output = htmlMsg(output, `<form method="post">
	<fieldset>
		<legend>{{.LabelCloseEatingLegend}}</legend>
		<label for="closeeating-{{.Id}}-spend">{{.LabelSpent}}</label>
		<input type="text" name="closeeating-{{.Id}}-spend" id="closeeating-{{.Id}}-spend" value="{{.SpendValue}}" />
		<input type="submit" name="closeeating-{{.Id}}-submit" value="{{.LabelCloseEatingSubmit}}" />
	</fieldset>
</form>`, map[string]interface{}{
					"Id": item.Id.Int(),
					"LabelCloseEatingLegend": p.s.msg("meeting-closeeating-title"),
					"LabelSpent":             p.s.msg("meeting-closeeating-spent"),
					"LabelCloseEatingSubmit": p.s.msg("meeting-closeeating-submit"),
					"SpendValue":             item.Spend,
				})
			} else if item.Closedby == p.s.auth.Uid {
				output = htmlMsg(output, `<form method="post">
	<fieldset>
		<legend>{{.LabelOpenEatingLegend}}</legend>
		<input type="submit" name="openeating-{{.Id}}-submit" value="{{.LabelOpenEatingSubmit}}" />
	</fieldset>
</form>`, map[string]interface{}{
					"Id": item.Id.Int(),
					"LabelOpenEatingLegend": p.s.msg("meeting-openeating-title"),
					"LabelOpenEatingSubmit": p.s.msg("meeting-openeating-submit"),
				})
			} else {
				closedByUser := db.GetUser(item.Closedby)
				output = htmlMsg(output, `<p>{{.LabelEatingClosedBy}}</p>`,
					map[string]interface{}{
						"LabelEatingClosedBy": template.HTML(p.s.msg("meeting-eatclosedby", map[string]interface{}{"Name": closedByUser.Name})),
					})
			}
		}
	}
	return output
}

func (p *MeetingPage) commitmentForm(output string, responded bool, index int, extraId, extraName string) string {
	meeting := p.GetMeeting()
	var form string
	diningForm := `<span class="scheduleform-item">{{.ItemTitle}}:</span>
<input type="checkbox" name="meeting-{{.Id}}-eating" id="meeting-{{.LabelId}}-eating"{{if .Closed}} disabled="true"{{end}}{{if .EatingChecked}} checked="true"{{end}} />
	<label for="meeting-{{.LabelId}}-eating">{{.LabelEating}}</label>
	<input type="checkbox" name="meeting-{{.Id}}-cooking" id="meeting-{{.LabelId}}-cooking"{{if .Closed}} disabled="true"{{end}}{{if .CookingChecked}} checked="true"{{end}} />
	<label for="meeting-{{.LabelId}}-cooking">{{.LabelCooking}}</label>
	<input type="checkbox" name="meeting-{{.Id}}-foodhelp" id="meeting-{{.LabelId}}-foodhelp"{{if .Closed}} disabled="true"{{end}}{{if .FoodhelpChecked}} checked="true"{{end}} />
	<label for="meeting-{{.LabelId}}-foodhelp">{{.LabelFoodhelp}}</label><br />`
	meetingForm := `<span class="scheduleform-item">{{.ItemTitle}}:</span>
<input type="checkbox" name="meeting-{{.Id}}-attending" id="meeting-{{.LabelId}}-attending"{{if .Closed}} disabled="true"{{end}}{{if .AttendingChecked}} checked="true"{{end}} />
	<label for="meeting-{{.LabelId}}-attending">{{.LabelAttending}}</label><br />`

	userId := p.s.auth.Uid.String()
	if extraId != "" {
		userId = fmt.Sprintf("%s-%s", userId, extraId)
	}

	for _, item := range db.SortSchedule(meeting.Schedule) {
		if item.Nojoin {
			continue
		}
		labelId := item.Id.String()
		if index > 0 {
			labelId = fmt.Sprintf("%d-%s", index, labelId)
		}
		if item.Type == "eat" {
			// Per default we assume people will be eating, but not
			// cooking or helping.
			eating, cooking, foodhelp := true, false, false
			if _, ok := meeting.Users[userId]; ok {
				// Oh, they have already committed to this event?
				// Then let's get their values.
				eating = meeting.Users[userId].Schedule[item.Id.String()].Eating
				cooking = meeting.Users[userId].Schedule[item.Id.String()].Cooking
				foodhelp = meeting.Users[userId].Schedule[item.Id.String()].Foodhelp
			} else if !item.Open {
				// But if there are no commitment and the meeting is
				// closed, then
				eating, cooking, foodhelp = false, false, false
			}
			form = htmlMsg(form, diningForm,
				map[string]interface{}{
					"LabelEating":     p.s.msg("meeting-form-eating"),
					"LabelCooking":    p.s.msg("meeting-form-cooking"),
					"LabelFoodhelp":   p.s.msg("meeting-form-foodhelp"),
					"Id":              item.Id.String(),
					"LabelId":         labelId,
					"EatingChecked":   eating,
					"CookingChecked":  cooking,
					"FoodhelpChecked": foodhelp,
					"Closed":          !item.Open,
					"ItemTitle":       item.Title,
				})
		} else {
			attending := true
			if _, ok := meeting.Users[userId]; ok {
				attending = meeting.Users[userId].Schedule[item.Id.String()].Attending
			} else if !item.Open {
				attending = false
			}
			form = htmlMsg(form, meetingForm,
				map[string]interface{}{
					"LabelAttending":   p.s.msg("meeting-form-attending"),
					"Id":               item.Id.String(),
					"LabelId":          labelId,
					"AttendingChecked": attending,
					"Closed":           !item.Open,
					"ItemTitle":        item.Title,
				})
		}
	}
	var meetingFormTitle string
	var meetingFormSubmit string
	if index > 0 {
		if responded {
			meetingFormTitle = "meeting-form-extra-title-change"
			meetingFormSubmit = "meeting-form-submit-change"
		} else {
			meetingFormTitle = "meeting-form-extra-title-new"
			meetingFormSubmit = "meeting-form-submit-new"
		}
	} else {
		if responded {
			meetingFormTitle = "meeting-form-title-change"
			meetingFormSubmit = "meeting-form-submit-change"
		} else {
			meetingFormTitle = "meeting-form-title-new"
			meetingFormSubmit = "meeting-form-submit-new"
		}
	}
	name := ""
	if index == 0 {
		name = p.s.auth.Uid.GetUser().Name
	} else if responded {
		name = meeting.Users[userId].Name
	}
	output = htmlMsg(output, `<form method="post">
<fieldset>
	<legend>{{.LabelMeetingForm}}</legend>
	{{if .ExtraPerson}}
	<p>{{.DescriptionExtraPerson}}</p>
	<input type="hidden" name="meeting-usertype" value="extra" />
	{{if .ExtraPersonId}}<input type="hidden" name="meeting-extraid" value="{{.ExtraPersonId}}" />{{end}}
	<label for="meeting-name">{{.LabelExtraPersonName}}</label>
	<input type="text" name="meeting-name" id="meeting-name"{{if .UserName}} value="{{.UserName}}"{{end}} class="distanceitself" />
	{{else}}
	<input type="hidden" name="meeting-usertype" value="self" />
	{{end}}
	{{.Form}}
	<br />
	<label for="meeting-comment">{{.LabelComment}}</label>
	<input type="text" name="meeting-comment" id="meeting-comment"{{if .Comment}} value="{{.Comment}}"{{end}} />
	<input type="submit" name="meeting-submit" value="{{.LabelSubmit}}" />
</fieldset>
</form>`, map[string]interface{}{
		"LabelMeetingForm": template.HTML(p.s.tmsg(meetingFormTitle,
			struct {
				Name string
			}{
				name,
			})),
		"LabelComment":           p.s.msg("meeting-form-comment"),
		"Comment":                meeting.Users[userId].Comment,
		"LabelSubmit":            p.s.msg(meetingFormSubmit),
		"UserName":               name,
		"ExtraPerson":            index > 0,
		"ExtraPersonId":          extraId,
		"DescriptionExtraPerson": p.s.msg("meeting-form-extra-intro"),
		"LabelExtraPersonName":   p.s.msg("meeting-form-extra-name"),
		"Form":                   template.HTML(form),
	})
	return output
}

func (p *MeetingPage) userCommitmentForm(output string, responded bool) string {
	return p.commitmentForm(output, responded, 0, "", "")
}

func (p *MeetingPage) extraCommitmentForm(output string, responded bool, index int, extraId, extraName string) string {
	return p.commitmentForm(output, responded, index, extraId, extraName)
}

func (p *MeetingPage) userForms(content string) string {
	meeting := p.GetMeeting()
	var output string
	if !meeting.Locked {
		responded := false
		for _, user := range meeting.Users {
			if user.Id == p.s.auth.Uid && user.IsUser() {
				responded = true
			}
		}
		output = p.userCommitmentForm(output, responded)
		if responded {
			output = p.closeOpenMeetingForms(output)
		}
		i := 1
		for id, user := range meeting.Users {
			sid := strings.Split(id, "-")
			if len(sid) == 2 && sid[0] == p.s.auth.Uid.String() {
				output = p.extraCommitmentForm(output, true, i, sid[1], user.Name)
				i++
			}
		}
		output = p.extraCommitmentForm(output, false, i, "", "")
	} else {
		output = htmlMsg(output, `<p>{{.LabelMeetingClosed}}</p>`, map[string]interface{}{
			"LabelMeetingClosed": p.s.msg("meeting-isclosed"),
		})
	}

	out := bytes.NewBufferString(content)
	out.WriteString(output)
	return out.String()
}

