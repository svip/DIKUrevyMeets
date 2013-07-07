package auth

import (
	"encoding/json"
	"io/ioutil"
	"database/sql"
	"fmt"
	"log"
	"net/http"
	"regexp"
	"db"
	_ "github.com/go-sql-driver/mysql"
)

const logininfoFile = "../data/config.json"

type UserAuth struct {
	Uid string
	Name string
	Nickname string
	LoggedIn bool
}

// Contact the Drupal sessions table to see if the user is logged in
func getUserAuthFromDb(cookieData string) *UserAuth {
	var LogInInfo struct {
		Dbhost string
		Dbname string
		Dbuser string
		Dbpass string
	}
	data, err := ioutil.ReadFile(logininfoFile)
	if err != nil {
		log.Fatal(err)
	}
	err = json.Unmarshal(data, &LogInInfo)
	if err != nil {
		log.Fatal(err)
	}
	var sqldb *sql.DB
	if LogInInfo.Dbhost != "localhost" {
		sqldb, err = sql.Open("mysql", fmt.Sprintf("%s:%s@tcp(%s:%d)/%s",
			LogInInfo.Dbuser, LogInInfo.Dbpass, LogInInfo.Dbhost, 3306, LogInInfo.Dbname))
	} else {
		sqldb, err = sql.Open("mysql", fmt.Sprintf("%s:%s@/%s",
			LogInInfo.Dbuser, LogInInfo.Dbpass, LogInInfo.Dbname))
	}
	defer sqldb.Close()
	log.Println(cookieData)
	row := sqldb.QueryRow(`SELECT s.uid, u.name, p.value
		FROM drupal_sessions s
		JOIN drupal_users u
		ON s.uid = u.uid
		LEFT JOIN drupal_profile_values p
		ON p.uid = s.uid AND p.fid = 14
		WHERE s.sid = ? AND s.uid != 0`, cookieData)
	log.Println(row)
	if row == nil {
		return &UserAuth{LoggedIn:false}
	}
	// Since neither uid or nickname can be NULL, they are regular
	// strings, but since name could be NULL, we make it to a pointer.
	var uid string
	var nickname string
	var name *string
	err = row.Scan(&uid, &nickname, &name)
	if err != nil {
		log.Println(err)
		return &UserAuth{LoggedIn:false}
	}
	// If the name *is* NULL, then we just set it to the nickname.
	if name == nil {
		name = &nickname
	}
	if !db.UserExistsByDrupalId(uid) {
		db.CreateUser(uid, *name, nickname)
	}
	return &UserAuth{
		LoggedIn: true,
		Uid:      uid,
		Nickname: nickname,
		Name:     *name,
	}
}

func GetAuth(req *http.Request) *UserAuth {
	r, _ := regexp.Compile("SESS.*")
	for _, cookie := range req.Cookies() {
		if r.MatchString(cookie.Name) {
			return getUserAuthFromDb(cookie.Value)
		}
	}
	return &UserAuth{LoggedIn:false}
}
