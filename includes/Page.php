<?php

abstract class Page {

	protected $weekdays = array ( null,
		'mandag', 'tirsdag', 'onsdag', 'torsdag', 'fredag', 'lørdag', 'søndag'
	);
	protected $multidayEvent = false;

	protected $content = '';
	protected $database = null;
	protected $auth = null;
	protected $contentType = null;
	protected $additionalScript = array();
	protected $additionalStyles = array();
	
	function __construct ( $database, $auth ) {
		$this->database = $database;
		$this->auth = $auth;
		$this->render();
	}
	
	function getContent ( ) {
		return $this->content;
	}
	
	function getAdditionalScripts ( ) {
		return $this->additionalScript;
	}
	
	function getAdditionalStyles ( ) {
		return $this->additionalStyles;
	}
	
	function getContentType ( ) {
		return $this->contentType;
	}
	
	abstract protected function render ( ); // reimplement
	
	protected function weekDay ( $date, $capitalise=false ) {
		$t = $this->weekdays[date('N', strtotime($date))];
		if ( !$capitalise )
			return $t;
		return ucfirst ( $t );
	}
	
	protected function readableDate ( $date ) {
		$t = explode('-',  $date);
		return ($t[2]+0).'/'.($t[1]+0);
	}

	protected function logInFunction ( ) {
		return $this->auth->logInFunction();
	}
	
	protected function safeString ( $str ) {
		return str_replace ( 
			array ( '"', '&' ),
			array ( '&quot;', '&amp;' ),
			$str );
	}
	
	protected function fixItemTime ( $time ) {
		if ( preg_match('@^[0-9]{2}:[0-9]{2}@', $time) )
			$time = '0 '.$time;
		return $time;
	}
	
	protected function showTime ( $time ) {
		$split = explode(' ', $time);
		
		return $split[1];
	}
	
	protected function sortSchedule ( $schedule ) {
		$tmp = array();
		foreach ( $schedule as $i => $item ) {
			$item->start = $this->fixItemTime($item->start);
			$item->end = $this->fixItemTime($item->end);
			$time = $this->timeval($item->start.$item->end);
			$tmp[$time] = $item;
			$tmp[$time]->id = $i;
		}
		ksort($tmp);
		return $tmp;
	}
	
	protected function timeval ( $time ) {
		return intval ( str_replace ( ':', '', $time ) );
	}
	
	protected function fullTimestamp ( $time ) {
		return date ( 'd/m/Y H:i:s', $time );
	}
	
	protected function getEndDate ( $date, $days ) {
		$t = DateTime::createFromFormat ( "Y-m-d", $date );
		$t->add(new DateInterval("P{$days}D"));
		return $t->format('Y-m-d');
	}
}
