<?php

class Admin extends Page {

	protected function render ( ) {
		if ( !$this->auth->isAdmin() ) {
			$this->content = '<p>Du skal lige som være administrator her, eh?</p>';
			return;
		}
		$this->additionalStyles[] = 'jsdatepick.css';
		$this->additionalScript[] = 'jsdatepick.js';
		$this->additionalScript[] = 'admin.js';
		switch ( $_GET['admin'] ) {
			case 'user':
				$this->userPage();
				break;
			case 'meeting':
				$this->meetingPage();
				break;
			case 'rawmeeting':
				$this->rawMeetingPage();
				break;
			case 'userlist':
				$this->userListPage();
				break;
			case 'deletemeeting':
				$this->deleteMeetingPage();
				break;
			case 'front':
			default:
				$this->frontPage();
				break;
		}
	}
	
	private function userPage ( ) {
		$userid = $_GET['user'];
		$user = $this->database->getUserById($userid);
		if ( empty($user) ) {
			header('Location: ./?admin=front');
			return;
		}
		if ( isset($_POST['user-submit']) ) {
			$username = $_POST['user-name'];
			$admin = isset($_POST['user-admin'])?true:false;
			$this->database->updateUser($userid, array(
				'admin'		=> $admin,
				'username'	=> $username)
			);
			header('Location: ./?admin=user&user='.$userid);
			return;
		}
		$form = '<form method="post">
		<fieldset>
		<legend>Ændre <b>'.$user->name.'</b>s data</legend>
		<label for="user-name">Nyt brugernavn (blank for at lade brugernavnet være; brugernavnet vil <em>kun</em> blive ændret på denne side og ikke andre sider, det kan være praktisk hvis folk ikke kender ens normale brugernavn):</label>
		<input type="text" name="user-name" id="user-name" />
		<input type="checkbox" name="user-admin" id="user-admin" '.($user->admin?'checked="true"':'').' />
		<label for="user-admin">Administrator?</label><br />
		<input type="submit" name="user-submit" value="Opdatér" />
		</fieldset>
		</form>';
		$this->content = '<p><a href="./?admin=userlist">Tilbage</a></p>'.$form;
	}
	
	private function userListPage ( ) {
		$users = $this->database->getUsers();
		$content = '<p><a href="./?admin=front">Tilbage</a></p>';
		$content .= '<ul>';
		foreach ( $users as $id => $user ) {
			$content .= '<li><a href="./?admin=user&amp;user='.$id.'">'.$user->name.'</a></li>';
		}
		$content .= '</ul>';
		$this->content = $content;
	}
	
