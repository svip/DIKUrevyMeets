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
		$styles = '';
		foreach ( $this->page->getAdditionalStyles() as $style ) {
			$styles .= '<link rel="stylesheet" href="./media/'.$style.'" />';
		}
		$template = file_get_contents ( 'includes/template.html' );
		echo str_replace (
			array ( '{{CONTENT}}', '{{TOPMENU}}', '{{SCRIPT}}', '{{STYLE}}' ),
			array ( $this->page->getContent(), $this->topMenu(), $scripts, $styles ),
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
		$menu[] = array ( 'http://moeder.dikurevy.dk/?do=ical', 'ical' );
		$menu[] = array ( './', 'Forside' );
		$menu[] = array ( 'http://dikurevy.dk/', 'dikurevy.dk' );
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
