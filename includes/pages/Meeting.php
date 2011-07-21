<?php

class Meeting extends Page {

	protected function render() {
		$m = $_GET['meeting'];
		if ( empty ( $this->database->getMeetings()->{$m} ) ) {
			$this->content = '<p>Intet møde på '.$this->weekDay($m).' den '.$this->readableDate($m).'</p>';
			$this->content .= '<p><a href="./">Til forsiden</a>.</p>';
			return;
		}
		$meeting = $this->database->getMeetings()->{$m};
		$this->makePage ( $m, $meeting );
	}
	
	private function makePage ( $date, $meeting ) {
		$content = '<h1>'.$meeting->{'title'}.'</h1><h2>'.$this->weekDay($date, true).' den '.$this->readableDate($date).'</h2>';
		$eats = array();
		$meets = array();
		foreach ( $meeting->schedule as $i => $item ) {
			if ( $item->type == 'eat' ) {
				$eats[] = $item;
			}
			if ( $item->type == 'meet' ) {
				$meets[] = $item;
			}
		}
		if ( count ( $meets ) == 1 ) {
			$content .= '<p>Møde: <b>'.$meets[0]->start.'</b>';
		}
		if ( count ( $eats ) == 1 ) {
			$content .= ', spisetid: <b>'.$eats[0]->start.'</b>';
		}
		$content .= '.</p>';
		$currentInfo = null;
		$table = '<table>
		<tr><th>Bruger</th><th>Kommer</th>'.(count($eats)==1?'<th>Spiser med</th><th>Laver mad</th>':'').'<th>Kommentar</th></tr>';
		$stats = array (
			'users'		=> 0,
			'attending'	=> 0,
			'eating'	=> 0,
			'cooking'	=> 0,
		);
		$tmp = array();
		foreach ( $meeting->users as $name => $user )
			$tmp[$name] = $user;
		ksort($tmp);
		$users = $tmp;
		foreach ( $users as $user ) {
			if ( $this->auth->loggedIn()
				&& $user->{'name'} == $this->auth->userinfo->{'name'} )
				$currentInfo = $user;
			$table .= '<tr><td>'.$user->{'name'}.'</td><td>'.$this->tick($user->attending).'</td>'.(count($eats)==1?'<td>'.$this->tick($user->eating).'</td><td>'.$this->tick($user->cooking).'</td>':'').'<td>'.$user->comment.'</td></tr>';
			$stats['users']++;
			if ( $user->attending ) $stats['attending']++;
			if ( $user->eating ) $stats['eating']++;
			if ( $user->cooking ) $stats['cooking']++;
		}
		$table .= '</table>';
		$content .= $table;
		$content .= '<p>'.$stats['users'].' person(er)'.(count($eats)==0?' og ':', ').$stats['attending'].' deltager'.(count($eats)==1?', '.$stats['eating'].' spiser med og '.$stats['cooking'].' kok(ke)':'').'.</p>';
		if ( $this->auth->loggedIn() ) {
			if ( isset ( $_POST['meeting-submit'] ) ) {
				$attending = (isset($_POST['meeting-attending'])?true:false);
				$comment = $_POST['meeting-comment'];
				$eating = (isset($_POST['meeting-eating'])?true:false);
				$cooking = (isset($_POST['meeting-cooking'])?true:false);
				$this->database->addUserToDate ( $date, $this->auth->userinfo->{'name'}, $attending, $eating, $cooking, $comment );
				header ( 'Location: ./?meeting='.$date );
			}
			if ( isset ( $_POST['closeeating-submit'] )
				&& $meeting->users->{$this->auth->userinfo->name}->cooking ) {
				$this->database->closeForEating($date);
				header ( 'Location: ./?meeting='.$date );
			}
			$content .= $this->meetingForm($meeting, $currentInfo);
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
	
	private function meetingForm ( $meeting, $currentInfo ) {
		$hasEating = false;
		$eating = false;
		$open = false;
		foreach ( $meeting->schedule as $item ) {
			if ( $item->type == 'eat' ) {
				$hasEating = true;
				$eating = $item->open;
				$open = $item->open;
			}
		}
		$attending = true;
		$cooking = false;
		$comment = '';
		if ( $currentInfo != null ) {
			$attending = $currentInfo->attending;
			$eating = $currentInfo->eating;
			$cooking = $currentInfo->cooking;
			$comment = $this->safeString($currentInfo->comment);
		}
		return '<form method="post">
'.( ($cooking && $meeting->{'eatingopen'} )?'
<fieldset>
<legend>Luk for flere spisetilmeldinger</legend>
<input type="submit" name="closeeating-submit" value="Luk nu" />
</fieldset>' :'').'
<fieldset>
<legend>'.($currentInfo!=null?'Ændre <b>'.$this->auth->userinfo->{'name'}.'</b>s tilmelding':'Tilmeld <b>'.$this->auth->userinfo->{'name'}.'</b> møde').'</legend>
<input type="checkbox" name="meeting-attending" id="meeting-attending" '.($attending?'checked="true"':'').' />
<label for="meeting-attending">Kommer</label>
'.($hasEating?'
<input type="checkbox" name="meeting-eating" id="meeting-eating" '.($eating?'checked="true"':'').' '.(!$open?'disabled="true"':'').' />
<label for="meeting-eating">Spiser med</label>
<input type="checkbox" name="meeting-cooking" id="meeting-cooking" '.($cooking?'checked="true"':'').' '.(!$open?'disabled="true"':'').' />
<label for="meeting-cooking">Laver mad</label>':'').'
'.(!$open?'<span>(Kokkene har lukket for madtilmeldingen)</span>':'').'
<br />
<label for="meeting-comment">Eventuelle kommentarer:</label>
<input type="text" name="meeting-comment" id="meeting-comment" value="'.$comment.'" />
<input type="submit" name="meeting-submit" value="'.($currentInfo!=null?'Ændre':'Tilmeld').'" />
</fieldset>
</form>';
	}
}

$page = new Meeting($database, $auth);
