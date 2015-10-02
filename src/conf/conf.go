package conf

import (
	"encoding/json"
	"os"
)

type ServerConfig struct {
	Port              int
	MediaDirectory    string
	MessageDirectory  string
	TemplateDirectory string
	DataDirectory     string
	DbName            string
	DbUser            string
	DbPass            string
	DbHost            string
}

func LoadConfiguration() (ServerConfig, error) {
	file, err := os.Open("serverconfig.json")
	if err != nil {
		return ServerConfig{}, err
	}
	fi, _ := file.Stat()
	data := make([]byte, fi.Size())
	_, err = file.Read(data)
	if err != nil {
		return ServerConfig{}, err
	}
	var conf ServerConfig
	err = json.Unmarshal(data, &conf)
	if err != nil {
		return ServerConfig{}, err
	}
	err = file.Close()
	if err != nil {
		return ServerConfig{}, err
	}
	return conf, nil
}

