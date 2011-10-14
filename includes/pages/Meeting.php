<?php

class Meeting extends Page {

	protected function render() {
		$m = $_GET['meeting'];
		if ( empty ( $this->database->getMeetings()->{$m} ) ) {
			$this->content = '<p>Intet møde på '.$this->weekDay($m).' den '.$this->readableDate($m).'</p>';
			$this->content .= '<p><a href="./">Til forsiden</a>.</p>';
			return;
		}
		$itemid = isset($_GET['subid'])?$_GET['subid']:null;
		$meeting = $this->database->getMeetings()->{$m};
		$this->makePage ( $m, $meeting, $itemid );
	}
	
	function userSort ( $a, $b ) {
		return strncasecmp($a->name, $b->name, 4);
	}
	
	private function seeAlso ( $date, $meeting, $itemid ) {
		$seeAlso = array();
		if ( is_null($itemid) ) {
			$schedule = $this->sortSchedule($meeting->schedule);
			foreach ( $schedule as $key => $item ) {
				if ( $item->unique )
					$seeAlso[] = $item;
			}
		} else {
			foreach ( $meeting->schedule as $id => $item ) {
				if ($id != $itemid) {
					if ($item->unique)
						$seeAlso[] = $item;
					elseif ( !in_array($meeting, $seeAlso) )
						$seeAlso[-1] = $meeting;
				}
			}
		}
		if (count($seeAlso) == 0)
			return '';
		ksort($seeAlso);
		$content = '<p>Se også for denne dato: ';
		$i = 0;
		foreach ($seeAlso as $id => $item) {
			if ( $i != 0 )
				$content .= ' &middot; ';
			if ( $id === -1 )
				$content .= '<a href="./?meeting='.$date.'">'.$item->title.'</a>';
			else
				$content .= '<a href="./?meeting='.$date.'&amp;subid='.$item->id.'">'.$item->title.'</a>';
			$i++;
		}
		$content .= '</p>';
		return $content;
	}
	
	private function onlyUniques ( $date ) {
		$meeting = $this->database->getMeeting($date);
		foreach ( $meeting->schedule as $item )
			if ( !$item->unique )
				return false;
		return true;
	}
	
	private function navigation ( $date ) {
		$nav = '';
		$prevMeeting = $this->database->getMeetingBefore($date);
		if ( $prevMeeting!==false )
			$nav .= '<a href="./?meeting='.$prevMeeting['date'].($this->onlyUniques($prevMeeting['date'])?'&amp;subid=0':'').'">&lt; <b>'.$this->readableDate($prevMeeting['date']).'</b>: '.$prevMeeting['title'].'</a>';
		$nextMeeting = $this->database->getMeetingAfter($date);
		if ( $nextMeeting!==false ) {
			if ( $prevMeeting!==false )
				$nav .= ' &middot; ';
			$nav .= '<a href="./?meeting='.$nextMeeting['date'].($this->onlyUniques($nextMeeting['date'])?'&amp;subid=0':'').'"><b>'.$this->readableDate($nextMeeting['date']).'</b>: '.$nextMeeting['title'].' &gt;</a>';
		}
		return $nav;
	}
	
	private function isJoinableEvent ( $schedule ) {
		foreach ( $schedule as $item ) 
			if ( !$item->nojoin )
				return true;
		return false;
	}
	
