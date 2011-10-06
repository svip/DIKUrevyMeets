<?php

abstract class Page {

	protected $weekdays = array ( null,
		'mandag', 'tirsdag', 'onsdag', 'torsdag', 'fredag', 'lørdag', 'søndag'
	);

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
	
	protected function sortSchedule ( $schedule ) {
		$tmp = array();
		foreach ( $schedule as $i => $item ) {
			$time = intval(str_replace(':', '', $item->start));
			$tmp[$time] = $item;
			$tmp[$time]->id = $i;
		}
		ksort($tmp);
		return $tmp;
	}
}
