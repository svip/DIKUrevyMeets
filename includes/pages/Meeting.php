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
	
	function userSort ( $a, $b ) {
		return strncasecmp($a->name, $b->name, 4);
	}
	
	private function makePage ( $date, $meeting ) {
		$content = '<h1>'.$meeting->{'title'}.'</h1><h3>'.nl2br($meeting->{'comment'}).'</h3><h2>'.$this->weekDay($date, true).' den '.$this->readableDate($date).'</h2>';
		$schedule = $this->sortSchedule($meeting->schedule);
		$meets = 0;
		$eats = 0;
		$table = '<table>
		<tr>
		<th rowspan="2">Program</th>';
		foreach ( $schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$table .= '<th>'.$item->title.'</th>';
				$meets++;
			} elseif ( $item->type == 'eat' ) {
				$table .= '<th colspan="2">'.$item->title.(!$item->open?' (lukket)':'').'</th>';
				$eats++;
			}
		}
		$table .= '<th rowspan="3" class="comment">Kommentar</th></tr>
		<tr>';
		foreach ( $schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$table .= '<th>'.$item->start.'</th>';
			} elseif ( $item->type == 'eat' ) {
				$table .= '<th colspan="2">'.$item->start.'</th>';
			}
		}
		$table .= '</tr><tr>';
		$table .= '<th>Bruger</th>';
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
			'schedule'	=> array()
		);
		foreach ( $schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$stats['schedule'][$item->id] = array ( 'attending' => 0 );
			} elseif ( $item->type == 'eat' ) {
				$stats['schedule'][$item->id] = array ( 'eating' => 0, 'cooking' => 0 );
			}
		}
		$tmp = array();
		foreach ( $meeting->users as $userid => $user ) {
			$tmp[$userid] = $user;
			$dbuser = $this->database->getUserById($userid);
			if ( is_object($dbuser) )
				$tmp[$userid]->name = $dbuser->name;
			else
				$tmp[$userid]->name = $user->name;
		}
		uasort($tmp, array(__CLASS__, 'userSort'));
		$users = $tmp;
		$currentInfo = array ( 0 => null );
		foreach ( $users as $userid => $user ) {
			if ( $this->auth->loggedIn()
				&& $userid == $this->auth->userinfo->{'identity'} )
				$currentInfo[0] = $user;
			if ( $this->auth->loggedIn()
				&& strpos($userid, '-')!==false ) {
				$split = explode('-', $userid);
				if (intval($split[0]) == $userid)
					$currentInfo[] = $user;
			}
			$table .= '<tr><td>'.$user->name.'</td>';
			foreach ( $schedule as $item ) {
				if ( $item->type == 'meet' ) {
					if (!isset($user->schedule->{$item->id})
						|| ($item->nojoin) )
						$table .= '<td class="centre">?</td>';					
					else {
						if ( $user->schedule->{$item->id}->attending ) $stats['schedule'][$item->id]['attending']++;
						$table .= '<td class="centre '.($user->schedule->{$item->id}->attending?'yes':'no').'">'.$this->tick($user->schedule->{$item->id}->attending).'</td>';
					}
				} elseif ( $item->type == 'eat' ) {
					if (!isset($user->schedule->{$item->id})
						|| ($item->nojoin) )
						$table .= '<td class="centre">?</td>';
					else {
						if ( $user->schedule->{$item->id}->eating ) $stats['schedule'][$item->id]['eating']++;
						if ( $user->schedule->{$item->id}->cooking ) $stats['schedule'][$item->id]['cooking']++;
						$table .= '<td class="centre '.($user->schedule->{$item->id}->eating?'yes':'no').'">'.$this->tick($user->schedule->{$item->id}->eating).'</td>';
						$table .= '<td class="centre '.($user->schedule->{$item->id}->cooking?'yes':'no').'">'.$this->tick($user->schedule->{$item->id}->cooking).'</td>';
					}
				}
			}
			$table .= '<td class="comment">'.$user->comment.'</td></tr>';
			$stats['users']++;
		}
		$table .= '<tr>
		<th>Bruger</th>';
		foreach ( $schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$table .= '<th>Kommer</th>';
			} elseif ( $item->type == 'eat' ) {
				$table .= '<th>Spiser med</th><th>Laver mad</th>';
			}
		}
		$table .= '<th>Kommentar</th></tr>';
		$table .= '<tr class="total">
		<td>'.$stats['users'].'</td>';
		foreach ( $stats['schedule'] as $id => $item ) {
			if ( $meeting->schedule->{$id}->type == 'meet' ) {
				$table .= '<td>'.$item['attending'].'</td>';
			} elseif ( $meeting->schedule->{$id}->type == 'eat' ) {
				$table .= '<td>'.$item['eating'].'</td><td>'.$item['cooking'].'</td>';
			}
		}
		$table .= '<td>&nbsp;</td></tr>';
		$table .= '</table>';
		$content .= $table;
		if ( $this->auth->loggedIn() ) {
			if ( isset ( $_POST['meeting-submit'] ) ) {
				$this->handleMeetingSubmit($date, $schedule);
			}
			if ( isset ( $_POST['meeting-remove'] ) ) {
				$this->handleMeetingRemove($date);
			}
			foreach ( $schedule as $item ) {
				if ( isset ( $_POST['closeeating-'.$item->id.'-submit'] )
					&& $meeting->users->{$this->database->getUserId($this->auth->userinfo->name)}->schedule->{$item->id}->cooking ) {
					$this->database->closeForEating($date, $item->id, $_POST['closeeating-'.$item->id.'-spend']);
					header ( 'Location: ./?meeting='.$date );					
				}
			}
			$content .= $this->meetingForm($meeting, $currentInfo);
		} else {
			$content .= $this->logInFunction();
		}
		$this->content = '<p><a href="./">Tilbage</a></p>'.$content;
	}
	
	private function handleMeetingRemove ( $date ) {
		$name = $_POST['meeting-name'];
		$this->database->removeNonUserFromDate( $date, $this->auth->userinfo->{'identity'}, $name );
		header ( 'Location: ./?meeting='.$date );		
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
					'cooking' => isset($_POST['meeting-'.$item->id.'-cooking']),
					'paid' => 0.0
				);
			}
		}
		$comment = $_POST['meeting-comment'];
		if ( $_POST['meeting-usertype'] == 'extra' ) {
			$name = $_POST['meeting-name'];
			$this->database->addNonUserToDate ( $date, $this->auth->userinfo->{'identity'}, $name, $userSchedule, $comment );
		} else {
			$this->database->addUserToDate ( $date, $this->auth->userinfo->{'name'},
				$userSchedule, $comment );
		}
		header ( 'Location: ./?meeting='.$date );
	}
	
	private function tick ( $value ) {
		if ( $value ) return 'X';
		return '-';
	}
	
	private function meetingForm ( $meeting, $currentInfo ) {
		$userSchedule = array();
		$canJoin = false;
		$form = '';
		foreach ( $currentInfo as $subuserid => $a ) {
			foreach ( $meeting->schedule as $item ) {
				if ( $item->nojoin ) {
					continue;
				} elseif ( $item->type == 'meet' ) {
					$userSchedule[$subuserid][$item->id] = array ( 'attending' => true );
					if ( $currentInfo != null ) {
						$userSchedule[$subuserid][$item->id]['attending'] = $currentInfo[$subuserid]->schedule->{$item->id}->attending;
					}
					$canJoin = true;
				} elseif ( $item->type == 'eat' ) {
					if ( $item->open )
						$userSchedule[$subuserid][$item->id] = array ( 'eating' => true, 'cooking' => false );
					else
						$userSchedule[$subuserid][$item->id] = array ( 'eating' => false, 'cooking' => false );
					if ( $currentInfo != null ) {
						$userSchedule[$subuserid][$item->id]['eating'] = $currentInfo[$subuserid]->schedule->{$item->id}->eating;
						$userSchedule[$subuserid][$item->id]['cooking'] = $currentInfo[$subuserid]->schedule->{$item->id}->cooking;
					}		
					$canJoin = true;	
				}
			}
			$userSchedule[$subuserid]['comment'] = $this->safeString($currentInfo[$subuserid]->comment);
		}
		if ( !$canJoin ) return '';
		foreach ( $this->sortSchedule($meeting->schedule) as $item ) {
			if ( $item->nojoin ) {
				continue;
			} elseif ( $item->type == 'eat' 
				&& $userSchedule[0][$item->id]['cooking']
				&& $item->open ) {
				$form .= '<form method="post">
<fieldset>
<legend>Luk for flere spisetilmeldinger til <b>'.$item->title.'</b></legend>
<label for="closeeasting-'.$item->id.'-spend">Indkøbt for (i danske kroner):</label>
<input type="text" name="closeeating-'.$item->id.'-spend" id="closeeating-'.$item->id.'-spend" value="'.$item->spend.'" />
<input type="submit" name="closeeating-'.$item->id.'-submit" value="Luk nu" />
</fieldset>
</form>';
			}
		}
		foreach ( $currentInfo as $subuserid => $user ) {
			$form .= '<form method="post">
	<fieldset>
	<legend>'.($user!=null?'Ændre <b>'.$user->{'name'}.'</b>s tilmelding':'Tilmeld <b>'.$this->auth->userinfo->{'name'}.'</b> møde').'</legend>';
			if ( $subuserid === 0 )
				$form .= '
<input type="hidden" name="meeting-usertype" value="self" />';
			else
				$form .= '
<input type="hidden" name="meeting-usertype" value="extra" />
<input type="hidden" name="meeting-name" value="'.$user->name.'" />';

			foreach ( $this->sortSchedule($meeting->schedule) as $item ) {
				if ( $item->nojoin ) {
					continue;
				} else if ( $item->type == 'meet' ) {
					$form .= '<span style="width: 150px; display: block; float: left;"><b>'.$item->title.'</b>:</span> ';
					$form .= '
	<input type="checkbox" name="meeting-'.$item->id.'-attending" id="meeting-'.$item->id.'-'.$subuserid.'-attending" '.($userSchedule[$subuserid][$item->id]['attending']?'checked="true"':'').' />
	<label for="meeting-'.$item->id.'-'.$subuserid.'-attending">Kommer</label>';
					$form .= '<br />';
				} elseif ( $item->type == 'eat' ) {
					$form .= '<span style="width: 150px; display: block; float: left;"><b>'.$item->title.'</b>:</span> ';
					$form .= '
	<input type="checkbox" name="meeting-'.$item->id.'-eating" id="meeting-'.$item->id.'-'.$subuserid.'-eating" '.($userSchedule[$subuserid][$item->id]['eating']?'checked="true"':'').' '.(!$item->open?'disabled="true"':'').' />
	<label for="meeting-'.$item->id.'-'.$subuserid.'-eating">Spiser med</label>';
					$form .= '
	<input type="checkbox" name="meeting-'.$item->id.'-cooking" id="meeting-'.$item->id.'-'.$subuserid.'-cooking" '.($userSchedule[$subuserid][$item->id]['cooking']?'checked="true"':'').' '.(!$item->open?'disabled="true"':'').' />
	<label for="meeting-'.$item->id.'-'.$subuserid.'-cooking">Laver mad</label>';
					$form .= (!$item->open?' <span>(Kokkene har lukket for madtilmeldingen)</span>':'');
					$form .= '<br />';
				}
			}
			$form .= '<br />
<label for="meeting-'.$subuserid.'-comment">Eventuelle kommentarer:</label>
<input type="text" name="meeting-comment" id="meeting-'.$subuserid.'-comment" value="'.$userSchedule[$subuserid]['comment'].'" />
<input type="submit" name="meeting-submit" value="'.($currentInfo[$subuserid]!=null?'Ændr':'Tilmeld').'" />';
			if ( $subuserid !== 0 )
				$form .= '<input type="submit" name="meeting-remove" value="Fjern" />';
			$form .= '
</fieldset>
</form>';
		}
		$form .= '<form method="post">
<fieldset>
<legend>Tilmeld en <b>ekstra person</b></legend>
<p>I stedet for at tilmelde mere end én person per linje, så tilmeld derimod en ekstra person (du kan altid ændre deres tilmelding senere, da deres tilmelding vil blive bundet til din konto).</p>
<input type="hidden" name="meeting-usertype" value="extra" />
<label for="meeting-name">Navn på person:</label>
<input type="text" name="meeting-name" id="meeting-name" class="distanceitself" />
';
		foreach ( $this->sortSchedule($meeting->schedule) as $item ) {
			if ( $item->nojoin ) {
				continue;
			} else if ( $item->type == 'meet' ) {
				$form .= '<span style="width: 150px; display: block; float: left;"><b>'.$item->title.'</b>:</span> ';
				$form .= '
<input type="checkbox" name="meeting-'.$item->id.'-attending" id="meeting-'.$item->id.'-attending" checked="true" />
<label for="meeting-'.$item->id.'-attending">Kommer</label>';
				$form .= '<br />';
			} elseif ( $item->type == 'eat' ) {
				$form .= '<span style="width: 150px; display: block; float: left;"><b>'.$item->title.'</b>:</span> ';
				$form .= '
<input type="checkbox" name="meeting-'.$item->id.'-eating" id="meeting-'.$item->id.'-eating" '.(!$item->open?'disabled="true"':'checked="true"').' />
<label for="meeting-'.$item->id.'-eating">Spiser med</label>';
				$form .= '
<input type="checkbox" name="meeting-'.$item->id.'-cooking" id="meeting-'.$item->id.'-cooking" '.(!$item->open?'disabled="true"':'').' />
<label for="meeting-'.$item->id.'-cooking">Laver mad</label>';
				$form .= (!$item->open?' <span>(Kokkene har lukket for madtilmeldingen)</span>':'');
				$form .= '<br />';
			}
		}
		$form .= '<br />
<label for="meeting-comment">Eventuelle kommentarer:</label>
<input type="text" name="meeting-comment" id="meeting-comment" />
<input type="submit" name="meeting-submit" value="Tilmeld" />
</fieldset>
</form>';
		return $form;
	}
}

$page = new Meeting($database, $auth);
