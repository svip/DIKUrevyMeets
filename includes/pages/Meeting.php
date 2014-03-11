<?php

class Meeting extends Page {
	
	private $thisdate = '';
	
	protected function render() {
		$m = $_GET['meeting'];
		if ( empty ( $this->database->getMeetings()->{$m} ) ) {
			$this->content = gfRawMsg('<p>$1</p>',
				gfMsg('meeting-nomeetingfor',
					$this->weekDay($m),
					$this->readableDate($m)
				)
			);
			$this->content .= gfRawMsg('<p><a href="./">$1</a>.</p>',
				gfMsg('meeting-tofrontpage')
			);
			return;
		}
		$this->additionalScript[] = 'meeting.js';
		$itemid = isset($_GET['subid'])?$_GET['subid']:null;
		$meeting = $this->database->getMeetings()->{$m};
		$this->makePage ( $m, $meeting, $itemid );
	}
	
	private function userSort ( $a, $b ) {
		$a = (isset($a->nickname)?$a->nickname:$a->name);
		$b = (isset($b->nickname)?$b->nickname:$b->name);
		if ( ord($a[0]) < ord('A') ) $a[0] = 'Å';
		if ( ord($b[0]) < ord('A') ) $b[0] = 'Å';
		$a = preg_replace('@[^a-zA-ZæøåÆØÅ]@i', '', $a);
		$b = preg_replace('@[^a-zA-ZæøåÆØÅ]@i', '', $b);
		if ( preg_match('@.*brainfuck.*@i', $a) ) $a = chr(10);
		if ( preg_match('@.*brainfuck.*@i', $b) ) $b = chr(10);
		return strncasecmp($a, $b, 4);
	}
	
