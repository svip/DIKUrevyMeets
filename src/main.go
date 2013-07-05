package main

import (
	"io"
	"log"
	"net/http"
	"pages"
)

func MainEntry(w http.ResponseWriter, req *http.Request) {
	io.WriteString(w, pages.HandleAction(req))
}

func main() {
	// Handle media requests (which are files in the media directory)
	http.Handle("/media/", http.StripPrefix("/media/", http.FileServer(http.Dir("../media"))))
	http.HandleFunc("/", MainEntry)
	err := http.ListenAndServe(":8080", nil)
	if err != nil {
		log.Fatal("ListenAndServe: ", err)
	}
}
