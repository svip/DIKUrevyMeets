package msg

import (
	"conf"
	"encoding/json"
	"fmt"
	"log"
	"os"
	"sort"
)

const (
	defaultLanguage = "da_DK"
)

var serverConfiguration conf.ServerConfig

var messages map[string]*map[string]string

type Language struct {
	Code string
}

func init() {
	serverConfiguration, _ = conf.LoadConfiguration()
	FlushMessages()
}

func FlushMessages() {
	messages = make(map[string]*map[string]string)
	dir := serverConfiguration.MessageDirectory

	for _, lang := range GetLanguages() {
		messages[lang.Code] = getMessages(dir, lang.Code+".json")
	}
}

func getMessages(dir string, name string) *map[string]string {
	path := fmt.Sprintf("%s%s", dir, name)
	f, err := os.Open(path)
	if err != nil {
		log.Print(err)
		return nil
	}
	dec := json.NewDecoder(f)
	var v map[string]string
	if err = dec.Decode(&v); err != nil {
		log.Fatal(err)
	}
	f.Close()
	return &v
}

type RawMessage struct {
	Name   string
	String string
}

type RawMessages []RawMessage

func (a RawMessages) Len() int           { return len(a) }
func (a RawMessages) Swap(i, j int)      { a[i], a[j] = a[j], a[i] }
func (a RawMessages) Less(i, j int) bool { return a[i].Name < a[j].Name }

var avoidMessages = map[string]struct{}{
	"code": struct{}{},
	"name": struct{}{},
}

func GetRawMessages(language string) []RawMessage {
	language = GetLegalLanguage(language)
	var rawMessages []RawMessage
	for name, str := range *messages[language] {
		if _, ok := avoidMessages[name]; !ok {
			rawMessages = append(rawMessages, RawMessage{name, str})
		}
	}
	sort.Sort(RawMessages(rawMessages))
	return rawMessages
}

func GetRawDescriptions(language string) []RawMessage {
	language = GetLegalLanguage(language)
	var rawMessages []RawMessage
	for name, str := range *descriptions[language] {
		if _, ok := avoidMessages[name]; !ok {
			rawMessages = append(rawMessages, RawMessage{name, str})
		}
	}
	sort.Sort(RawMessages(rawMessages))
	return rawMessages
}

func GetLanguages() []Language {
	return []Language{Language{"da_DK"}}
}

func GetDefaultLanguage() string {
	return defaultLanguage
}

func GetLegalLanguage(cookieValue string) string {
	for _, language := range GetLanguages() {
		if language.Code == cookieValue {
			return language.Code
		}
	}
	return defaultLanguage
}

type Container struct {
	msgs map[string]string
}

func NewContainer(lang string, messageDir string) *Container {
	con := &Container{
		make(map[string]string),
	}
	con.msgs = *messages[lang]
	return con
}

func (c *Container) Msg(msg string, a ...interface{}) string {
	s, ok := c.msgs[msg]
	if !ok {
		return fmt.Sprintf("<%s>", msg)
	}
	return fmt.Sprintf(s, a...)
}

