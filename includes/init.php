<?php

class Page {

	protected $weekdays = array ( null,
		'mandag', 'tirsdag', 'onsdag', 'torsdag', 'fredag', 'lørdag', 'søndag'
	);

	protected $content = '';
	protected $database = null;
	protected $auth = null;
	protected $additionalScript = array();
	
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
	
	protected function render ( ) {
		// re-implement;
	}
	
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
			$tmp[intval(str_replace(':', '', $item->start))] = $item;
			$tmp[intval(str_replace(':', '', $item->start))]->id = $i;
		}
		ksort($tmp);
		return $tmp;
	}
}

if ( isset ( $_GET['meeting'] ) 
	&& preg_match ( '@[0-9]{4}-[0-9]{2}-[0-9]{2}@', $_GET['meeting'] ) ) {
	require ( 'includes/pages/Meeting.php' );
} elseif ( isset ( $_GET['do'] ) ) {
	require ( 'includes/pages/Actions.php' );
} elseif ( isset ( $_GET['admin'] ) ) {
	require ( 'includes/pages/Admin.php' );
} else {
	require ( 'includes/pages/Front.php' );
}
