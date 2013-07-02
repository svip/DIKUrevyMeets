package main

import (
	"io"
	"net/http"
	"net/url"
	"log"
	"fmt"
	"pages"
)

func MainEntry(w http.ResponseWriter, req *http.Request) {
	values, err := url.ParseQuery(req.URL.RawQuery)
	if err != nil {
		io.WriteString(w, "500")
		return
	}
	io.WriteString(w, pages.HandleAction(values.Get("action"), req))
	fmt.Println(req.URL.RawQuery)
}

func main () {
	// Handle media requests (which are files in the media directory)
	http.Handle("/media/", http.StripPrefix("/media/", http.FileServer(http.Dir("../media"))))
	http.HandleFunc("/", MainEntry)
	err := http.ListenAndServe(":8080", nil)
	if err != nil {
		log.Fatal("ListenAndServe: ", err)
	}
}