	private function frontPage ( ) {
		if ( isset ( $_POST['newmeeting-submit'] ) ) {
			$title = $_POST['newmeeting-title'];
			$comment = $_POST['newmeeting-comment'];
			$date = $_POST['newmeeting-date'];
			$i = 0;
			$schedule = array();
			while ( true ) {
				if ( !isset($_POST['newmeeting-'.$i.'-type']) )
					break;
				if ( !isset($_POST['newmeeting-'.$i.'-ignore']) ) {
					$type = $_POST['newmeeting-'.$i.'-type'];
					if ( $type == 'meet' ) {
						$schedule[] = array (
							'title'		=> $_POST['newmeeting-'.$i.'-title'],
							'type'		=> 'meet',
							'start'		=> $_POST['newmeeting-'.$i.'-start'],
							'end'		=> $_POST['newmeeting-'.$i.'-end'],
							'unique'	=> isset($_POST['newmeeting-'.$i.'-unique']),
						);
					} elseif ( $type == 'eat' ) {
						$spend = isset($_POST['newmeeting-'.$i.'-spend'])
							?floatval($_POST['newmeeting-'.$i.'-spend'])
							:0.0;
						$schedule[] = array (
							'title'		=> $_POST['newmeeting-'.$i.'-title'],
							'type'		=> 'eat',
							'start'		=> $_POST['newmeeting-'.$i.'-start'],
							'end'		=> $_POST['newmeeting-'.$i.'-end'],
							'open'		=> true,
							'spend'		=> $spend,
							'costperperson'	=> $spend,
							'unique'	=> isset($_POST['newmeeting-'.$i.'-unique']),
						);
					}
				}
				$i++;
			}
			$this->database->insertMeeting($date, $title, $schedule, 
				$comment);
			header ( 'Location: ./?admin=front' );
		}
		$list = "<ul>\n";
		foreach ( $this->database->getSortedMeetings() as $date => $meeting ) {
			$list .= '<li><a href="./?admin=meeting&amp;date='.$date.'">'.$date.': '.$meeting->{'title'}.'</a> (<a href="./?admin=deletemeeting&amp;date='.$date."\">Slet</a>)</li>\n";
		}
		$list .= "</ul>\n";
		$form = '<form method="post">
<fieldset>
<legend>Nyt program</legend>
<label for="newmeeting-title">Overskrift:</label>
<input type="text" id="newmeeting-title" name="newmeeting-title" />
<label for="newmeeting-comment">Eventuel kommentar:</label>
<input type="text" id="newmeeting-comment" name="newmeeting-comment" />
<label for="newmeeting-date">Dato (format: <tt>ÅÅÅÅ-MM-DD</tt>):</label>
<input type="text" id="newmeeting-date" name="newmeeting-date" />
<div id="schedule">
<fieldset id="newmeeting-0">
<legend>Møde</legend>
<label for="newmeeting-0-title">Titel:</label>
<input type="text" id="newmeeting-0-title" name="newmeeting-0-title" value="Møde" />
<label for="newmeeting-0-start">Mødetid:</label>
<span class="time"><input type="text" id="newmeeting-0-start" name="newmeeting-0-start" value="19:00" /><span> - </span><input type="text" id="newmeeting-0-end" name="newmeeting-0-end" value="23:00" /></span>
<input type="checkbox" name="newmeeting-0-unique" id="newmeeting-0-unique" />
<label for="newmeeting-0-unique">Vis separat på ical?</label>
<input type="hidden" name="newmeeting-0-type" value="meet" />
</fieldset>
<fieldset id="newmeeting-1">
<legend>Måltid</legend>
<input type="checkbox" id="newmeeting-1-ignore" name="newmeeting-1-ignore" /><label for="newmeeting-1-ignore">Ignorér</label><br />
<label for="newmeeting-1-title">Titel:</label>
<input type="text" id="newmeeting-1-title" name="newmeeting-1-title" value="Aftensmad" />
<label for="newmeeting-1-start">Spisetid:</label>
<span class="time"><input type="text" id="newmeeting-1-start" name="newmeeting-1-start" value="18:00" /><span> - </span><input type="text" id="newmeeting-1-end" name="newmeeting-1-end" value="19:00" /></span>
<label for="newmeeting-1-spend">Indkøbspris (i hele kroner):</label>
<input type="text" id="newmeeting-1-spend" name="newmeeting-1-spend" />
<input type="checkbox" name="newmeeting-1-unique" id="newmeeting-1-unique" />
<label for="newmeeting-1-unique">Vis separat på ical?</label>
<input type="hidden" name="newmeeting-1-type" value="eat" />
</fieldset>
</div>
<a onclick="addMeet();" href="javascript://">Endnu et møde</a> &middot; <a onclick="addEat();" href="javascript://">Endnu et måltid</a><br />
<input type="submit" name="newmeeting-submit" value="Nyt møde!" />
</fieldset>
</form>';
		$menu = '<p><a href="./">Forside</a> &middot; <a href="./?admin=userlist">Brugerliste</a></p>';
		$this->content = $menu.$list.$form;
	}
	
