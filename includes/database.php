<?php

class Database {

	private $users = null;
	private $meetings = null;
	
	public function __construct ( ) {
		$this->users = json_decode ( file_get_contents( 'data/users.json' ) );
		$this->meetings = json_decode ( file_get_contents( 'data/meetings.json' ) );
	}
	
	private function writeData ( $file ) {
		switch ( $file ) {
			case 'meetings':
				$hl = fopen ( "data/$file.json", 'w' );
				fwrite ( $hl, json_encode ( $this->meetings, JSON_FORCE_OBJECT ) );
				fclose ( $hl );
				break;
			case 'users':
				$hl = fopen ( "data/$file.json", 'w' );
				fwrite ( $hl, json_encode ( $this->users ) );
				fclose ( $hl );
				break;
			default: break;
		}
	}
	
	function getMeeting ( $date ) {
		if ( !isset ( $this->meetings->{$date} ) )
			return null;
		return $this->meetings->{$date};
	}
	
	function getUsers ( ) {
		return $this->users;
	}
	
	function getMeetings ( ) {
		return $this->meetings;
	}
	
	function getSortedMeetings ( ) {
		$tmp = array();
		foreach ( $this->meetings as $date => $meeting )
			$tmp[$date] = $meeting;
		ksort($tmp);
		return $tmp;
	}
	
	function insertMeeting ( $date, $title, $schedule=array(array('title'=>'Aftensmad','type'=>'eat','start'=>'18:00','end'=>'19:00','open'=>true,
	'spend'=>0.0,'costperperson'=>0.0),array('title'=>'Møde','type'=>'meet','start'=>'19:00','end'=>'23:00')), 
			$comment='' ) {
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
			'comment'		=> $comment,
			'users'			=> array()
		);
		$this->writeData ( 'meetings' );
		return $this->meetings;
	}
	
	function insertUser ( $user, $service, $identity, $signature ) {
		if ( !empty ( $this->users->{$user} ) )
			return false;
		$this->users->{$user} = array (
			'name'		=> $user,
			'register'	=> time(),
			'admin'		=> false,
			'service'	=> $service,
			'identity'	=> $identity,
			'signature'	=> $signature
		);
		$this->writeData ( 'users' );
		return true;
	}
	
	function addUserToDate ( $date, $name, $userSchedule, $comment ) {
		if ( empty ( $this->meetings->{$date} ) )
			return false;
		foreach ( $this->meetings->{$date}->schedule as $id => $item ) {
			if ( $item->type == 'eat'
				&& !$item->open ) {
				if ( !empty($this->meetings->{$date}->{'users'}->{$name}) ) {
					$userSchedule[$id]['eating'] = $this->meetings->{$date}->{'users'}->{$name}->schedule->{$id}->eating;
					$userSchedule[$id]['cooking'] = $this->meetings->{$date}->{'users'}->{$name}->schedule->{$id}->cooking;
				} else {
					$userSchedule[$id]['eating'] = false;
					$userSchedule[$id]['cooking'] = false;
				}
			}
		}
		$this->meetings->{$date}->{'users'}->{$name} = array (
			'name'		=> $name,
			'schedule'	=> $userSchedule,
			'comment'	=> $comment,
			'paid'		=> 0.0,
			'modified'	=> time()
		);
		$i = 0;
		foreach ( $this->meetings->{$date}->users as $user )
			if ( (is_object($user) && $user->eating)
				|| ( is_array($user) && $user['eating'] ) )
				$i++;
		$this->meetings->{$date}->schedule->{0}->{'costperperson'} = $this->meetings->{$date}->schedule->{0}->{'spend'}/$i;
		$this->writeData ( 'meetings' );
		return true;
	}
	
	function closeForEating ( $date, $id ) {
		if ( empty ( $this->meetings->{$date} ) )
			return false;
		$this->meetings->{$date}->schedule->{$id}->open = false;
		$this->writeData ( 'meetings' );
		return true;
	}
}

$database = new Database();
