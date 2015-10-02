package main

import (
	"conf"
	"fmt"
	"log"
	"net/http"
	"pages"
)

func main() {
	conf, err := conf.LoadConfiguration()
	if err != nil {
		log.Fatal("Configuration load: ", err)
	}

	http.Handle("/media/", http.StripPrefix("/media/", http.FileServer(http.Dir(conf.MediaDirectory))))
	http.HandleFunc("/favicon.ico", func(w http.ResponseWriter, r *http.Request) {
		http.ServeFile(w, r, conf.MediaDirectory+"favicon.ico")
	})
	http.HandleFunc("/", pages.PageEntry)

	err = http.ListenAndServe(fmt.Sprintf("localhost:%d", conf.Port), nil)
	if err != nil {
		log.Fatal("ListenAndServe: ", err)
	}
}

