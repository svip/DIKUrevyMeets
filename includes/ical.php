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
TZID:Europe/Paris
X-LIC-LOCATION:Europe/Paris
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
			$schedule = $this->sortSchedule($meeting->schedule);
			foreach ( $schedule as $i => $item ) {
				$content .= <<<EOF
BEGIN:VEVENT
SUMMARY:{$meeting->title}: {$item->title}
DTSTART:{$this->icalTime($date, $item->start)}
DTEND:{$this->icalTime($date, $item->end)}
DTSTAMP:{$this->icalTime($date, $item->start)}
TRANSP:OPAQUE
SEQUENCE:0
STATUS:CONFIRMED
UID:dikurevy{$this->uid($date, $item->start, $i, $meeting->title . $item->title)}
END:VEVENT

EOF;
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
		return str_replace('-', '', $date).'T'.str_replace(':', '', $time).'00Z';
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
