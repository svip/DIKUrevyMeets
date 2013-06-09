<?php

class Database {

	private $users = null;
	private $meetings = null;
	private $usersFile = null;
	private $meetingsFile = null;
	
	public function __construct ( ) {
		$this->usersFile = @fopen ( "data/users.json", 'r+' );
		$this->meetingsFile = @fopen ( "data/meetings.json", 'r+' );
		if ( empty($this->usersFile) || empty($this->meetingsFile) ) {
			// If we fail, tell the user.
			die('Kunne ikke få adgang til datafilerne.  Kontakt administratorer.');
		}
		flock($this->usersFile, LOCK_EX);
		flock($this->meetingsFile, LOCK_EX);
		$this->users = json_decode ( trim(fread($this->usersFile, 
			filesize("data/users.json") )) );
		rewind($this->usersFile);
		$this->meetings = json_decode ( trim(fread($this->meetingsFile, 
			filesize("data/meetings.json") )) );
		rewind($this->meetingsFile);
		$this->checkMeetings();
	}
	
	public function __destruct ( ) {
		@fclose($this->usersFile);
		@fclose($this->meetingsFile);
	}
	
	private function writeData ( $file ) {
		switch ( $file ) {
			case 'meetings':
				ftruncate ( $this->meetingsFile, 0 );
				$str = str_replace ( '},', "},\n", json_encode ( $this->meetings, JSON_FORCE_OBJECT ) );
				fwrite ( $this->meetingsFile, $str );
				rewind( $this->meetingsFile );
				break;
			case 'users':
				ftruncate ( $this->usersFile, 0 );
				$str = str_replace ( '},', "},\n", json_encode ( $this->users, JSON_FORCE_OBJECT ) );
				fwrite ( $this->usersFile, $str );
				rewind( $this->usersFile );
				break;
			default: break;
		}
	}
	
	private function checkMeetings ( ) {
		foreach ( $this->meetings as $date => $meeting ) {
			if ( $this->isBeforeToday($date) ) {
				// lock and hide events if they are before today
				// admins can overrule this.
				// TODO: Allow admins to overrule this.
				$this->meetings->{$date}->hidden = true;
				$this->meetings->{$date}->locked = true;
			}
		}
		$this->writeData ( 'meetings' );
	}
	
	private function isBeforeToday ( $date ) {
		return strtotime($date) < time()-24*60*60;
	}
	
	function getMeeting ( $date ) {
		if ( !isset ( $this->meetings->{$date} ) )
			return null;
		return $this->meetings->{$date};
	}
	
	function getMeetingBefore ( $checkDate, $onlyWithSchedule=true ) {
		$testDate = false;
		foreach ( $this->getSortedMeetings() as $date => $meeting ) {
			if ( $date == $checkDate )
				return $testDate;
			if ( $onlyWithSchedule && isset($meeting->schedule->{0}) )
				$testDate = array (
					'date'  => $date,
					'title' => $meeting->title
				);
		}
		return $testDate;
	}
	
	function getMeetingAfter ( $checkDate, $onlyWithSchedule=true ) {
		$returnNext = false;
		foreach ( $this->getSortedMeetings() as $date => $meeting ) {
			$testDate = array (
				'date'  => $date,
				'title' => $meeting->title
			);
			if ( $returnNext
				&& ( $onlyWithSchedule && isset($meeting->schedule->{0}) ) )
				return $testDate;
			if ( $date == $checkDate )
				$returnNext = true;
		}
		return false;
	}
	
	function getUsers ( ) {
		return $this->users;
	}
	
	function getUserById ( $id ) {
		if ( !empty($this->users->{$id} ) )
			return $this->users->{$id};
		return null;
	}
	
	function getUser ( $name ) {
		foreach ( $this->users as $user )
			if ( $user->name == $name )
				return $user;
		return null;
	}
	
	function getUserId ( $name ) {
		foreach ( $this->users as $id => $user )
			if ( $user->name == $name )
				return $id;
		return null;
	}
	
	function getMeetings ( ) {
		return $this->meetings;
	}
	
