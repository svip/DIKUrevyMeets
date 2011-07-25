<?php

class Admin extends Page {

	protected function render ( ) {
		if ( !$this->auth->isAdmin() ) {
			$this->content = '<p>Du skal lige som være administrator her, eh?</p>';
			return;
		}
		$this->additionalScript[] = 'admin.js';
		switch ( $_GET['admin'] ) {
			case 'meeting':
				$this->meetingPage();
				break;		
			case 'front':
			default:
				$this->frontPage();
				break;
		}
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
			$list .= '<li><a href="./?admin=meeting&amp;date='.$date.'">'.$date.': '.$meeting->{'title'}."</a></li>\n";
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
<input type="hidden" name="newmeeting-0-type" value="meet" />
</fieldset>
<fieldset id="newmeeting-1">
<legend>Spisning</legend>
<input type="checkbox" id="newmeeting-1-ignore" name="newmeeting-1-ignore" /><label for="newmeeting-1-ignore">Ignorér</label><br />
<label for="newmeeting-1-title">Titel:</label>
<input type="text" id="newmeeting-1-title" name="newmeeting-1-title" value="Aftensmad" />
<label for="newmeeting-1-start">Spisetid:</label>
<span class="time"><input type="text" id="newmeeting-1-start" name="newmeeting-1-start" value="18:00" /><span> - </span><input type="text" id="newmeeting-1-end" name="newmeeting-1-end" value="19:00" /></span>
<label for="newmeeting-1-spend">Indkøbspris (i hele kroner):</label>
<input type="text" id="newmeeting-1-spend" name="newmeeting-1-spend" />
<input type="hidden" name="newmeeting-1-type" value="eat" />
</fieldset>
</div>
<a onclick="addMeet();" href="javascript://">Endnu et møde</a> &middot; <a onclick="addEat();" href="javascript://">Endnu en spisning</a><br />
<input type="submit" name="newmeeting-submit" value="Nyt møde!" />
</fieldset>
</form>';
		$this->content = $list.$form;
	}
	
	private function meetingPage ( ) {
		$date = $_GET['date'];
		$meeting = $this->database->getMeeting($date);
		$schedule = $this->sortSchedule($meeting->schedule);
		if ( empty ($meeting) ) {
			header( 'Location: ./?admin=front' );
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
<input type="submit" name="meeting-submit" value="Ændr" />
</fieldset>';
		$form .= '<table>
		<tr><th rowspan="2">Bruger</th>';
		foreach ( $schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$form .= '<th>'.$item->title.'</th>';
			} elseif ( $item->type == 'eat' ) {
				$form .= '<th colspan="3">'.$item->title.'<br />'.($item->open?'<input type="submit" name="meeting-'.$item->id.'"-close" value="Luk" />':'<input type="submit" name="meeting-'.$item->id.'"-open" value="Åben" />').'</th>';
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
		foreach ( $meeting->users as $user ) {
			$form .= '<tr>
			<td><a href="?admin=user&amp;user='.$user->name.'">'.$user->name.'</a></td>';
			foreach ( $schedule as $item ) {
				$id = $item->id;
				$useritem = $user->schedule->{$id};
				if ( $item->type == 'meet' ) {
					$form .= '<td class="centre '.($useritem->attending?'yes':'no').'"><input type="checkbox" name="meeting-'.$user->name.'-'.$id.'-attending" '.($useritem->attending?'checked="true"':'').' /></td>';
				} elseif ( $item->type == 'eat' ) {
					$form .= '<td class="centre '.($useritem->eating?'yes':'no').'"><input type="checkbox" name="meeting-'.$user->name.'-'.$id.'-eating" '.($useritem->eating?'checked="true"':'').' /></td>';
					$form .= '<td class="centre '.($useritem->cooking?'yes':'no').'"><input type="checkbox" name="meeting-'.$user->name.'-'.$id.'-cooking" '.($useritem->cooking?'checked="true"':'').' /></td>';
					$form .= '<td class="centre '.($useritem->paid?'yes':'no').'"><input type="checkbox" name="meeting-'.$user->name.'-'.$id.'-paid" '.($useritem->paid?'checked="true"':'').' /></td>';
				}
			}
			$form .= '<td><input type="text" name="meeting-'.$user->name.'-comment" value="'.$user->comment.'" /></td>';
			$form .= '</tr>';
		}
		$form .= '</table>';
		$form .= '<input type="submit" name="meeting-submit" value="Ændr" />';
		$form .= '</form>';
		$this->content = $form;
	}
}

$page = new Admin($database, $auth);
