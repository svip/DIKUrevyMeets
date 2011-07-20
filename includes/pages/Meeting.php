<?php

class Meeting extends Page {

	protected function render() {
		$m = $_GET['meeting'];
		$meeting = $this->database->getMeetings()->{$m};
		if ( empty ( $meeting ) ) {
			$this->content = '<p>Intet møde på '.$this->weekDay($m).' den '.$this->readableDate($m).'</p>';
			$this->content .= '<p><a href="./">Til forsiden</a>.</p>';
			return;
		}
		$this->makePage ( $m, $meeting );
	}
	
	private function makePage ( $date, $meeting ) {
		$content = '<h1>'.$meeting->{'title'}.'</h1><h2>'.$this->weekDay($date, true).' den '.$this->readableDate($date).'</h2>';
		$content .= '<p>Møde: <b>'.$meeting->{'meettime'}.'</b>';
		if ( $meeting->{'haseating'} ) {
			$content .= ', spisetid: <b>'.$meeting->{'eattime'}.'</b>';
		}
		$content .= '.</p>';
		$currentInfo = null;
		$table = '<table>
		<tr><th>Bruger</th><th>Kommer</th>'.($meeting->{'haseating'}?'<th>Spiser med</th><th>Laver mad</th>':'').'<th>Kommentar</th></tr>';
		$stats = array (
			'users'		=> 0,
			'attending'	=> 0,
			'eating'	=> 0,
			'cooking'	=> 0,
		);
		foreach ( $meeting->{'users'} as $user ) {
			if ( $this->auth->loggedIn()
				&& $user->{'name'} == $this->auth->userinfo->{'name'} )
				$currentInfo = $user;
			$table .= '<tr><td>'.$user->{'name'}.'</td><td>'.$this->tick($user->attending).'</td>'.($meeting->haseating?'<td>'.$this->tick($user->eating).'</td><td>'.$this->tick($user->cooking).'</td>':'').'<td>'.$user->comment.'</td></tr>';
			$stats['users']++;
			if ( $user->attending ) $stats['attending']++;
			if ( $user->eating ) $stats['eating']++;
			if ( $user->cooking ) $stats['cooking']++;
		}
		$table .= '</table>';
		$content .= $table;
		$content .= '<p>'.$stats['users'].' person(er), '.$stats['attending'].' deltager'.($meeting->haseating?', '.$stats['eating'].' spiser med og '.$stats['cooking'].' kokke':'').'.</p>';
		if ( $this->auth->loggedIn() ) {
			if ( isset ( $_POST['meeting-submit'] ) ) {
				$attending = (isset($_POST['meeting-attending'])?true:false);
				$comment = $_POST['meeting-comment'];
				$eating = null;
				$cooking = null;
				if ( $meeting->{'haseating'} ) {
					$eating = (isset($_POST['meeting-eating'])?true:false);
					$cooking = (isset($_POST['meeting-cooking'])?true:false);
				}
				$this->database->addUserToDate ( $date, $this->auth->userinfo->{'name'}, $attending, $eating, $cooking, $comment );
				header ( 'Location: ./?meeting='.$date );
			}
			$content .= $this->meetingForm($meeting->{'haseating'}, $currentInfo);
		} else {
			$content .= $this->logInFunction();
		}
		$this->content = $content;
	}
	
	private function tick ( $value ) {
		if ( $value ) return 'X';
		return '-';
	}
	
	protected function safeString ( $str ) {
		return str_replace ( 
			array ( '"', '&' ),
			array ( '&quot;', '&amp;' ),
			$str );
	}
	
	private function meetingForm ( $hasEating, $currentInfo ) {
		$attending = true;
		$eating = true;
		$cooking = false;
		$comment = '';
		if ( $currentInfo != null ) {
			$attending = $currentInfo->attending;
			$eating = $currentInfo->eating;
			$cooking = $currentInfo->cooking;
			$comment = $this->safeString($currentInfo->comment);
		}
		return '<form method="post">
<fieldset>
<legend>Tilmeld <b>'.$this->auth->userinfo->{'name'}.'</b> møde</legend>
<input type="checkbox" name="meeting-attending" id="meeting-attending" '.($attending?'checked="true"':'').' />
<label for="meeting-attending">Kommer</label>
'.($hasEating?'
<input type="checkbox" name="meeting-eating" id="meeting-eating" '.($eating?'checked="true"':'').' />
<label for="meeting-eating">Spiser med</label>
<input type="checkbox" name="meeting-cooking" id="meeting-cooking" '.($cooking?'checked="true"':'').' />
<label for="meeting-cooking">Laver mad</label>':'').'
<br />
<label for="meeting-comment">Eventuelle kommentarer:</label>
<input type="text" name="meeting-comment" id="meeting-comment" value="'.$comment.'" />
<input type="submit" name="meeting-submit" value="Tilmeld" />
</fieldset>
</form>';
	}
}

$page = new Meeting($database, $auth);