	function getTags ( ) {
		$tags = array();
		foreach ( $this->meetings as $meeting ) {
			foreach ( $meeting->tags as $tag ) {
				if ( trim($tag)=='' )
					continue;
				if ( !in_array($tag, $tags) )
					$tags[] = $tag;
			}
		}
		return $tags;
	}
	
	function getSortedMeetings ( $includeHidden=false ) {
		$tmp = array();
		foreach ( $this->meetings as $date => $meeting )
			if ( $includeHidden || (!isset($meeting->hidden) || !$meeting->hidden) )
				$tmp[$date] = $meeting;
		ksort($tmp);
		return $tmp;
	}
	
	function stripHtml ( $string ) {
		return strip_tags($string);
		//return str_replace(array('<', '>'), array('&lt;', '&gt;'), $string);
	}
	
	function insertMeeting ( $date, $title, $schedule=array(array
			('title'=>'Aftensmad','type'=>'eat','start'=>'18:00',
			'end'=>'19:00','open'=>true,'spend'=>0.0,'costperperson'=>0.0,
			'unique'=>false),
			array('title'=>'Møde','type'=>'meet','start'=>'19:00','end'=>'23:00',
			'unique'=>false)), $comment='', $days=false, $tags=array() ) {
		if ( !preg_match ( '@[0-9]{4}-[0-9]{2}-[0-9]{2}@', $date ) )
			return $this->meetings;
		if ( !empty( $this->meetings->{$date} ) )
			return $this->meetings;
		foreach ( $schedule as $item ) {
			if ( !in_array ( $item['type'], array('eat', 'meet') )
				|| !preg_match ( '@[0-9]{2}:[0-9]{2}@', $item['start'] )
				|| !preg_match ( '@[0-9]{2}:[0-9]{2}@', $item['end'] ) )
					return $this->meetings;
		}
		$this->meetings->{$date} = array (
			'title'			=> $title,
			'schedule'		=> $schedule,
			'comment'		=> $this->stripHtml($comment),
			'users'			=> array(),
			'hidden'		=> false,
			'locked'		=> false,
			'tags'			=> $tags,
			'days'			=> $days,
		);
		$this->writeData ( 'meetings' );
		return $this->meetings;
	}
	
	function deleteMeeting ( $date ) {
		unset($this->meetings->{$date});
		$this->writeData ( 'meetings' );
		return true;
	}
	
	function updateMeeting ( $date, $title, $comment, $schedule, $days=false,
		$tags=array(), $locked=null, $hidden=null, $ignoreConstraints=false ) {
		if ( !preg_match ( '@[0-9]{4}-[0-9]{2}-[0-9]{2}@', $date ) )
			return false;
		if ( empty( $this->meetings->{$date} ) )
			return false;
		if ( @$this->meeting->{$date}->locked
			&& !$ignoreConstraints )
			// don't do anything with a locked meeting.
			return false;
		foreach ( $schedule as $id => $item )
			if ( $item['type'] == 'eat' )
				$schedule[$id]['open'] = $this->meetings->{$date}->schedule->{$id}->open;
		$this->meetings->{$date}->title = $title;
		$this->meetings->{$date}->comment = $comment;
		$this->meetings->{$date}->schedule = $schedule;
		if ( !is_null($locked) )
			$this->meetings->{$date}->locked = $locked;
		if ( !is_null($hidden) )
			$this->meetings->{$date}->hidden = $hidden;
		$this->meetings->{$date}->tags = $tags;
		if ( $days!==false )
			$this->meetings->{$date}->days = $days;
		else
			$this->meetings->{$date}->days = false;
		$this->calculateSpend($date);
		$this->writeData ( 'meetings' );
		return true;
	}
	
	function moveMeeting ( $olddate, $newdate ) {
		if ( !preg_match ( '@[0-9]{4}-[0-9]{2}-[0-9]{2}@', $olddate ) )
			return false;
		if ( !preg_match ( '@[0-9]{4}-[0-9]{2}-[0-9]{2}@', $newdate ) )
			return false;
		if ( empty( $this->meetings->{$olddate} ) )
			return false;
		$this->meetings->{$newdate} = $this->meetings->{$olddate};
		unset($this->meetings->{$olddate});
		$this->writeData ( 'meetings' );
		return true;
	}
	
