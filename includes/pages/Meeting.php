<?php

class Meeting extends Page {

	protected function render() {
		$m = $_GET['meeting'];
		$meeting = $this->database->getMeetings()->{$m};
		if ( empty ( $meeting ) ) {
			$this->content = '<p>Intet møde på '.$this->weekDay($m).' den '.$this->readableDate($m).'</p>';
			$this->content .= '<p><a href="./">Til forsiden</a>.</p>';
			return;
		}
		$this->makePage ( $m, $meeting );
	}
	
	private function makePage ( $date, $meeting ) {
		$content = '<h1>'.$meeting->{'title'}.'</h1><h2>'.$this->weekDay($date, true).' den '.$this->readableDate($date).'</h2>';
		$content .= '<p>Møde: <b>'.$meeting->{'meettime'}.'</b>';
		if ( $meeting->{'haseating'} ) {
			$content .= ', spisetid: <b>'.$meeting->{'eattime'}.'</b>';
		}
		$content .= '.</p>';
		foreach ( $meeting->{'users'} as $user ) {
		
		}
		if ( $this->auth->loggedIn() ) {
		
		} else {
			$content .= $this->logInFunction();
		}
		$this->content = $content;
	}
}

$page = new Meeting($database, $auth);
