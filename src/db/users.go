package db

import (
	"sort"
	"io/ioutil"
	"encoding/json"
	"log"
	"strconv"
	"fmt"
	"time"
	"strings"
)

// Functionality specifically related to users.json

type User struct {
	Id UserId
	Name string
	Register timeStamp
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

func GetUserByDrupalId(uid string) *User {
	if !usersLoaded {
		loadUsers()
	}
	for userId := range users {
		if users[userId].Identity == uid {
			return users[userId]
		}
	}
	return nil
}

func CreateUser(uid, name, nickname string) {
	id, _ := strconv.Atoi(uid)
	t := time.Now()
	users[uid] = &User{
		Id: UserId(id),
		Name: name,
		Nickname: nickname,
		Register: timeStamp(t.Unix()),
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
	nameI := s.users[i].Name
	nameJ := s.users[j].Name
	// Handle the cases where the user name are not set in the schedule...
	if strings.Trim(nameI, " \n") == "" {
		nameI = users[s.users[i].Id.String()].Name
	}
	if strings.Trim(nameJ, " \n") == "" {
		nameJ = users[s.users[j].Id.String()].Name
	}
	return nameI < nameJ
}

func SortUsersByName(users map[string]UserSchedule) (sorted []UserSchedule) {
	for userId := range users {
		sorted = append(sorted, users[userId])
	}
	us := &UserSorter{sorted}
	sort.Sort(us)
	return sorted
}
