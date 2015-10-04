package db

import (
	"encoding/json"
	"io/ioutil"
	"log"
	"sort"
	"strings"
	"time"
)

// Functionality specifically related to users.json

type User struct {
	Id       UserId
	Name     string
	Register timeStamp
	Admin    bool
	Identity int
	Nickname string
}

type Users map[UserId]User

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
		// Might be old data, let's
		err = parseLegacyUserData(data)
		if err != nil {
			log.Fatal(err)
		}
	}
	log.Println("Users loaded.")
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

func UserExistsByDrupalId(uid int) bool {
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

func GetUserByDrupalId(uid int) *User {
	if !usersLoaded {
		loadUsers()
	}
	for userId := range users {
		if users[userId].Identity == uid {
			user := users[userId]
			return &user
		}
	}
	return nil
}

func CreateUser(uid int, name, nickname string) {
	t := time.Now()
	users[UserId(uid)] = User{
		Id:       UserId(uid),
		Name:     name,
		Nickname: nickname,
		Register: timeStamp(t.Unix()),
		Admin:    false,
		Identity: uid,
	}
}

func GetUser(uid UserId) *User {
	if !usersLoaded {
		loadUsers()
	}
	user, ok := users[uid]
	if !ok {
		return nil
	}
	return &user
}

type UserSorter struct {
	users []UserSchedule
}

func (s UserSorter) Len() int      { return len(s.users) }
func (s UserSorter) Swap(i, j int) { s.users[i], s.users[j] = s.users[j], s.users[i] }
func (s UserSorter) Less(i, j int) bool {
	nameI := s.users[i].Name
	nameJ := s.users[j].Name
	// Handle the cases where the user name are not set in the schedule...
	if strings.Trim(nameI, " \n") == "" {
		nameI = users[s.users[i].Id].Name
	}
	if strings.Trim(nameJ, " \n") == "" {
		nameJ = users[s.users[j].Id].Name
	}
	return nameI < nameJ
}

func SortUsersByName(users map[UserId]UserSchedule) (sorted []UserSchedule) {
	for userId := range users {
		sorted = append(sorted, users[userId])
	}
	us := &UserSorter{sorted}
	sort.Sort(us)
	return sorted
}

