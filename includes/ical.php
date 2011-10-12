<?php

class Ical {
	
	private $database = null;
	private $content = '';
	
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
			if ( is_numeric(@$meeting->days) ) {
				$startDate = $this->icalTime($date, $meeting->schedule->{0}->start);
				$endDate = str_replace('-', '', $this->getEndDate($date, $meeting->days+1));
				$uid = "dikurevy{$this->uid($date, $startDate, -1, $meeting->title)}";
				$content .= <<<EOF
BEGIN:VEVENT
SUMMARY:{$meeting->title}
DTSTART;TZID=Europe/Copenhagen;VALUE=DATE-TIME:$startDate
DTEND;TZID=Europe/Copenhagen;VALUE=DATE:$endDate
DTSTAMP;TZID=Europe/Copenhagen;VALUE=DATE:$startDate
TRANSP:OPAQUE
SEQUENCE:0
STATUS:CONFIRMED
UID:$uid
END:VEVENT

EOF;
			} else {
				$schedule = $this->sortSchedule($meeting->schedule);
				$day = array();
				foreach ( $schedule as $i => $item ) {
					if ( @$item->hidden )
						continue;
					if ( @$item->icalunique ) {
						if ( !isset($day[0]) )
							$day[1] = array (
								'title'		=>	"{$meeting->title}: {$item->title}",
								'dtstart'	=>	$this->icalTime($date, $item->start),
								'dtend'		=>	$this->icalTime($date, $item->end),
								'dtstamp'	=>	$this->icalTime($date, $item->start),
								'uid'		=>	"dikurevy{$this->uid($date, $item->start, $i, $meeting->title . $item->title)}"
							);
						else
							$day[] = array (
								'title'		=>	"{$meeting->title}: {$item->title}",
								'dtstart'	=>	$this->icalTime($date, $item->start),
								'dtend'		=>	$this->icalTime($date, $item->end),
								'dtstamp'	=>	$this->icalTime($date, $item->start),
								'uid'		=>	"dikurevy{$this->uid($date, $item->start, $i, $meeting->title . $item->title)}"
							);
					} else {
						if ( isset($day[0]) ) {
							if ( $this->isNewer($this->icalTime($date, $item->end), $day[0]['dtend']) )
								$day[0]['dtend'] = $this->icalTime($date, $item->end);
							if ( $this->isNewer($day[0]['dtstart'], $this->icalTime($date, $item->start)) )
								$day[0]['dtstart'] = $this->icalTime($date, $item->start);
						} else {
							$day[0] = array (
								'title'		=>	"{$meeting->title}",
								'dtstart'	=>	$this->icalTime($date, $item->start),
								'dtend'		=>	$this->icalTime($date, $item->end),
								'dtstamp'	=>	$this->icalTime($date, $item->start),
								'uid'		=>	"dikurevy{$this->uid($date, $item->start, $i, $meeting->title . $item->title)}"
							);
						}
					}
				}
				ksort($day);
				foreach ( $day as $item ) {
					$content .= <<<EOF
BEGIN:VEVENT
SUMMARY:{$item['title']}
DTSTART;TZID=Europe/Copenhagen;VALUE=DATE-TIME:{$item['dtstart']}
DTEND;TZID=Europe/Copenhagen;VALUE=DATE-TIME:{$item['dtend']}
DTSTAMP;TZID=Europe/Copenhagen;VALUE=DATE-TIME:{$item['dtstamp']}
TRANSP:OPAQUE
SEQUENCE:0
STATUS:CONFIRMED
UID:{$item['uid']}
END:VEVENT

EOF;
				}
			}
		}
		$content .= <<<EOF
END:VCALENDAR

EOF;
		$this->content = $content;
	}
	
	private function uid ( $date, $time, $id, $title ) {
		return md5(str_replace('-', '', $date).$time.$id.preg_replace('@[^A-Za-z0-9]@is', '', $title));
	}
	
	private function icalTime ( $date, $time ) {
		return str_replace('-', '', $date).'T'.str_replace(':', '', $time).'00';
	}
	
	private function isNewer ( $test, $against ) {
		$test = strptime($test, "%Y%m%dT%H%M00");
		$against = strptime($against, "%Y%m%dT%H%M00");
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