	private function meetingPage ( ) {
		$date = $_GET['date'];
		$meeting = $this->database->getMeeting($date);
		if ( empty ($meeting) ) {
			header( 'Location: ./?admin=front' );
		}
		if ( isset($_POST['meeting-submit']) ) {
			$date = $_POST['meeting-date'];
			$title = $_POST['meeting-title'];
			$meetComment = $_POST['meeting-comment'];
			
			$i = 0;
			$newSchedule = array();
			while ( true ) {
				if ( !isset($_POST['newmeeting-'.$i.'-type']) )
					break;
				if ( !isset($_POST['newmeeting-'.$i.'-ignore']) ) {
					$type = $_POST['newmeeting-'.$i.'-type'];
					if ( $type == 'meet' ) {
						$newSchedule[] = array (
							'title'		=> $_POST['newmeeting-'.$i.'-title'],
							'type'		=> 'meet',
							'start'		=> $_POST['newmeeting-'.$i.'-start'],
							'end'		=> $_POST['newmeeting-'.$i.'-end'],
							'unique'	=> isset($_POST['newmeeting-'.$i.'-unique']),
						);
					} elseif ( $type == 'eat' ) {
						$spend = isset($_POST['newmeeting-'.$i.'-spend'])
							?floatval($_POST['newmeeting-'.$i.'-spend'])
							:0.0;
						$newSchedule[] = array (
							'title'		=> $_POST['newmeeting-'.$i.'-title'],
							'type'		=> 'eat',
							'start'		=> $_POST['newmeeting-'.$i.'-start'],
							'end'		=> $_POST['newmeeting-'.$i.'-end'],
							'open'		=> true,
							'spend'		=> $spend,
							'costperperson'	=> $spend,
							'unique'	=> isset($_POST['newmeeting-'.$i.'-unique']),
						);
					}
				}
				$i++;
			}
			print_r($newSchedule);
			$users = explode(',', $_POST['meeting-users']);
			foreach ( $users as $userid ) {
				if ( empty($userid) ) continue;
				$comment = $_POST['meeting-'.$userid.'-comment'];
				$userSchedule = $meeting->users->{$userid}->schedule;
				foreach ( $meeting->schedule as $id => $item ) {
					if ( $item->type == 'meet' ) {
						$userSchedule->{$id}->attending = isset($_POST['meeting-'.$userid.'-'.$id.'-attending']);
					} elseif ( $item->type == 'eat' ) {
						$userSchedule->{$id}->eating = isset($_POST['meeting-'.$userid.'-'.$id.'-eating']);
						$userSchedule->{$id}->cooking = isset($_POST['meeting-'.$userid.'-'.$id.'-cooking']);
						$userSchedule->{$id}->paid = isset($_POST['meeting-'.$userid.'-'.$id.'-paid'])?$item->costperperson:0.0;
					}
				}
				$this->database->addUserToDate($date, $userid, $userSchedule, $comment, true, true);
			}
			$this->database->updateMeeting($date, $title, $meetComment, $newSchedule);
			header('Location: ./?admin=meeting&date='.$date);
			#return;
		}
		$schedule = $this->sortSchedule($meeting->schedule);
		foreach ( $meeting->schedule as $id => $item ) {
			if ( isset ( $_POST['meeting-'.$id.'-open'] ) ) {
				$this->database->openForEating($date, $id);
				header('Location: ./?admin=meeting&date='.$date);
			}
			if ( isset ( $_POST['meeting-'.$id.'-close'] ) ) {
				$this->database->closeForEating($date, $id);
				header('Location: ./?admin=meeting&date='.$date);
			}
		}
		$form = '<form method="post">
<fieldset>
<legend>Information</legend>
<label for="meeting-date">Dato:</label>
<input type="text" name="meeting-date" id="meeting-date" value="'.$date.'" />
<label for="meeting-title">Overskrift:</label>
<input type="text" name="meeting-title" id="meeting-title" value="'.$meeting->title.'" />
<label for="meeting-comment">Kommentar:</label>
<input type="text" name="meeting-comment" id="meeting-comment" value="'.$meeting->comment.'" />
<div id="schedule">';
		foreach ( $meeting->schedule as $id => $item ) {
			if ( $item->type == 'meet' ) {
				$form .= '<fieldset id="newmeeting-'.$id.'">
<legend>Møde</legend>
<input type="checkbox" id="newmeeting-'.$id.'-ignore" name="newmeeting-'.$id.'-ignore" /><label for="newmeeting-'.$id.'-ignore">Slet</label><br />
<label for="newmeeting-'.$id.'">Titel:</label>
<input type="text" id="newmeeting-'.$id.'-title" name="newmeeting-'.$id.'-title" value="'.$item->title.'" />
<label for="newmeeting-'.$id.'-start">Mødetid:</label>
<span class="time"><input type="text" id="newmeeting-'.$id.'-start" name="newmeeting-'.$id.'-start" value="'.$item->start.'" /><span> - </span><input type="text" id="newmeeting-'.$id.'-end" name="newmeeting-'.$id.'-end" value="'.$item->end.'" /></span>
<input type="checkbox" name="newmeeting-'.$id.'-unique" id="newmeeting-'.$id.'-unique"'.($item->unique?' checked="true"':'').' />
<label for="newmeeting-'.$id.'-unique">Vis separat på ical?</label>
<input type="hidden" name="newmeeting-'.$id.'-type" value="meet" />
</fieldset>
';
			} elseif ( $item->type == 'eat' ) {
				$form .= '<fieldset id="newmeeting-'.$id.'">
<legend>Måltid</legend>
<input type="checkbox" id="newmeeting-'.$id.'-ignore" name="newmeeting-'.$id.'-ignore" /><label for="newmeeting-'.$id.'-ignore">Slet</label><br />
<label for="newmeeting-'.$id.'-title">Titel:</label>
<input type="text" id="newmeeting-'.$id.'-title" name="newmeeting-'.$id.'-title" value="'.$item->title.'" />
<label for="newmeeting-'.$id.'-start">Spisetid:</label>
<span class="time"><input type="text" id="newmeeting-1-start" name="newmeeting-1-start" value="'.$item->start.'" /><span> - </span><input type="text" id="newmeeting-'.$id.'-end" name="newmeeting-'.$id.'-end" value="'.$item->end.'" /></span>
<label for="newmeeting-'.$id.'-spend">Indkøbspris (i hele kroner):</label>
<input type="text" id="newmeeting-'.$id.'-spend" name="newmeeting-'.$id.'-spend" value="'.$item->spend.'" />
<input type="checkbox" name="newmeeting-'.$id.'-unique" id="newmeeting-'.$id.'-unique"'.($item->unique?' checked="true"':'').' />
<label for="newmeeting-'.$id.'-unique">Vis separat på ical?</label>
<input type="hidden" name="newmeeting-'.$id.'-type" value="eat" />
</fieldset>
';
			}
		}
		$form .= '</div>
<a onclick="addMeet();" href="javascript://">Endnu et møde</a> &middot; <a onclick="addEat();" href="javascript://">Endnu et måltid</a><br />
<input type="submit" name="meeting-submit" value="Ændr" />
</fieldset>';
		$form .= '<table>
		<tr><th rowspan="2">Bruger</th>';
		foreach ( $schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$form .= '<th>'.$item->title.'</th>';
			} elseif ( $item->type == 'eat' ) {
				$form .= '<th colspan="3">'.$item->title.'<br />'.($item->open?'<input type="submit" name="meeting-'.$item->id.'-close" value="Luk" />':'<input type="submit" name="meeting-'.$item->id.'-open" value="Åben" />').'</th>';
			}
		}
		$form .= '<th rowspan="2">Kommentar</th></tr><tr>';
		foreach ( $schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$form .= '<th>Kommer</th>';
			} elseif ( $item->type == 'eat' ) {
				$form .= '<th>Spiser med</th><th>Laver mad</th><th>Betalt?</th>';
			}
		}
		$form .= '</tr>';
		$userids = '';
		foreach ( $meeting->users as $userid => $user ) {
			if ( $userids != '' )
				$userids .= ',';
			$userids .= $userid;
			$form .= '<tr>
			<td><a href="?admin=user&amp;user='.$userid.'">'.$this->database->getUserById($userid)->name.'</a></td>';
			foreach ( $schedule as $item ) {
				$id = $item->id;
				$useritem = $user->schedule->{$id};
				if ( $item->type == 'meet' ) {
					$form .= '<td class="centre '.($useritem->attending?'yes':'no').'"><input type="checkbox" name="meeting-'.$userid.'-'.$id.'-attending" '.($useritem->attending?'checked="true"':'').' /></td>';
				} elseif ( $item->type == 'eat' ) {
					$form .= '<td class="centre '.($useritem->eating?'yes':'no').'"><input type="checkbox" name="meeting-'.$userid.'-'.$id.'-eating" '.($useritem->eating?'checked="true"':'').' /></td>';
					$form .= '<td class="centre '.($useritem->cooking?'yes':'no').'"><input type="checkbox" name="meeting-'.$userid.'-'.$id.'-cooking" '.($useritem->cooking?'checked="true"':'').' /></td>';
					$form .= '<td class="centre '.($useritem->paid?'yes':($useritem->eating?'no':'nomatter')).'"><input type="checkbox" name="meeting-'.$userid.'-'.$id.'-paid" '.($useritem->paid?'checked="true"':'').' /></td>';
				}
			}
			$form .= '<td><input type="text" name="meeting-'.$userid.'-comment" value="'.$user->comment.'" /></td>';
			$form .= '</tr>';
		}
		$form .= '</table>';
		$form .= '<input type="hidden" name="meeting-users" value="'.$userids.'" />';
		$form .= '<input type="submit" name="meeting-submit" value="Ændr" />';
		$form .= '<br /><a href="./?admin=rawmeeting&amp;date='.$date.'">Rådata</a>';
		$form .= '</form>';
		$this->content = '<p><a href="?admin=front">Tilbage</a></p>'.$form;
	}
	
	private function deleteMeetingPage ( ) {
		$date = $_GET['date'];
		$meeting = $this->database->getMeeting($date);
		if ( isset($_POST['deletemeeting-yes']) ) {
			$this->database->deleteMeeting($date);
		}
		if ( isset($_POST['deletemeeting-yes'])
			|| isset($_POST['deletemeeting-no']) ) {
			header('Location: ./?admin=front');
		}
		$this->content = '<form method="post">
<fieldset>
<legend>Slet '.$meeting->title.'?</legend>
<input type="submit" name="deletemeeting-yes" value="Ja" />
<input type="submit" name="deletemeeting-no" value="Nej" />
</fieldset>
</form>';
	}
	
	private function rawMeetingPage ( ) {
		$date = $_GET['date'];
		$meeting = $this->database->getMeeting($date);
		if ( empty ($meeting) ) {
			header( 'Location: ./?admin=front' );
		}
		$this->content = '<a href="./?admin=meeting&amp;date='.$date.'">Tilbage til møde</a>';
		$this->content .= '<h2>Rådata for '.$date.'</h2>';
		$this->content .= '<pre>'.print_r($meeting, true).'</pre>';
	}
}

$page = new Admin($database, $auth);