	function insertUser ( $name, $nickname, $service, $uid, $signature ) {
		if ( isset($this->users->{$uid}) )
			return false;
		$this->users->{$uid} = array (
			'name'		=> $name,
			'nickname'      => $nickname,
			'register'	=> time(),
			'admin'		=> false,
			'identity'	=> $uid,
		);
		$this->writeData ( 'users' );
		return true;
	}
	
	function updateUser ( $userid, $dataToUpdate ) {
		if ( empty ( $this->users->{$userid} ) )
			return false;
		if ( !empty($dataToUpdate['realname']) )
			$this->users->{$userid}->name = $dataToUpdate['realname'];
		if ( !empty($dataToUpdate['nickname']) )
			$this->users->{$userid}->nickname = $dataToUpdate['nickname'];
		if ( isset($dataToUpdate['admin']) )
			$this->users->{$userid}->admin = $dataToUpdate['admin'];
		$this->writeData ( 'users' );
		return true;
	}
	
	function addUserToDate ( $date, $name, $userSchedule, $comment,
		$useridSupplied=false, $ignoreConstraints=false ) {
		if ( empty ( $this->meetings->{$date} ) )
			return false;
		if ( @$this->meetings->{$date}->locked
			&& !$ignoreConstraints )
			return false;
		if ( $useridSupplied ) {
			$userid = $name;
		} else {
			$userid = null;
			foreach ( $this->users as $id => $user )
				if ( $user->name == $name ) {
					$userid = $id;
					break;
				}
		}
		if ( $userid == null )
			return false;
		if ( !$ignoreConstraints ) {
			foreach ( $this->meetings->{$date}->schedule as $id => $item ) {
				if ( $item->type == 'eat'
					&& !$item->open ) {
					if ( is_array ( $userSchedule ) ) {
						if ( !empty($this->meetings->{$date}->{'users'}->{$userid}) ) {
							$userSchedule[$id]['eating'] = $this->meetings->{$date}->{'users'}->{$userid}->schedule->{$id}->eating;
							$userSchedule[$id]['cooking'] = $this->meetings->{$date}->{'users'}->{$userid}->schedule->{$id}->cooking;
							$userSchedule[$id]['foodhelp'] = @$this->meetings->{$date}->{'users'}->{$userid}->schedule->{$id}->foodhelp;
						} else {
							$userSchedule[$id]['eating'] = false;
							$userSchedule[$id]['cooking'] = false;
							$userSchedule[$id]['foodhelp'] = false;
						}
					}
				}
			}
		}
		$this->meetings->{$date}->{'users'}->{$userid} = array (
			'schedule'	=> $userSchedule,
			'usertype'	=> 'normal',
			'comment'	=> $comment,
			'modified'	=> ($ignoreConstraints
				?$this->meetings->{$date}->{'users'}->{$userid}->modified
				:time())
		);
		$this->calculateSpend($date);
		$this->writeData ( 'meetings' );
		return true;
	}
	
	function makeSubId ( $name ) {
		return substr(md5($name), 0, 4);
	}
	
