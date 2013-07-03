package msg

import (
	"regexp"
	"fmt"
	"encoding/json"
	"io/ioutil"
	"log"
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
	return RawMsg(messages[msg], a...)
}

func RawMsg(rawmsg string, a ...interface{}) string {
	for i, s := range a {
		expr := fmt.Sprintf("\\$%d([^0-9]|$)", i+1)
		r, err := regexp.Compile(expr)
		if err != nil {
			return ""
		}
		var repl string
		switch s.(type) {
			case int:
				repl = fmt.Sprintf("%d", s)
			case float64:
			case float32:
				repl = fmt.Sprintf("%f", s)
			case string:
				repl = fmt.Sprintf("%s", s)
			//case []byte:
			//	n, err := conn.Read(s[0:])
			//	repl = string(s[0:n])
		}
		rawmsg = r.ReplaceAllString(rawmsg, repl)
	}
	return rawmsg
}