	protected function showTime ( $time ) {
		$split = explode(' ', $time);
		
		if ( $split[0] == '0' && !$this->multidayEvent )
			return $split[1];
		
		$date = $this->getEndDate($this->thisdate, intval($split[0]));
		
		return gfRawMsg('<b>$1</b><br />$2', $this->readableDate($date), 
			$split[1]);
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
		$content = gfRawMsg('<p>$1', gfMsg('meeting-alsoforthisdate'));
		$i = 0;
		foreach ($seeAlso as $id => $item) {
			if ( $i != 0 )
				$content .= ' &middot; ';
			if ( $id === -1 )
				$content .= gfRawMsg('<a href="./?meeting=$1">$2</a>',
					$date, $item->title
				);
			else
				$content .= gfRawMsg('<a href="./?meeting=$1&amp;subid=$3">$2</a>',
					$date, $item->title, $item->id
				);
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
			$nav .= gfRawMsg('<a href="./?meeting=$1$2">&lt; <b>$3</b>: $4</a>',
				$prevMeeting['date'],
				($this->onlyUniques($prevMeeting['date'])?'&amp;subid=0':''),
				$this->readableDate($prevMeeting['date']),
				$prevMeeting['title']
			);
		$nextMeeting = $this->database->getMeetingAfter($date);
		if ( $nextMeeting!==false ) {
			if ( $prevMeeting!==false )
				$nav .= ' &middot; ';
			$nav .= gfRawMsg('<a href="./?meeting=$1$2"><b>$3</b>: $4 &gt;</a>',
				$nextMeeting['date'],
				($this->onlyUniques($nextMeeting['date'])?'&amp;subid=0':''),
				$this->readableDate($nextMeeting['date']),
				$nextMeeting['title']
			);
		}
		return $nav;
	}
	
	private function isJoinableEvent ( $schedule ) {
		foreach ( $schedule as $item ) 
			if ( !$item->nojoin )
				return true;
		return false;
	}
	
	private function topNav ( $date ) {
		$menu = array(
			array ('./', gfMsg('topnav-back'))
		);
		if ( $this->auth->isAdmin() ) {
			$menu[] = array ("./?admin=meeting&amp;date=$date", gfMsg('topnav-managemeeting'));
		}
		
		$nav = '';
		
		foreach ( $menu as $item ) {
			if ( $nav != '' )
				$nav .= ' &middot; ';
			$nav .= gfRawMsg('<a href="$1">$2</a>',
				$item[0], $item[1]
			);
		}
		
		return $nav;
	}
	
	private function makePage ( $date, $meeting, $itemid ) {
		$topNav = $this->topNav($date);
		$nav = $this->navigation($date);
		$content = "<p>$topNav</p>\n";
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
		$content .= gfRawMsg('<h1>$1$2</h1><h3>$3</h3>',
			$meeting->{'title'},
			(!is_null($itemid)?': '.$item->title:''),
			nl2br($meeting->{'comment'})
		);
		if ( is_numeric(@$meeting->days) ) {
			$this->multidayEvent = true;
			$this->thisdate = $date;
			$content .= gfRawMsg('<h2>$1</h2>',
				gfMsg('meeting-multiday-period', 
					$this->weekDay($date),
					$this->readableDate($date),
					$this->weekDay($this->getEndDate($date, $meeting->days)),
					$this->readableDate($this->getEndDate($date, $meeting->days))
				)
			);
		} else
			$content .= '<h2>'.$this->weekDay($date, true).' den '.$this->readableDate($date).'</h2>';
		$content .= $this->seeAlso($date, $meeting, $itemid);
		if ( !$this->isJoinableEvent($schedule) ) {
			$content .= gfRawMsg('<h3>$1:</h3>',
				gfMsg('meeting-schedule')
			);
			foreach ( $schedule as $item ) {
				$content .= gfRawMsg('<h4>$1: $2-$3</h4>',
					$item->title, $this->showTime($item->start),
					$this->showTime($item->end)
				);
			}
			$this->content = $content;
			return;
		}
		$meets = 0;
		$eats = 0;
		$table = gfRawMsg('<table>
<tr>
<th rowspan="2">$1</th>',
			gfMsg('datatable-header-schedule')
		);
		foreach ( $schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$table .= gfRawMsg('<th>$1</th>', $item->title);
				$meets++;
			} elseif ( $item->type == 'eat' ) {
				$table .= gfRawMsg('<th colspan="3">$1$2</th>', 
					$item->title,
					( !$item->open
						? gfMsg('datatable-header-eatingclosed')
						: ''
					)
				);
				$eats++;
			}
		}
		$table .= gfRawMsg('<th rowspan="3" class="comment">$1</th></tr>
<tr>',
			gfMsg('datatable-header-comment')
		);
		foreach ( $schedule as $item ) {
			$extra = '';
			if ( $item->nojoin )
				$extra = ' rowspan="2"';
			if ( $item->type == 'meet' ) {
				$table .= gfRawMsg('<th$2>$1</th>',
					$this->showTime($item->start), $extra);
			} elseif ( $item->type == 'eat' ) {
				$table .= gfRawMsg('<th colspan="3"$2>$1</th>',
					$this->showTime($item->start), $extra);
			}
		}
		$table .= '</tr><tr>';
		$table .= gfRawMsg('<th>$1<br />$2</th>', gfMsg('datatable-header-user'),
			gfRawMsg('<a href="javascript://" onclick="toggleNames();">$1</a>',
				gfMsg('datatable-header-usertoggle')
			)
		);
		foreach ( $schedule as $item ) {
			if ( $item->nojoin )
				continue;
			if ( $item->type == 'meet' ) {
				$table .= gfRawMsg('<th>$1</th>',
					gfMsg('datatable-header-attending')
				);
			} elseif ( $item->type == 'eat' ) {
				$table .= gfRawMsg('<th>$1</th><th>$2</th><th>$3</th>',
					gfMsg('datatable-header-eating'),
					gfMsg('datatable-header-cooking'),
					gfMsg('datatable-header-foodhelp')
				);
			}
		}
		$table .= '</tr>';
		$stats = array (
			'users'    => 0,
			'schedule' => array()
		);
		foreach ( $schedule as $item ) {
			if ( $item->type == 'meet' ) {
				$stats['schedule'][$item->id] = array ( 'attending' => 0 );
			} elseif ( $item->type == 'eat' ) {
				$stats['schedule'][$item->id] = array ( 'eating' => 0, 'cooking' => 0, 'foodhelp' => 0 );
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
			$table .= '<tr><td class="user">'.$this->makeUserName($userid, $user->name).'</td>';
			foreach ( $schedule as $item ) {
				$id = $item->id;
				if ( $item->nojoin ) {
					$table .= '<td class="centre nojoin">-</td>';
				} elseif ( $item->type == 'meet' ) {
					if (!isset($user->schedule->{$id})
						|| ($item->nojoin) )
						$table .= gfRawMsg('<td class="centre">$1</td>',
							gfMsg('tick-unknown')
						);
					else {
						if ( $user->schedule->{$id}->attending ) $stats['schedule'][$id]['attending']++;
						$table .= '<td class="centre '.($user->schedule->{$id}->attending?'yes':'no').'">'.$this->tick($user->schedule->{$id}->attending).'</td>';
					}
				} elseif ( $item->type == 'eat' ) {
					if (!isset($user->schedule->{$id})
						|| ($item->nojoin) ) {
						$table .= gfRawMsg('<td class="centre">$1</td>',
							gfMsg('tick-unknown')
						);
						$table .= gfRawMsg('<td class="centre">$1</td>',
							gfMsg('tick-unknown')
						);
					} else {
						if ( $user->schedule->{$id}->eating ) $stats['schedule'][$id]['eating']++;
						if ( $user->schedule->{$id}->cooking ) $stats['schedule'][$id]['cooking']++;
						if ( @$user->schedule->{$id}->foodhelp ) $stats['schedule'][$id]['foodhelp']++;
						$table .= gfRawMsg('<td class="centre $1">$2</td>',
							($user->schedule->{$id}->eating?'yes':'no'),
							$this->tick($user->schedule->{$id}->eating)
						);
						$table .= gfRawMsg('<td class="centre $1">$2</td>',
							($user->schedule->{$id}->cooking?'yes':'no'),
							$this->tick($user->schedule->{$id}->cooking)
						);
						$table .= gfRawMsg('<td class="centre $1">$2</td>',
							(@$user->schedule->{$id}->foodhelp?'yes':'no'),
							$this->tick(@$user->schedule->{$id}->foodhelp)
						);
					}
				}
			}
			if ( preg_match('/.*postrevy.*/i', $meeting->title) ) {
				if ( !$this->verifyPun($user->comment)
					|| $user->comment == gfMsg('punrequired') ) {
					$user->comment = gfMsg('punrequired');
				} else {
					$user->comment = preg_replace('/(patter|patte|pat)/i', '<span style="color: green; font-weight: bold;">\1</span>', $user->comment);
				}
			}
			$table .= gfRawMsg('<td class="comment">$1</td></tr>',
				$user->comment
			);
			$stats['users']++;
		}
		$table .= gfRawMsg('<tr>
		<th>$1</th>',
			gfMsg('datatable-header-user')
		);
		foreach ( $schedule as $item ) {
			if ( $item->nojoin ) {
				$table .= gfRawMsg('<th>$1</th>', $item->title);
			} elseif ( $item->type == 'meet' ) {
				$table .= gfRawMsg('<th>$1</th>',
					gfMsg('datatable-header-attending')
				);
			} elseif ( $item->type == 'eat' ) {
				$table .= gfRawMsg('<th>$1</th><th>$2</th><th>$3</th>',
					gfMsg('datatable-header-eating'),
					gfMsg('datatable-header-cooking'),
					gfMsg('datatable-header-foodhelp')
				);
			}
		}
		$table .= gfRawMsg('<th>$1</th></tr>',
			gfMsg('datatable-header-comment')
		);
		$table .= '<tr class="total">
		<td>'.$stats['users'].'</td>';
		foreach ( $stats['schedule'] as $id => $item ) {
			if ( $meeting->schedule->{$id}->nojoin ) {
				$table .= gfRawMsg('<td class="nojoin">$1</td>', '-');
			} elseif ( $meeting->schedule->{$id}->type == 'meet' ) {
				$table .= gfRawMsg('<td>$1</td>', $item['attending']);
			} elseif ( $meeting->schedule->{$id}->type == 'eat' ) {
				$table .= gfRawMsg('<td>$1</td><td>$2</td><td>$3</td>',
					$item['eating'], $item['cooking'], $item['foodhelp']);
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
				if ( isset ( $_POST['closeeating-'.$id.'-submit'] ) ) {
					$this->database->closeForEating($date, $id, $this->auth->userinfo->{'identity'}, $_POST['closeeating-'.$item->id.'-spend']);
					if ( is_null($itemid) )
						header ( 'Location: ./?meeting='.$date );
					else
						header ( 'Location: ./?meeting='.$date.'&subid='.$itemid );	
				}
				if ( isset ( $_POST['openeating-'.$id.'-submit'] )
					&& $this->someoneCookingForThis($currentInfo, $item) ) {
					$this->database->openForEating($date, $id);
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
	
	private function verifyPun($comment) {
		if ( !preg_match('/.*pat.*/i', $comment) )
			return false;
		if ( preg_match('/.*patter.*/i', $comment) )
			return strlen($comment) > 10;
		else
			return strlen($comment) > 6;
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
					'foodhelp' => isset($_POST['meeting-'.$id.'-foodhelp']),
					'paid' => 0.0
				);
			}
		}
		
		$comment = $this->database->stripHtml($_POST['meeting-comment']);
		
		if ( preg_match('/.*postrevy.*/i', $meeting->title)
			&& !$this->verifyPun($comment) ) {
			$comment = gfMsg('punrequired');
		}
		
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
		if ( $value ) return gfMsg('tick-yes');
		return gfMsg('tick-no');
	}
	
	private function meetingForm ( $meeting, $currentInfo, $itemid ) {
		$userSchedule = array();
		$canJoin = false;
		if ( @$meeting->locked ) {
			return gfRawMsg('<p>$1</p>', gfMsg('joinform-closed'));
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
						$userSchedule[$subuserid][$id]['attending'] = @$currentInfo[$subuserid]->schedule->{$id}->attending;
					}
					$canJoin = true;
				} elseif ( $item->type == 'eat' ) {
					if ( $item->open )
						$userSchedule[$subuserid][$id] = array (
							'eating' => true,
							'cooking' => false,
							'foodhelp' => false
						);
					else
						$userSchedule[$subuserid][$id] = array (
							'eating' => false,
							'cooking' => false,
							'foodhelp' => false
						);
					if ( !is_null($currentInfo[$subuserid]) ) {
						$userSchedule[$subuserid][$id]['eating'] = @$currentInfo[$subuserid]->schedule->{$id}->eating;
						$userSchedule[$subuserid][$id]['cooking'] = @$currentInfo[$subuserid]->schedule->{$id}->cooking;
						$userSchedule[$subuserid][$id]['foodhelp'] = @$currentInfo[$subuserid]->schedule->{$id}->foodhelp;
					}		
					$canJoin = true;	
				}
			}
			if ( !is_null($currentInfo[$subuserid]) )
				$userSchedule[$subuserid]['comment'] = $this->safeString($this->database->stripHtml($currentInfo[$subuserid]->comment));
			else
				$userSchedule[$subuserid]['comment'] = '';
		}
		
