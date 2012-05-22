<?php

class Authentication {
	
	public $userinfo = array();
	private $database = null;
	private $loginChecked = false;
	private $login = false;

	function __construct ( $database ) {
		$this->database = $database;
		//$this->checkLoginAttempt();
	}
	
	public function setCookie ( $name, $value ) {
		setcookie ( $name, $value, time() + 365*24*60*60, '/', '.dikurevy.dk' );
	}
	
	public function unsetCookie ( $name ) {
		setcookie ( $name, '', 0, '/', '.dikurevy.dk' );
	}
	
	private function checkLoginAttempt ( ) {
		/*
		if ( isset ( $_POST['login-openid-submit'] ) ) {
			$this->openIdLogin();
		}
		if ( isset ( $_POST['register-openid-submit'] ) ) {
			$this->openIdRegister();
		}
		if ( isset ( $_POST['login-google-submit'] ) ) {
			$this->googleLogin();
		}
		if ( isset ( $_POST['register-google-submit'] ) ) {
			$this->googleRegister();
		}*/
	}
	
	private function getSignature ( $identity, $service ) {
		foreach ( $this->database->getUsers() as $user ) {
			if ( $user->identity == $identity
				&& $user->service == $service ) {
				return $user->signature;	
			}
		}
		return null;
	}
	
	private function drupalRegister ( $uid, $name ) {
		global $debug;
		if ( $this->database->insertUser ( $name, 'drupal', $uid, null ) ) {
			if ( !$debug ) // Never redirect in debug mode.
				if ( !empty($_SERVER['HTTP_REFERER']) )
					header('Location: '.$_SERVER['HTTP_REFERER']);
				else
					header('Location: ./');
		}
	}
	
	private function drupalCookie ( ) {
		foreach ( $_COOKIE as $name => $cookie ) {
			if ( preg_match("@SESS.*@is", $name ) ) {
				$data = $cookie;
				$i = gfDBQuery ( "SELECT s.`uid`, u.`name`, p.`value`
					FROM `drupal_sessions` s
						JOIN `drupal_users` u
							ON s.`uid` = u.`uid`
						LEFT JOIN `drupal_profile_values` p
							ON p.`uid` = s.`uid` AND p.`fid` = 14
					WHERE s.`sid` = '$data' AND s.`uid` != 0" );
				if ( gfDBGetNumRows($i) > 0 ) {
					$result = gfDBGetResult($i);
					foreach ( $this->database->getUsers() as $user ) {
						if ( is_object($user)
							&& $user->{'identity'} == $result['uid'] ) {
							$this->userinfo = $user;
							$this->loginChecked = true;
							$this->login = true;
							if ( $result['value'] != null
								&& $result['value'] != $user->name ) {
								$this->database->updateUser ( $result['uid'],
									array ( 'username' => $result['value'] ) );	
							}
							return true;
						}
					}
					$this->drupalRegister($result['uid'], (!empty($result['value'])?$result['value']:$result['name']));
					$this->userinfo = $user;
					$this->loginChecked = true;
					$this->login = true;
					return true;
				}
			}
		}
		$this->loginChecked = true;
		$this->login = false;
		return false;
	}
	
	public function loggedIn ( ) {
		if ( $this->loginChecked ) 
			return $this->login;
		return $this->drupalCookie();
	}
	
	public function isAdmin ( ) {
		if ( !$this->loginChecked )
			$this->loggedIn();
		if ( !isset ( $this->userinfo->admin ) )
			return false;
		return $this->userinfo->{'admin'};
	}
	
	public function logInFunction ( ) {
		$form = '<form><h5>For at kunne tilmelde dig dette revymøde, skal du logge ind via vores nye <a href="http://dikurevy.dk/">Drupal system</a>.  Returnér her når du har logget ind der.</h5></form>';
		return $form;
	}
}

$auth = new Authentication($database);
