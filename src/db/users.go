package db

import (
	"sort"
	"io/ioutil"
	"encoding/json"
	"log"
	"strconv"
	"fmt"
	"time"
)

// Functionality specifically related to users.json

type User struct {
	Id UserId
	Name string
	Register int64
	Admin bool
	Identity string
	Nickname string
}

type Users map[string]*User

var users Users
var usersLoaded bool
var readyToWriteUsers = make(chan bool)

const usersFile = "../data/users.json"

func loadUsers() {
	data, err := ioutil.ReadFile(usersFile)
	if err != nil {
		log.Fatal(err)
	}
	err = json.Unmarshal(data, &users)
	if err != nil {
		log.Fatal(err)
	}
	for userId, user := range users {
		if user.Id == 0 {
			i, _ := strconv.Atoi(userId)
			user.Id = UserId(i)
		}
	}
	fmt.Println("Users loaded.")
	usersLoaded = true
}

func WriteUsers() {
	if !<-readyToWriteUsers {
		return
	}
//	data, err := json.Marshal(users)
//	ioutil.WriteFile(usersFile, data, 0777)
//	log.Println("Users file written.")
}

func GetUsers() Users {
	if !usersLoaded {
		loadUsers()
	}
	return users
}

func UserExistsByDrupalId(uid string) bool {
	if !usersLoaded {
		loadUsers()
	}
	for userId := range users {
		if users[userId].Identity == uid {
			return true
		}
	}
	return false
}

func CreateUser(uid, name, nickname string) {
	id, _ := strconv.Atoi(uid)
	t := time.Now()
	users[uid] = &User{
		Id: UserId(id),
		Name: name,
		Nickname: nickname,
		Register: t.Unix(),
		Admin: false,
		Identity: uid,
	}
}

func GetUser (uid UserId) *User {
	user, ok := users[uid.String()]
	if !ok {
		return nil
	}
	return user
}

type UserSorter struct {
	users []UserSchedule
}

func (s UserSorter) Len() int { return len(s.users) }
func (s UserSorter) Swap(i, j int) { s.users[i], s.users[j] = s.users[j], s.users[i] }
func (s UserSorter) Less(i, j int) bool {
	return s.users[i].Name < s.users[j].Name
}

func SortUsersByName(users map[string]UserSchedule) (sorted []UserSchedule) {
	for userId := range users {
		sorted = append(sorted, users[userId])
	}
	us := &UserSorter{sorted}
	sort.Sort(us)
	return sorted
}