		if ( !$canJoin ) return '';
		
		foreach ( $currentInfo as $subuserid => $user ) {
			$form .= gfRawMsg('<form method="post">
	<fieldset>
	<legend>$1</legend>',
				(is_null($user)
					? gfMsg('joinform-title-join',
						$this->auth->userinfo->{'name'})
					: gfMsg('joinform-title-change',
						$user->{'name'}))
			);
			if ( $subuserid === 0 )
				$form .= '
<input type="hidden" name="meeting-usertype" value="self" />';
			else
				$form .= gfRawMsg('
<input type="hidden" name="meeting-usertype" value="extra" />
<input type="hidden" name="meeting-name" value="$1" />',
					$this->safeString($user->name)
				);
			
			foreach ( $this->sortSchedule($meeting->schedule) as $item ) {
				if ( $item->nojoin ) {
					continue;
				} else {
					if ( (!is_null($itemid) && $item->id == $itemid)
						|| (is_null($itemid) && !$item->unique) ) {
						if ( $item->type == 'meet' ) {
							$form .= gfRawMsg('<span class="scheduleform-item">$1:</span>', $item->title);
							$form .= gfRawMsg('
			<input type="checkbox" name="meeting-$1-attending" id="meeting-$1-$2-attending" $3 />
			<label for="meeting-$1-$2-attending">$4</label>',
								$item->id, $subuserid,
								($userSchedule[$subuserid]
									[$item->id]['attending']
									? 'checked="true"' : ''),
								gfMsg('joinform-attending')
							);
							$form .= '<br />';
						} elseif ( $item->type == 'eat' ) {
							$form .= gfRawMsg('<span class="scheduleform-item">$1:</span>', $item->title);
							$form .= gfRawMsg('
			<input type="checkbox" name="meeting-$1-eating" id="meeting-$1-$2-eating" $3 $4 />
			<label for="meeting-$1-$2-eating">$5</label>',
								$item->id, $subuserid,
								($userSchedule[$subuserid]
									[$item->id]['eating']
									? 'checked="true"' : ''),
								(!$item->open?'disabled="true"':''),
								gfMsg('joinform-eating')
							);
							$form .= gfRawMsg('
			<input type="checkbox" name="meeting-$1-cooking" id="meeting-$1-$2-cooking" $3 $4 />
			<label for="meeting-$1-$2-cooking">$5</label>',
								$item->id, $subuserid,
								($userSchedule[$subuserid]
									[$item->id]['cooking']
									? 'checked="true"' : ''),
								(!$item->open?'disabled="true"':''),
								gfMsg('joinform-cooking')
							);
							$form .= gfRawMsg('
			<input type="checkbox" name="meeting-$1-foodhelp" id="meeting-$1-$2-foodhelp" $3 $4 />
			<label for="meeting-$1-$2-foodhelp">$5</label>',
								$item->id, $subuserid,
								($userSchedule[$subuserid]
									[$item->id]['foodhelp']
									? 'checked="true"' : ''),
								(!$item->open?'disabled="true"':''),
								gfMsg('joinform-foodhelp')
							);
							$form .= (!$item->open
								? gfRawMsg(' <span>$1</span>',
									gfMsg('joinform-eatingisclosed',
										$this->getClosedBy(@$item->closedby)
									))
								: '' );
							$form .= '<br />';
						}
					} else {
						if ( $item->type == 'meet' ) {
							if ( $userSchedule[$subuserid][$item->id]['attending'] )
								$form .= gfRawMsg('<input type="hidden" name="meeting-$1-attending" value="1" />', $item->id);
						} elseif ( $item->type == 'eat' ) {
							if ( $userSchedule[$subuserid][$item->id]['eating'] )
								$form .= gfRawMsg('<input type="hidden" name="meeting-$1-eating" value="1" />', $item->id);
							if ( $userSchedule[$subuserid][$item->id]['cooking'] )
								
								$form .= gfRawMsg('<input type="hidden" name="meeting-$1-cooking" value="1" />', $item->id);
							if ( $userSchedule[$subuserid][$item->id]['foodhelp'] )
								
								$form .= gfRawMsg('<input type="hidden" name="meeting-$1-foodhelp" value="1" />', $item->id);
						}
					}
				}
			}
			$form .= gfRawMsg('<br />
<label for="meeting-$1-comment">$2:</label>
<input type="text" name="meeting-comment" id="meeting-$1-comment" value="$3" />
<input type="submit" name="meeting-submit" value="$4" />',
				$subuserid,
				gfMsg('joinform-comment'),
				($userSchedule[$subuserid]['comment']==gfMsg('punrequired')
					?''
					:$this->database->stripHtml($userSchedule[$subuserid]['comment'])),
				(is_null($currentInfo[$subuserid])
					? gfMsg('joinform-submit-join')
					: gfMsg('joinform-submit-change'))
			);
			if ( $subuserid !== 0 )
				$form .= gfRawMsg('<input type="submit" name="meeting-remove" value="$1" />', gfMsg('joinform-submit-remove'));
			$form .= '
</fieldset>
</form>';
		}
		
