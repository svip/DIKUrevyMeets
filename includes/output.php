<?php

class Output {

	private $page = null;
	private $auth = null;
	private $contentType = null;
	
	function __construct ( $page, $auth ) {
		$this->page = $page;
		$this->auth = $auth;
		$this->contentType = $page->getContentType();
		if ( empty ($this->contentType) )
			$this->outputHtml();
		else
			$this->outputRaw();
	}
	
	function outputHtml ( ) {
		$scripts = '';
		foreach ( $this->page->getAdditionalScripts() as $script ) {
			$scripts .= '<script src="./media/'.$script.'"></script>';
		}
		$template = file_get_contents ( 'includes/template.html' );
		echo str_replace (
			array ( '{{CONTENT}}', '{{TOPMENU}}', '{{SCRIPT}}' ),
			array ( $this->page->getContent(), $this->topMenu(), $scripts ),
			$template );
	}
	
	function outputRaw ( ) {
		header('Content-Type: '.$this->contentType.'; charset=UTF-8');
		echo $this->page->getContent();
	}
	
	private function topMenu ( ) {
		$menu = array();
		if ( $this->auth->loggedIn() ) {
			if ( $this->auth->isAdmin () ) {
				$menu[] = array ( './?admin=front', 'Admin' );
			}
			$menu[] = array ( './?do=logout', 'Log ud' );
		}
		$menu[] = array ( './?do=ical', 'ical' );
		$menu[] = array ( './', 'Forside' );
		$str = '';
		foreach ( $menu as $item ) {
			if ( $str != '' )
				$str .= ' &middot; ';
			$str .= '<a href="'.$item[0].'">'.$item[1].'</a>';
		}
		return $str;
	}	
}

$output = new Output($page, $auth);
