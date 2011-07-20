<?php

class Output {

	private $page = null;
	private $auth = null;
	
	function __construct ( $page, $auth ) {
		$this->page = $page;
		$this->auth = $auth;
		$this->outputHtml();
	}
	
	function outputHtml ( ) {
		$template = file_get_contents ( 'includes/template.html' );
		echo str_replace (
			array ( '{{CONTENT}}', '{{TOPMENU}}' ),
			array ( $this->page->getContent(), $this->topMenu() ),
			$template );
	}
	
	private function topMenu ( ) {
		if ( $this->auth->loggedIn() ) {
			return '<div id="topmenu"><a href="./?do=logout">Log ud</a> &middot; <a href="./">Front page</a></div>';
		}
		return '';
	}	
}

$output = new Output($page, $auth);
