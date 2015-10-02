package msg

import (
	"encoding/json"
	"fmt"
	"log"
	"os"
	"path/filepath"
)

const (
	defaultLanguage = "da_DK"
)

var currentLanguage = defaultLanguage
var msgs map[string]map[string]string

func init() {
	msgs = make(map[string]map[string]string)
}

func loadMessages(path string, info os.FileInfo, err error) error {
	if info.IsDir() {
		return nil
	}
	file, err := os.Open(path)
	if err != nil {
		return err
	}
	data := make([]byte, info.Size())
	_, err = file.Read(data)
	if err != nil {
		return err
	}
	var lMsgs map[string]string
	err = json.Unmarshal(data, &lMsgs)
	if err != nil {
		return err
	}
	err = file.Close()
	if err != nil {
		return err
	}
	msgs[lMsgs["code"]] = lMsgs
	return nil
}

func LoadMessages(baseDir string) {
	err := filepath.Walk(baseDir, loadMessages)
	if err != nil {
		log.Fatal(err)
	}
}

func Msg(msg string, a ...interface{}) string {
	s, ok := msgs[currentLanguage][msg]
	if !ok {
		return fmt.Sprintf("<%s>", msg)
	} else {
		return fmt.Sprintf(s, a...)
	}
}