		$subuserid++;
		
		foreach ( $this->sortSchedule($meeting->schedule) as $item ) {
			if ( $item->nojoin
				|| (!is_null($itemid) && $item->id != $itemid)
				|| (is_null($itemid) && $item->unique) ) {
				continue;
			} elseif ( $item->type == 'eat' ) {
				if ( $item->open ) {
					$form .= gfRawMsg('<form method="post">
	<fieldset>
	<legend>$1</legend>
	<label for="closeeating-$2-spend">$3:</label>
	<input type="text" name="closeeating-$2-spend" id="closeeating-$2-spend" value="$4" />
	<input type="submit" name="closeeating-$2-submit" value="$5" />
	</fieldset>
	</form>',
						gfMsg('closeeatingform-title', $item->title),
						$item->id,
						gfMsg('closeeatingform-spend'),
						$item->spend,
						gfMsg('closeeatingform-submit')
					);
				} elseif ( !$item->open
					&& $this->someoneCookingForThis($currentInfo, $item) ) {
					$form .= gfRawMsg('<form method="post">
	<fieldset>
	<legend>$1</legend>
	<input type="submit" name="openeating-$2-submit" value="$3" />
	</fieldset>
	</form>',
						gfMsg('openeatingform-title', $item->title),
						$item->id,
						gfMsg('openeatingform-submit')
					);
				
				}
			}
		}
		
