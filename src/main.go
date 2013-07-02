package main

import (
	"io"
	"net/http"
	"net/url"
	"log"
	//"strings"
	"fmt"
)

func Meetings(w http.ResponseWriter, req *http.Request) {
	values, err := url.ParseQuery(req.URL.RawQuery)
	if err != nil {
		io.WriteString(w, "500")
		return
	}
	action := values.Get("action")
	io.WriteString(w, action)
	fmt.Println(req.URL.RawQuery)
}

func main () {
	// Handle media requests (which are files in the media directory)
	http.Handle("/media/", http.StripPrefix("/media/", http.FileServer(http.Dir("../media"))))
	http.HandleFunc("/", Meetings)
	err := http.ListenAndServe(":8080", nil)
	if err != nil {
		log.Fatal("ListenAndServe: ", err)
	}
}
