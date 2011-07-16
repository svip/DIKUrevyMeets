<?php

class Output {

	private $page = null;
	
	function __construct ( $page ) {
		$this->page = $page;
		$this->outputHtml();
	}
	
	function outputHtml ( ) {
		$template = file_get_contents ( 'includes/template.html' );
		echo str_replace (
			array ( '{{CONTENT}}' ),
			array ( $this->page->getContent() ),
			$template );
	}
	
}

$output = new Output($page);
