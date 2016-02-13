package auth

import (
	"conf"
	"database/sql"
	"db"
	"fmt"
	"log"
	"net/http"
	"regexp"

	_ "github.com/go-sql-driver/mysql"
)

var serverConfiguration conf.ServerConfig

func init() {
	serverConfiguration, _ = conf.LoadConfiguration()
}

const logininfoFile = "../data/config.json"

type UserAuth struct {
	Uid      db.UserId
	Name     string
	Nickname string
	IsAdmin  bool
	LoggedIn bool
}

func (a *UserAuth) GetName() string {
	if a.Name == "" {
		return a.Nickname
	}
	return a.Name
}

// Contact the Drupal sessions table to see if the user is logged in
func getUserAuthFromDb(cookieData string) *UserAuth {
	var url string
	if serverConfiguration.DbHost != "localhost" {
		url = fmt.Sprintf("%s:%s@tcp(%s:%d)/%s",
			serverConfiguration.DbUser, serverConfiguration.DbPass,
			serverConfiguration.DbHost, 3306, serverConfiguration.DbName)
	} else {
		url = fmt.Sprintf("%s:%s@/%s",
			serverConfiguration.DbUser, serverConfiguration.DbPass,
			serverConfiguration.DbName)
	}
	sqldb, err := sql.Open("mysql", url)
	if err != nil {
		log.Fatal(err)
	}
	defer sqldb.Close()
	row := sqldb.QueryRow(`SELECT s.uid, u.name, p.value
		FROM drupal_sessions s
		JOIN drupal_users u
			ON s.uid = u.uid
		LEFT JOIN drupal_profile_values p
			ON p.uid = s.uid AND p.fid = 14
		WHERE s.sid = ? AND s.uid != 0`, cookieData)
	if row == nil {
		return &UserAuth{LoggedIn: false}
	}
	// Since neither uid or nickname can be NULL, they are regular
	// strings, but since name could be NULL, we make it to a pointer.
	var uid int
	var nickname string
	var name *string
	if err := row.Scan(&uid, &nickname, &name); err != nil {
		log.Println(err)
		return &UserAuth{LoggedIn: false}
	}
	log.Println(uid, nickname, name)
	// If the name *is* NULL, then we just set it to the nickname.
	if name == nil {
		name = &nickname
	}
	if !db.UserExistsByDrupalId(uid) {
		db.CreateUser(uid, *name, nickname)
	}
	return &UserAuth{
		LoggedIn: true,
		IsAdmin:  db.GetUserByDrupalId(uid).Admin,
		Uid:      db.UserId(uid),
		Nickname: nickname,
		Name:     *name,
	}
}

func GetAuth(req *http.Request) *UserAuth {
	log.Println("Skip cookie checking for now, no SQL connection; always logged out")
	// Do not commit this!
	return &UserAuth{LoggedIn: true, Uid: 4, Nickname: "Brainfuck"}
	r, _ := regexp.Compile("SESS.*")
	for _, cookie := range req.Cookies() {
		if r.MatchString(cookie.Name) {
			return getUserAuthFromDb(cookie.Value)
		}
	}
	return &UserAuth{LoggedIn: false}
}

