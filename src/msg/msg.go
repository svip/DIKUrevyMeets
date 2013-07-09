package msg

import (
	"fmt"
	"encoding/json"
	"io/ioutil"
	"log"
	"bytes"
	"html/template"
)

var messages map[string]string
var messagesLoaded bool

func loadMessages() {
	data, err := ioutil.ReadFile("../data/messages-da.json")
	if err != nil {
		log.Fatal(err)
	}
	err = json.Unmarshal(data, &messages)
	if err != nil {
		log.Fatal(err)
	}
	fmt.Println("Messages loaded.")
	messagesLoaded = true
}

func Msg(msg string, a ...interface{}) string {
	if !messagesLoaded {
		loadMessages()
	}
	if _, ok := messages[msg]; !ok {
		return fmt.Sprintf("<%s>", msg)
	}
	if len(a) > 0 {
		tmp, _ := HtmlMsg("", messages[msg], a[0])
		return tmp
	}
	return messages[msg]
	//return RawMsg(messages[msg], a...)
}

func HtmlMsg(existingContent string, tmplText string, data interface{}) (string, error) {
	t, err := template.New("tmp").Parse(tmplText)
	if err != nil {
		return "", err
	}
	out := bytes.NewBufferString(existingContent)
	t.Execute(out, data)
	return out.String(), nil
}
