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
}

$page = new Actions($database, $auth);
