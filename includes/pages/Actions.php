<?php

require ( 'includes/ical.php' );

class Actions extends Page {

	private $ical = null;

	protected function render ( ) {
		switch ( $_GET['do'] ) {
			case 'logout':
				$this->logout();
				break;
			case 'ical':
				$this->icalendar();
				break;
			case 'gettags':
				$this->getTags();
				break;
			default:
				header ( 'Location: ' . $_SERVER['HTTP_REFERER'] );
				break;
		}
	}
	
	private function icalendar ( ) {
		$this->ical = new Ical($this->database);
		$this->contentType = 'text/calendar';
		$this->content = $this->ical->getContent();
	}
	
	private function logout ( ) {
		header ( 'Location: /logout' );
	}
	
	private function getTags ( ) {
		$this->contentType = 'application/json';
		$search = isset($_GET['search'])?$_GET['search']:'';
		$tags = $this->database->getTags();
		$fTags = array();
		foreach ( $tags as $tag ) {
			if ( substr($tag, 0, strlen($search) ) == $search
				|| $search == '' )
				$fTags[] = $tag;
		}
		$this->content = json_encode ( $fTags );
	}
}

$page = new Actions($database, $auth);