	function addNonUserToDate ( $date, $ownerid, $name, $userSchedule, $comment,
		$ignoreConstraints=false, $fullUserIdSupplied=false ) {
		if ( empty ( $this->meetings->{$date} ) )
			return false;
		if ( empty ( $ownerid ) || empty( $name ) )
			return false;
		if ( @$this->meetings->{$date}->locked
			&& !$ignoreConstraints )
			return false;
		if ( !$fullUserIdSupplied )
			$userid = $ownerid.'-'.$this->makeSubId($name);
		else
			$userid = $ownerid;
		if ( !$ignoreConstraints ) {
			foreach ( $this->meetings->{$date}->schedule as $id => $item ) {
				if ( $item->type == 'eat'
					&& !$item->open ) {
					if ( is_array ( $userSchedule ) ) {
						if ( !empty($this->meetings->{$date}->{'users'}->{$userid}) ) {
							$userSchedule[$id]['eating'] = $this->meetings->{$date}->{'users'}->{$name}->schedule->{$userid}->eating;
							$userSchedule[$id]['cooking'] = $this->meetings->{$date}->{'users'}->{$name}->schedule->{$userid}->cooking;
							$userSchedule[$id]['foodhelp'] = @$this->meetings->{$data}->{'users'}->{$name}->schedule->{$userid}->foodhelp;
						} else {
							$userSchedule[$id]['eating'] = false;
							$userSchedule[$id]['cooking'] = false;
							$userSchedule[$id]['foodhelp'] = false;
						}
					}
				}
			}
		}
		$this->meetings->{$date}->{'users'}->{$userid} = array (
			'name'		=> $name,
			'usertype'	=> 'extra',
			'schedule'	=> $userSchedule,
			'comment'	=> $this->stripHtml($comment),
			'modified'	=> ($ignoreConstraints
				?$this->meetings->{$date}->{'users'}->{$userid}->modified
				:time())
		);
		$this->calculateSpend($date);
		$this->writeData ( 'meetings' );
		return true;
	}
	
	function removeNonUserFromDate ( $date, $ownerid, $name,
		$ignoreConstraints=false ) {
		if ( empty ( $this->meetings->{$date} ) )
			return false;
		if ( empty ( $ownerid ) || empty ( $name ) )
			return false;
		if ( @$this->meetings->{$date}->locked
			&& !$ignoreConstraints )
			return false;
		$userid = $ownerid.'-'.$this->makeSubId($name);
		unset($this->meetings->{$date}->users->{$userid});
		$this->calculateSpend($date);
		$this->writeData ( 'meetings' );
		return true;
	}
	
	function removeUserFromDate ( $date, $userid, $ignoreConstraints=false ) {
		if ( empty ( $this->meetings->{$date} ) )
			return false;
		if ( empty ( $userid ) )
			return false;
		if ( @$this->meetings->{$date}->locked
			&& !$ignoreConstraints )
			return false;
		unset($this->meetings->{$date}->users->{$userid});
		$this->calculateSpend($date);
		$this->writeData ( 'meetings' );
		return true;
	}
	
	private function calculateSpend ( $date ) {
		foreach ( $this->meetings->{$date}->schedule as $id => $item ) {
			if ( is_array($item) ) {
				if ( $item['type'] == 'eat' ) {
					$i = 0;
					foreach ( $this->meetings->{$date}->users as $user )
						if ( (is_object($user) && $user->schedule->{$id}->eating )
							|| (is_array($user) && (
							(is_object($user['schedule']) && $user['schedule']->{$id}->eating )
							|| (is_array($user['schedule']) && $user['schedule'][$id]['eating'] ) ) ) )
						$i++;
					if ( $i === 0 ) $i = 1;
					$this->meetings->{$date}->schedule[$id]['costperperson'] = round(floatval($this->meetings->{$date}->schedule[$id]['spend'])/floatval($i), 2);
				}
			}
			if ( is_object($item) ) {
				if ( $item->type == 'eat' ) {
					$i = 0;
					foreach ( $this->meetings->{$date}->users as $user )
						if ( (is_object($user) && $user->schedule->{$id}->eating )
							|| (is_array($user) && (
							(is_object($user['schedule']) && $user['schedule']->{$id}->eating )
							|| (is_array($user['schedule']) && $user['schedule'][$id]['eating'] ) ) ) )
						$i++;
					if ( $i === 0 ) $i = 1;
					$this->meetings->{$date}->schedule->{$id}->costperperson = round(floatval($this->meetings->{$date}->schedule->{$id}->spend)/floatval($i), 2);
				}
			}
		}
	}
	
	function closeForEating ( $date, $id, $user, $spend=false ) {
		if ( empty ( $this->meetings->{$date} ) )
			return false;
		$this->meetings->{$date}->schedule->{$id}->closedby = $user;
		$this->meetings->{$date}->schedule->{$id}->open = false;
		if ( is_numeric($spend) )
			$this->meetings->{$date}->schedule->{$id}->spend = $spend;
		$this->calculateSpend($date);
		$this->writeData ( 'meetings' );
		return true;
	}
	
