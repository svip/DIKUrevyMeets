<?php

class Ical {
	
	private $database = null;
	private $content = '';
	private $icalTimeStamp = '%Y%m%dT%H%M00Z';
	private $icalTimeStampN = 'Ymd\THi00\Z';
	private $icalTimeStampM = 'Ymd\THis\Z';
	
	function __construct ( $database ) {
		$this->database = $database;
		$this->render();
	}
	
	function getContent ( ) {
		return $this->content;
	}
	
	private function getEndDate ( $date, $days ) {
		$t = DateTime::createFromFormat ( "Y-m-d", $date );
		$t->add(new DateInterval("P{$days}D"));
		return $t->format('Y-m-d');
	}
	
	private function render ( ) {
		$meetings = $this->database->getMeetings();
		if ( isset($_GET['tags']) ) {
			$tags = explode ( ',', $_GET['tags'] );
		} else {
			$tags = null;
		}
		$content = <<<EOF
BEGIN:VCALENDAR
PRODID:-//DIKUrevy//Møder//DA
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-CALNAME:DIKUrevy møder
X-WR-TIMEZONE:Europe/Copenhagen
X-WR-CALCDESC:DIKUrevy møder
BEGIN:VTIMEZONE
TZID:Europe/Copenhagen
X-LIC-LOCATION:Europe/Copenhagen
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
TZOFFSETTO:+0200
TZNAME:CEST
DTSTART:19700329T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
TZOFFSETTO:+0100
TZNAME:CET
DTSTART:19701025T030000
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
END:STANDARD
END:VTIMEZONE

EOF;
		foreach ( $meetings as $date => $meeting ) {
			if ( !is_null($tags) ) {
				$fitsSearch = false;
				foreach ( $meeting->tags as $mtag ) {
					if ( in_array($mtag, $tags) ) {
						$fitsSearch = true;
						break;
					}
				}
				if ( !$fitsSearch )
					continue;
			}
			if ( is_numeric(@$meeting->days)
				|| !isset($meeting->schedule->{0}) ) {
				// multi day event
				$startDate = str_replace('-', '', $date);
				$dtStamp = $this->dtStamp($date, '00:00');
				if ( is_numeric(@$meeting->days) )
					$endDate = str_replace('-', '', $this->getEndDate($date, $meeting->days+1));
				else
					$endDate = str_replace('-', '', $this->getEndDate($date, 1));
				$uid = "dikurevy{$this->uid($date, $startDate, -1, $meeting->title)}";
				$content .= <<<EOF
BEGIN:VEVENT
SUMMARY:{$meeting->title}
DTSTART;TZID=Europe/Copenhagen;VALUE=DATE:$startDate
DTEND;TZID=Europe/Copenhagen;VALUE=DATE:$endDate
DTSTAMP:$dtStamp
TRANSP:OPAQUE
SEQUENCE:0
STATUS:CONFIRMED
UID:$uid
END:VEVENT

EOF;
			}
			// On multi day events, each sub event will still appear
			// invidually.  One might consider not doing that if there is
			// only *one* subevent for the multi day event.
			$schedule = $this->sortSchedule($meeting->schedule);
			$day = array();
			foreach ( $schedule as $i => $item ) {
				if ( @$item->hidden )
					continue;
				// all events are now unique
				$summary = '';
				$description = '';
				if ( !$item->nojoin ) {
					$modified = 0;
					$participants = array(
						'attending'  => 0,
						'cooking'    => 0, // useless for meets
					);
					$names = array (
						'attending'  => array(),
						'cooking'    => array(),
					);
					foreach ( $meeting->users as $userid => $user ) {
						if ( $user->modified > $modified )
							$modified = $user->modified;
						foreach ( $user->schedule as $j => $userSchedule ) {
							if ( $j == $item->id ) {
								if ( $user->usertype == 'extra' ) {
									$name = $user->name;
								} else {
									$userObj = $this->database->getUserById(intval($userid));
									$name = $userObj->name;
								}
								if ( @$userSchedule->attending
									|| @$userSchedule->eating ) {
									$participants['attending']++;
									$names['attending'][] = $name;
								}
								if ( @$userSchedule->cooking ) {
									$participants['cooking']++;
									$names['cooking'][] = $name;
								}
								break;
							}
						}
					}
					
					$attendingList = $this->naturalLanguageList($names['attending']);
					if ( $item->type == 'meet' ) {
						$summary = " ({$participants['attending']} deltager(e))";
						$description = "Folk der kommer: $attendingList";
					} else {
						$summary = " ({$participants['attending']} deltager(e), {$participants['cooking']} kok(ke))";
						$cookingList = $this->naturalLanguageList($names['cooking']);
						$description = "Folk der kommer: $attendingList\\nKokke: $cookingList";
					}
					$dtstamp = date($this->icalTimeStampM, $modified);
				} else {
					$dtstamp = $this->dtStamp($date, $item->start);
				}
				$day[] = array (
					'title'     => "{$meeting->title}: {$item->title}{$summary}",
					'description' => $description,
					'dtstart'   => $this->icalTime($date, $item->start),
					'dtend'     => $this->icalTime($date, $item->end),
					'dtstamp'   => $dtstamp,
					'uid'       => "dikurevy{$this->uid($date, $item->start, $i, $meeting->title . $item->title)}"
				);
			}
			foreach ( $day as $item ) {
				$content .= <<<EOF
BEGIN:VEVENT
SUMMARY:{$item['title']}
DESCRIPTION:{$item['description']}
DTSTART;TZID=Europe/Copenhagen;VALUE=DATE-TIME:{$item['dtstart']}
DTEND;TZID=Europe/Copenhagen;VALUE=DATE-TIME:{$item['dtend']}
DTSTAMP:{$item['dtstamp']}
TRANSP:OPAQUE
SEQUENCE:0
STATUS:CONFIRMED
UID:{$item['uid']}
END:VEVENT

EOF;
			}
		}
		$content .= <<<EOF
END:VCALENDAR

EOF;
		$this->content = $content;
		$this->content = str_replace("\r", '', $this->content);
		$this->content = str_replace("\n", "\r\n", $this->content);
	}
	
	private function naturalLanguageList ( $list ) {
		$output = '';
		$count = count($list);
		
		foreach ( $list as $i => $item ) {
			if ( $output != '' ) {
				if ( $i == $count-1 ) {
					$output .= ' og ';
				} else {
					$output .= ', ';
				}
			}
			$output .= $item;
		}
		
		return $output;
	}
	
	private function uid ( $date, $time, $id, $title ) {
		return md5(str_replace('-', '', $date).$time.$id.preg_replace('@[^A-Za-z0-9]@is', '', $title));
	}
	
	private function icalTime ( $date, $time ) {
		$datetime = DateTime::createFromFormat('Y-m-d H:i',
			$date . ' ' . $time, new DateTimeZone('Europe/Copenhagen'));
		return $datetime->format($this->icalTimeStampN);
	}
	
	private function dtStamp ( $date, $time ) {
		$datetime = DateTime::createFromFormat('Y-m-d H:i', 
			$date . ' ' . $time, new DateTimeZone('Europe/Copenhagen'));
		$datetime->setTimezone(new DateTimeZone('UTC'));
		return $datetime->format($this->icalTimeStampN);
	}
	
	private function isNewer ( $test, $against ) {
		$test = strptime($test, $this->icalTimeStamp);
		$against = strptime($against, $this->icalTimeStamp);
		return $test > $against;
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
}
