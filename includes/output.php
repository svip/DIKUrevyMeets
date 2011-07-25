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
	
	private function topMenu ( ) {
		$menu = array();
		if ( $this->auth->loggedIn() ) {
			if ( $this->auth->isAdmin () ) {
				$menu[] = array ( './?admin=front', 'Admin' );
			}
			$menu[] = array ( './?do=logout', 'Log ud' );
		}
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