	function openForEating ( $date, $id ) {
		if ( empty ( $this->meetings->{$date} ) )
			return false;
		$this->meetings->{$date}->schedule->{$id}->open = true;
		$this->writeData ( 'meetings' );
		return true;
	}
}

$database = new Database();

class SQLDatabase {
	private $sql = array(
			'host' => 'localhost',
			'name' => '',
			'user' => 'root',
			'pass' => '',
	);
	
	private $con_id 	= '';
	private $query_array = array();
	private $id_array = array();
	private $query_amount = 0;
	
	function SQLDatabase($dbhost, $dbname, $dbuser, $dbpass) {
		$this->sql['host'] = $dbhost;
		$this->sql['name'] = $dbname;
		$this->sql['user'] = $dbuser;
		$this->sql['pass'] = $dbpass;
		
		$this->connect();
	}

	/**
	 * This function connects to the host and then the database.
	 * 
	 * @return TRUE upon succes, FALSE upon failure
	 */
	function connect() {
		$this->con_id = @mysql_connect($this->sql['host'], $this->sql['user'], $this->sql['pass']);
		if ( !mysql_select_db($this->sql['name'], $this->con_id) )
		{
			return false;
		}
		return true;
	}
	
	/**
	 * This function gets a function and returns it.  If $store is given, it inserts the
	 * resource in the query_array at the position of $store.  If $store isn't an int, it
	 * will then be stored in the standard query_id.  $store cannot be 0 or negative.
	 * 
	 * @param $sql string The query
	 * @param $store int[optional] Where to store the resource
	 * @return The query resource on succes, FALSE upon failure
	 */
	function query($sql, $store = 0) {
		//echo $sql."<br /><br /><br />\n";
		if((is_numeric($store)) && ($store>0)) {
				$this->query_array[$store] = "\0";
				if($this->query_array[$store] = mysql_query($sql, $this->con_id)) {
					$this->id_array[$store] = mysql_insert_id($this->con_id);
					$this->query_amount++;
					return $this->query_array[$store];
				}
				$this->id_array[$store] = false;
				return false;
		}
		$this->query_array[0] = "\0";
		if($this->query_array[0] = mysql_query($sql, $this->con_id)) {
			$this->id_array[0] = mysql_insert_id($this->con_id);
			$this->query_amount++;
			return $this->query_array[0];
		}
		$this->id_array[0] = false;
		return false;
	}
	
	/**
	 * This function returns the current query_id's row's information.  If $store is
	 * set, the resource at that point's row information will be returned.  If $store
	 * is not an int or is below 1, it will return the usual query_id.
	 * 
	 * @param $store int[optional] Where to get the result from
	 * @return The current row, FALSE upon failure
	 */
	function get_result($store = 0) {
		if((is_numeric($store)) && ($store>0)) {
				if($this->query_array[$store]) {
					return mysql_fetch_assoc($this->query_array[$store]);
				}
				return false;
		}
		if($this->query_array[0]) {
			return mysql_fetch_assoc($this->query_array[0]);
		}
		return false;
	}
	
	/**
	 * This function gets the amount of rows in the current resource.
	 * 
	 * @param $store int[optional] What resource to get the information from
	 * @return int, 0 upon no resource or 0 rows
	 */
	function get_num_rows($store = 0) {
		if((is_numeric($store)) && ($store>0)) {
			return mysql_num_rows($this->query_array[$store]);
		}
		return @mysql_num_rows($this->query_array[0]);
	}
	
	/**
	 * This function returns the id of the last INSERT INTO query.
	 * 
	 * @param $store int[optional] Resource to optain id
	 * @return int the id of the last insert query, FALSE upon failure
	 */
	function get_insert_id($store = 0) {
		return $this->id_array[$store];
	}
	
	function get_query_amount() {
		return $this->query_amount;
	}
}

$DB = new SQLDatabase ( $dbhost, $dbname, $dbuser, $dbpass );

$DB->query("SET NAMES UTF8");
