<?php

class Front extends Page { 
	
	protected function render ( ) {
		$list = '<ul>';
		foreach ( $this->database->getMeetings() as $date => $meeting ) {
			$list .= '<li><a href="?meeting='.$date.'">'.$date.': '.$meeting->{'title'}."</a></li>\n";
		}
		$list .= '</ul>';
		$this->content = $list;
	}
	
}

$page = new Front($database);
