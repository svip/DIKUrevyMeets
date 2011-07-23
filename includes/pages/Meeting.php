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
	
	private function sortSchedule ( $schedule ) {
		$tmp = array();
		foreach ( $schedule as $i => $item ) {
			$tmp[intval(str_replace(':', '', $item->start))] = $item;
			$tmp[intval(str_replace(':', '', $item->start))]->id = $i;
		}
		ksort($tmp);
		return $tmp;
	}
	
	private function makePage ( $date, $meeting ) {
		$content = '<h1>'.$meeting->{'title'}.'</h1><h2>'.$this->weekDay($date, true).' den '.$this->readableDate($date).'</h2>';
		$content .= '<p>';
		$schedule = $this->sortSchedule($meeting->schedule);
		$content .= '<b>Program:</b> ';
		$i = 0;
		foreach ( $schedule as $item ) {
			if ( $i > 0 )
				$content .= ', ';
			$content .= $item->title . ': <b>'.$item->start.'</b>';
			$i++;
		}
		$content .= '.</p>';
		$currentInfo = null;
		$meets = 0;
		$eats = 0;
		$table = '<table>
		<tr>
		<th rowspan="2">Bruger</th>';
		foreach ( $schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$table .= '<th>'.$item->title.'</th>';
				$meets++;
			} elseif ( $item->type == 'eat' ) {
				$table .= '<th colspan="2">'.$item->title.'</th>';
				$eats++;
			}
		}
		$table .= '<th rowspan="2">Kommentar</th></tr>
		<tr>';
		foreach ( $schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$table .= '<th>Kommer</th>';
			} elseif ( $item->type == 'eat' ) {
				$table .= '<th>Spiser med</th><th>Laver mad</th>';
			}
		}
		$table .= '</tr>';
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
			$table .= '<tr><td>'.$user->{'name'}.'</td>';
			$attending = 0;
			$eating = 0;
			$cooking = 0;
			foreach ( $schedule as $item ) {
				if ( $item->type == 'meet' ) {
					if ( $user->schedule->{$item->id}->attending ) $attending++;
					$table .= '<td>'.$this->tick($user->schedule->{$item->id}->attending).'</td>';
				} elseif ( $item->type == 'eat' ) {
					if ( $user->schedule->{$item->id}->eating ) $eating++;
					if ( $user->schedule->{$item->id}->cooking ) $cooking++;
					$table .= '<td>'.$this->tick($user->schedule->{$item->id}->eating).'</td>';
					$table .= '<td>'.$this->tick($user->schedule->{$item->id}->cooking).'</td>';
				}
			}
			$table .= '<td>'.$user->comment.'</td></tr>';
			$stats['users']++;
			$stats['attending'] += $attending/$meets;
			$stats['eating'] += $eating/$eats;
			$stats['cooking'] += $cooking/$eats;
		}
		$table .= '</table>';
		$content .= $table;
		$content .= '<p>'.$stats['users'].' person(er)'.($eats==0?' og ':', ').$stats['attending'].' deltager'.($eats>0?', '.$stats['eating'].' spiser med og '.$stats['cooking'].' kok(ke)':'').'.</p>';
		if ( $this->auth->loggedIn() ) {
			if ( isset ( $_POST['meeting-submit'] ) ) {
				$this->handleMeetingSubmit($date, $schedule);
			}
			foreach ( $schedule as $item ) {
				if ( isset ( $_POST['closeeating-'.$item->id.'-submit'] )
					&& $meeting->users->{$this->auth->userinfo->name}->schedule->{$item->id}->cooking ) {
					$this->database->closeForEating($date, $item->id);
					header ( 'Location: ./?meeting='.$date );					
				}
			}
			$content .= $this->meetingForm($meeting, $currentInfo);
		} else {
			$content .= $this->logInFunction();
		}
		$this->content = $content;
	}
	
	private function handleMeetingSubmit ( $date, $schedule ) {
		$userSchedule = array();
		foreach ( $schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$userSchedule[$item->id] = array (
					'attending' => isset($_POST['meeting-'.$item->id.'-attending'])
				);
			} elseif ( $item->type == 'eat' ) {
				$userSchedule[$item->id] = array (
					'eating' => isset($_POST['meeting-'.$item->id.'-eating']),
					'cooking' => isset($_POST['meeting-'.$item->id.'-cooking'])
				);
			}
		}
		$comment = $_POST['meeting-comment'];
		$this->database->addUserToDate ( $date, $this->auth->userinfo->{'name'},
			$userSchedule, $comment );
		header ( 'Location: ./?meeting='.$date );
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
		$userSchedule = array();
		foreach ( $meeting->schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$userSchedule[$item->id] = array ( 'attending' => true );
				if ( $currentInfo != null ) {
					$userSchedule[$item->id]['attending'] = $currentInfo->schedule->{$item->id}->attending;
				}
			} elseif ( $item->type == 'eat' ) {
				$userSchedule[$item->id] = array ( 'eating' => true, 'cooking' => false );
				if ( $currentInfo != null ) {
					$userSchedule[$item->id]['eating'] = $currentInfo->schedule->{$item->id}->eating;
					$userSchedule[$item->id]['cooking'] = $currentInfo->schedule->{$item->id}->cooking;
				}			
			}
		}
		$comment = '';
		if ( $currentInfo != null ) {
			$comment = $this->safeString($currentInfo->comment);
		}
		$form = '<form method="post">';
		foreach ( $this->sortSchedule($meeting->schedule) as $item ) {
			if ( $item->type == 'eat' 
				&& $userSchedule[$item->id]['cooking']
				&& $item->open ) {
				$form .= '
<fieldset>
<legend>Luk for flere spisetilmeldinger til <b>'.$item->title.'</b></legend>
<input type="submit" name="closeeating-'.$item->id.'-submit" value="Luk nu" />
</fieldset>';
			}
		}
		$form .= '
