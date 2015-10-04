package db

import (
	"strconv"
	"strings"
)

type HourStamp string
type scheduleItemId int
type UserId int

func (h HourStamp) Int() int {
	i, err := strconv.Atoi(strings.Replace(strings.Replace(string(h), " ", "", -1), ":", "", -1))
	if err != nil {
		i = 0
	}
	return i
}

func (i scheduleItemId) String() string {
	return strconv.Itoa(int(i))
}

func (i scheduleItemId) Int() int {
	return int(i)
}

func (i UserId) String() string {
	return strconv.Itoa(int(i))
}

func (i UserId) Int() int {
	return int(i)
}

func (i UserId) IsEqual(test string) bool {
	return i.String() == test
}

func (i UserId) GetUser() User {
	if !usersLoaded {
		loadUsers()
	}
	return users[i]
}
