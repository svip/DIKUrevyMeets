<?php

class Page {

	protected $content = '';
	protected $database = null;
	
	function __construct ( $database ) {
		$this->database = $database;
		$this->render();
	}
	
	function getContent ( ) {
		return $this->content;
	}
	
	protected function render ( ) {
		// re-implement;
	}

}

require ( 'includes/pages/Front.php' );