		$form .= gfRawMsg('<form method="post">
<fieldset>
<legend>$1</legend>
<p>$2</p>
<input type="hidden" name="meeting-usertype" value="extra" />
<label for="meeting-name">$3:</label>
<input type="text" name="meeting-name" id="meeting-name" class="distanceitself" />
',
			gfMsg('joinform-title-extraperson'),
			gfMsg('joinform-extrapersontext'),
			gfMsg('joinform-extrapersonname')
		);
		
		foreach ( $this->sortSchedule($meeting->schedule) as $item ) {
			if ( $item->nojoin ) {
				continue;
			} else {
				if ( (!is_null($itemid) && $item->id == $itemid)
					|| (is_null($itemid) && !$item->unique) ) {
					if ( $item->type == 'meet' ) {
						$form .= gfRawMsg('<span class="scheduleform-item">$1:</span>', $item->title);
						$form .= gfRawMsg('
		<input type="checkbox" name="meeting-$1-attending" id="meeting-$1-$2-attending" $3 />
		<label for="meeting-$1-$2-attending">$4</label>',
							$item->id, $subuserid,
							'checked="true"',
							gfMsg('joinform-attending')
						);
						$form .= '<br />';
					} elseif ( $item->type == 'eat' ) {
						$form .= gfRawMsg('<span class="scheduleform-item">$1:</span>', $item->title);
						$form .= gfRawMsg('
		<input type="checkbox" name="meeting-$1-eating" id="meeting-$1-$2-eating" $3 $4 />
		<label for="meeting-$1-$2-eating">$5</label>',
							$item->id, $subuserid,
							(!$item->open?'disabled="true"':'checked="true"'),
							(!$item->open?'disabled="true"':''),
							gfMsg('joinform-eating')
						);
						$form .= gfRawMsg('
		<input type="checkbox" name="meeting-$1-cooking" id="meeting-$1-$2-cooking" $3 $4 />
		<label for="meeting-$1-$2-cooking">$5</label>',
							$item->id, $subuserid,
							'',
							(!$item->open?'disabled="true"':''),
							gfMsg('joinform-cooking')
						);
						$form .= gfRawMsg('
		<input type="checkbox" name="meeting-$1-foodhelp" id="meeting-$1-$2-foodhelp" $3 $4 />
		<label for="meeting-$1-$2-foodhelp">$5</label>',
							$item->id, $subuserid,
							'',
							(!$item->open?'disabled="true"':''),
							gfMsg('joinform-foodhelp')
						);
						$form .= (!$item->open
							? gfRawMsg(' <span>$1</span>',
								gfMsg('joinform-eatingisclosed',
									$this->getClosedBy(@$item->closedby)
								))
							: '' );
						$form .= '<br />';
					}
				}
			}
		}
		$form .= gfRawMsg('<br />
<label for="meeting-comment">$1:</label>
<input type="text" name="meeting-comment" id="meeting-comment" />
<input type="submit" name="meeting-submit" value="$2" />
</fieldset>
</form>',
			gfMsg('joinform-comment'),
			gfMsg('joinform-submit-join')
		);
		return $form;
	}
	
	private function someoneCookingForThis ( $currentInfo, $item ) {
		foreach ( $currentInfo as $info ) {
			if ( $info->schedule->{$item->id}->cooking )
				return true;
		}
		return false;
	}
	
	private function getClosedBy ( $userid ) {
		if ( empty($userid) ) {
			return gfMsg('user-system');
		}
		return gfRawMsg('<b>$1</b>', $this->database->getUserById($userid)->name);
	}
}
