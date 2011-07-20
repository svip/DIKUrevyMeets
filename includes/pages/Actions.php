<?php

class Actions extends Page {

	protected function render ( ) {
		switch ( $_GET['do'] ) {
			case 'logout':
				$this->logout();
				break;
			default:
				header ( 'Location: ' . $_SERVER['HTTP_REFERER'] );
				break;
		}
	}
	
	private function logout ( ) {
		foreach ( $_COOKIE as $cookie => $value ) {
			if ( strpos($cookie, 'rym-')!==false ) {
				$this->auth->unsetCookie ( $cookie );
			}
		}
		header ( 'Location: ' . $_SERVER['HTTP_REFERER'] );
	}
}

$page = new Actions($database, $auth);
