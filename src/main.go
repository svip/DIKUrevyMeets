package main

import (
	"log"
	"net/http"
	"pages"
)

func main() {
	// Handle media requests (which are files in the media directory)
	http.Handle("/media/", http.StripPrefix("/media/", http.FileServer(http.Dir("../media"))))
	http.HandleFunc("/", pages.HandleAction)
	err := http.ListenAndServe(":8080", nil)
	if err != nil {
		log.Fatal("ListenAndServe: ", err)
	}
}