<fieldset>
<legend>'.($currentInfo!=null?'Ændre <b>'.$this->auth->userinfo->{'name'}.'</b>s tilmelding':'Tilmeld <b>'.$this->auth->userinfo->{'name'}.'</b> møde').'</legend>';

		foreach ( $this->sortSchedule($meeting->schedule) as $item ) {
			if ( $item->type == 'meet' ) {
				$form .= '
<input type="checkbox" name="meeting-'.$item->id.'-attending" id="meeting-'.$item->id.'-attending" '.($userSchedule[$item->id]['attending']?'checked="true"':'').' />
<label for="meeting-'.$item->id.'-attending"><b>Kommer</b> til <b>'.$item->title.'</b></label><br />';
			} elseif ( $item->type == 'eat' ) {
				$form .= '
<input type="checkbox" name="meeting-'.$item->id.'-eating" id="meeting-'.$item->id.'-eating" '.($userSchedule[$item->id]['eating']?'checked="true"':'').' '.(!$item->open?'disabled="true"':'').' />
<label for="meeting-'.$item->id.'-eating"><b>Spiser med</b> til <b>'.$item->title.'</b></label>';
				$form .= '
<input type="checkbox" name="meeting-'.$item->id.'-cooking" id="meeting-'.$item->id.'-cooking" '.($userSchedule[$item->id]['cooking']?'checked="true"':'').' '.(!$item->open?'disabled="true"':'').' />
<label for="meeting-'.$item->id.'-cooking"><b>Laver mad</b> til <b>'.$item->title.'</b></label>';
				$form .= (!$item->open?' <span>(Kokkene har lukket for madtilmeldingen)</span>':'');
				$form .= '<br />';
			}
		}
		$form .= '<br />
<label for="meeting-comment">Eventuelle kommentarer:</label>
<input type="text" name="meeting-comment" id="meeting-comment" value="'.$comment.'" />
<input type="submit" name="meeting-submit" value="'.($currentInfo!=null?'Ændr':'Tilmeld').'" />
</fieldset>
</form>';
		return $form;
	}
}

$page = new Meeting($database, $auth);