	private function makePage ( $date, $meeting, $itemid ) {
		$nav = $this->navigation($date);
		$content = '<p><a href="./">Tilbage</a></p>';
		$content .= "<p>$nav</p>\n";
		if ( is_null($itemid) ) {
			$schedule = $this->sortSchedule($meeting->schedule);
			$tmp = array();
			foreach ( $schedule as $key => $item ) {
				if ( !$item->unique )
					$tmp[$key] = $item;
			}
			$schedule = $tmp;
		} else {
			$schedule = array();
			foreach ( $this->sortSchedule($meeting->schedule) as $item ) {
				if ($item->id != $itemid)
					continue;
				$schedule[$item->start . $item->end] = $item;
				break;
			}
		}
		$content .= '<h1>'.$meeting->{'title'}.(!is_null($itemid)?': '.$item->title:'').'</h1><h3>'.nl2br($meeting->{'comment'}).'</h3>';
		if ( is_numeric(@$meeting->days) )
			$content .= '<h2>Fra '.$this->weekDay($date).' den '.$this->readableDate($date).' til '.$this->weekDay($this->getEndDate($date, $meeting->days)).' den '.$this->readableDate($this->getEndDate($date, $meeting->days)).'</h2>';
		else
			$content .= '<h2>'.$this->weekDay($date, true).' den '.$this->readableDate($date).'</h2>';
		$content .= $this->seeAlso($date, $meeting, $itemid);
		if ( !$this->isJoinableEvent($schedule) ) {
			$content .= '<h3>Program:</h3>';
			foreach ( $schedule as $item ) {
				$content .= '<h4>'.$item->title.': '.$item->start.'-'.$item->end.'</h4>';
			}
			$this->content = $content;
			return;
		}
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
			if ( $user->name == 'N/A' )
				// this is a bug, do not display it for general use
				// admins can see it, however.
				continue;
			if ( $this->auth->loggedIn()
				&& $userid == $this->auth->userinfo->{'identity'} )
				$currentInfo[0] = $user;
			if ( $this->auth->loggedIn()
				&& strpos($userid, '-')!==false ) {
				$split = explode('-', $userid);
				if (intval($split[0]) == $this->auth->userinfo->{'identity'})
					$currentInfo[] = $user;
			}
			$table .= '<tr><td>'.$user->name.'</td>';
			foreach ( $schedule as $item ) {
				$id = $item->id;
				if ( $item->type == 'meet' ) {
					if (!isset($user->schedule->{$id})
						|| ($item->nojoin) )
						$table .= '<td class="centre">?</td>';					
					else {
						if ( $user->schedule->{$id}->attending ) $stats['schedule'][$id]['attending']++;
						$table .= '<td class="centre '.($user->schedule->{$id}->attending?'yes':'no').'">'.$this->tick($user->schedule->{$id}->attending).'</td>';
					}
				} elseif ( $item->type == 'eat' ) {
					if (!isset($user->schedule->{$id})
						|| ($item->nojoin) )
						$table .= '<td class="centre">?</td>';
					else {
						if ( $user->schedule->{$id}->eating ) $stats['schedule'][$id]['eating']++;
						if ( $user->schedule->{$id}->cooking ) $stats['schedule'][$id]['cooking']++;
						$table .= '<td class="centre '.($user->schedule->{$id}->eating?'yes':'no').'">'.$this->tick($user->schedule->{$id}->eating).'</td>';
						$table .= '<td class="centre '.($user->schedule->{$id}->cooking?'yes':'no').'">'.$this->tick($user->schedule->{$id}->cooking).'</td>';
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
				$this->handleMeetingSubmit($date, $meeting, $itemid);
			}
			if ( isset ( $_POST['meeting-remove'] ) ) {
				$this->handleMeetingRemove($date, $itemid);
			}
			foreach ( $schedule as $id => $item ) {
				$id = (isset($item->id)?$item->id:$id);
				if ( isset ( $_POST['closeeating-'.$id.'-submit'] )
					&& $meeting->users->{$this->database->getUserId($this->auth->userinfo->name)}->schedule->{$id}->cooking ) {
					$this->database->closeForEating($date, $id, $_POST['closeeating-'.$item->id.'-spend']);
					if ( is_null($itemid) )
						header ( 'Location: ./?meeting='.$date );
					else
						header ( 'Location: ./?meeting='.$date.'&subid='.$itemid );				
				}
			}
			$content .= $this->meetingForm($meeting, $currentInfo, $itemid);
		} else {
			$content .= $this->logInFunction();
		}
		$this->content = $content;
	}
	
	private function handleMeetingRemove ( $date, $itemid ) {
		$name = $_POST['meeting-name'];
		$this->database->removeNonUserFromDate( $date, $this->auth->userinfo->{'identity'}, $name );
		if ( is_null($itemid) )
			header ( 'Location: ./?meeting='.$date );
		else
			header ( 'Location: ./?meeting='.$date.'&subid='.$itemid );
	}
	
	private function handleMeetingSubmit ( $date, $meeting, $itemid ) {
		$userSchedule = array();
		foreach ( $meeting->schedule as $id => $item ) {
			$id = (isset($item->id)?$item->id:$id);
			if ( $item->type == 'meet' ) {
				$userSchedule[$id] = array (
					'attending' => isset($_POST['meeting-'.$id.'-attending'])
				);
			} elseif ( $item->type == 'eat' ) {
				$userSchedule[$id] = array (
					'eating' => isset($_POST['meeting-'.$id.'-eating']),
					'cooking' => isset($_POST['meeting-'.$id.'-cooking']),
					'paid' => 0.0
				);
			}
		}
		$comment = $this->database->stripHtml($_POST['meeting-comment']);
		if ( $_POST['meeting-usertype'] == 'extra' ) {
			$name = $_POST['meeting-name'];
			$this->database->addNonUserToDate ( $date, $this->auth->userinfo->{'identity'}, $name, $userSchedule, $comment );
		} else {
			$this->database->addUserToDate ( $date, $this->auth->userinfo->{'name'},
				$userSchedule, $comment );
		}
		if ( is_null($itemid) )
			header ( 'Location: ./?meeting='.$date );
		else
			header ( 'Location: ./?meeting='.$date.'&subid='.$itemid );			
	}
	
	private function tick ( $value ) {
		if ( $value ) return 'X';
		return '-';
	}
	
	private function meetingForm ( $meeting, $currentInfo, $itemid ) {
		$userSchedule = array();
		$canJoin = false;
		if ( @$meeting->locked ) {
			return '<p>Tilmeldingen er lukket.</p>';
		}
		$form = '';
		foreach ( $currentInfo as $subuserid => $a ) {
			foreach ( $meeting->schedule as $id => $item ) {
				$id = (isset($item->id)?$item->id:$id);
				if ( $item->nojoin ) {
					continue;
				} elseif ( $item->type == 'meet' ) {
					$userSchedule[$subuserid][$id] = array ( 'attending' => true );
					if ( !is_null($currentInfo[$subuserid]) ) {
						$userSchedule[$subuserid][$id]['attending'] = $currentInfo[$subuserid]->schedule->{$id}->attending;
					}
					$canJoin = true;
				} elseif ( $item->type == 'eat' ) {
					if ( $item->open )
						$userSchedule[$subuserid][$id] = array ( 'eating' => true, 'cooking' => false );
					else
						$userSchedule[$subuserid][$id] = array ( 'eating' => false, 'cooking' => false );
					if ( !is_null($currentInfo[$subuserid]) ) {
						$userSchedule[$subuserid][$id]['eating'] = $currentInfo[$subuserid]->schedule->{$id}->eating;
						$userSchedule[$subuserid][$id]['cooking'] = $currentInfo[$subuserid]->schedule->{$id}->cooking;
					}		
					$canJoin = true;	
				}
			}
			if ( !is_null($currentInfo[$subuserid]) )
				$userSchedule[$subuserid]['comment'] = $this->safeString($currentInfo[$subuserid]->comment);
			else
				$userSchedule[$subuserid]['comment'] = '';
		}
		if ( !$canJoin ) return '';
		foreach ( $this->sortSchedule($meeting->schedule) as $item ) {
			if ( $item->nojoin
				|| (!is_null($itemid) && $item->id != $itemid)
				|| (is_null($itemid) && $item->unique) ) {
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
<input type="hidden" name="meeting-name" value="'.$this->safeString($user->name).'" />';

			foreach ( $this->sortSchedule($meeting->schedule) as $item ) {
				if ( $item->nojoin ) {
					continue;
				} else {
					if ( (!is_null($itemid) && $item->id == $itemid)
						|| (is_null($itemid) && !$item->unique) ) {
						 if ( $item->type == 'meet' ) {
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
					} else {
						if ( $item->type == 'meet' ) {
							if ( $userSchedule[$subuserid][$item->id]['attending'] )
								$form .= '<input type="hidden" name="meeting-'.$item->id.'-attending" value="1" />';
						} elseif ( $item->type == 'eat' ) {	
							if ( $userSchedule[$subuserid][$item->id]['eating'] )
								$form .= '<input type="hidden" name="meeting-'.$item->id.'-eating" value="1" />';
							if ( $userSchedule[$subuserid][$item->id]['cooking'] )
								$form .= '<input type="hidden" name="meeting-'.$item->id.'-cooking" value="1" />';
						}
					}
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
			} else {
				if ( (!is_null($itemid) && $item->id == $itemid)
					|| (is_null($itemid) && !$item->unique) ) {
					if ( $item->type == 'meet' ) {
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
