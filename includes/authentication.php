<?php

require ( 'openid.php' );

class Authentication {
	
	public $userinfo = array();
	private $database = null;
	private $loginChecked = false;
	private $login = false;
	private $openid = null;
	private $openidLogin = false;
	private $google = null;
	private $googleLogin = false;

	function __construct ( $database ) {
		$this->database = $database;
		$this->openIdInit();
		$this->googleInit();
		$this->checkLoginAttempt();
	}
	
	public function setCookie ( $name, $value ) {
		setcookie ( $name, $value, time() + 365*24*60*60, '/', 'dikurevy.dk' );
	}
	
	public function unsetCookie ( $name ) {
		setcookie ( $name, '', 0, '/', 'dikurevy.dk' );
	}
	
	private function checkLoginAttempt ( ) {
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
		}
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
	
	private function openIdInit ( ) {
		if ( isset ( $_GET['openid_identity'] )
			&& strpos ( $_GET['openid_identity'], 'google.com' ) !== false )
			return;
		$this->openid = new LightOpenID('dikurevy.dk');
		if ( $this->openid->validate() ) {
			$this->openidLogin = true;
			$this->setCookie('rym-openid-identity', $this->openid->identity);
			$signature = $this->getSignature($this->openid->identity, 'openid');
			if ( $signature != null ) {
				$this->setCookie('rym-openid-sig', $signature);
				if ( isset ( $_GET['meeting'] ) ) {
					header ( 'Location: ./?meeting='.$_GET['meeting'] );
				} else {
					header ( 'Location: ./' );
				}				
			} else {
				$this->setCookie('rym-openid-sig', $_GET['openid_sig']);
			}
		}
		if ( isset ( $_COOKIE['rym-openid-identity'] ) ) {
			$this->openid->identity = $_COOKIE['rym-openid-identity'];
		}
	}
	
	private function openIdLogin ( ) {
		try {
			if(!$this->openid->mode) {
				if( isset( $_POST['login-openid-url'] ) ) {
				    $this->openid->identity = $_POST['login-openid-url'];
				    header('Location: ' . $this->openid->authUrl());
				}
			}
		} catch(ErrorException $e) {
			echo $e->getMessage();
		}		
	}
	
	private function openIdRegister ( ) {
		if ( isset ( $_POST['register-openid-username'] ) ) {
			$this->database->insertUser ( $_POST['register-openid-username'], 'openid', $_COOKIE['rym-openid-identity'], $_COOKIE['rym-openid-sig'] );
		}
	}
	
	private function googleInit ( ) {
    	$this->google = new LightOpenID('dikurevy.dk');
		if ( $this->google->validate() ) {
			$this->googleLogin = true;
			$this->setCookie('rym-google-identity', $this->google->identity);
			$signature = $this->getSignature($this->google->identity, 'google');
			if ( $signature != null ) {
				$this->setCookie('rym-google-sig', $signature);
				if ( isset ( $_GET['meeting'] ) ) {
					header ( 'Location: ./?meeting='.$_GET['meeting'] );
				} else {
					header ( 'Location: ./' );
				}
				
			} else {
				$this->setCookie('rym-google-sig', $_GET['openid_sig']);
			}
		}
		if ( isset ( $_COOKIE['rym-google-identity'] ) ) {
			$this->openid->identity = $_COOKIE['rym-google-identity'];
		}
	}
	
	private function googleLogin ( ) {
		try {
			if(!$this->google->mode) {
			    $this->google->identity = 'https://www.google.com/accounts/o8/id';
			    header('Location: ' . $this->google->authUrl());
			}
		} catch(ErrorException $e) {
			echo $e->getMessage();
		}		
	}
	
	private function googleRegister ( ) {
		if ( isset ( $_POST['register-google-username'] ) ) {
			$this->database->insertUser ( $_POST['register-google-username'], 'google', $_COOKIE['rym-google-identity'], $_COOKIE['rym-google-sig'] );
		}
	}
	
	private function drupalRegister ( $uid, $name ) {
		if ( $this->database->insertUser ( $name, 'drupal', $uid, null ) ) {
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
				$i = gfDBQuery ( "SELECT s.`uid`, u.`name`
					FROM `drupal_sessions` s
						JOIN `drupal_users` u
							ON s.`uid` = u.`uid`
					WHERE s.`sid` = '$data' AND s.`uid` != 0" );
				if ( gfDBGetNumRows($i) > 0 ) {
					$result = gfDBGetResult($i);
					foreach ( $this->database->getUsers() as $user ) {
						if ( is_object($user)
							&& $user->{'identity'} == $result['uid'] ) {
							$this->userinfo = $user;
							$this->loginChecked = true;
							$this->login = true;
							return true;
						}
					}
					$this->drupalRegister($result['uid'], $result['name']);
					$this->userinfo = $user;
					$this->loginChecked = true;
					$this->login = true;
					return true;
				}
			}
		}
		return false;
	}
	
	public function loggedIn ( ) {
		if ( $this->loginChecked ) 
			return $this->login;
		return $this->drupalCookie();
		if ( !empty ( $_COOKIE['rym-openid-identity'] )
			&& !empty ( $_COOKIE['rym-openid-sig'] ) ) {
			$identity = $_COOKIE['rym-openid-identity'];
			$signature = $_COOKIE['rym-openid-sig'];
			foreach ( $this->database->getUsers() as $user ) {
				if ( is_object($user)
					&& $user->{'identity'} == $identity
					&& $user->{'service'} == 'openid'
					&& $user->{'signature'} == $signature ) {
					$this->userinfo = $user;
					$this->loginChecked = true;
					$this->login = true;
					return true;
				}
			}
		}
		if ( !empty ( $_COOKIE['rym-google-identity'] )
			&& !empty ( $_COOKIE['rym-google-sig'] ) ) {
			$identity = $_COOKIE['rym-google-identity'];
			$signature = $_COOKIE['rym-google-sig'];
			foreach ( $this->database->getUsers() as $user ) {
				if ( is_object($user)
					&& $user->{'identity'} == $identity
					&& $user->{'service'} == 'google'
					&& $user->{'signature'} == $signature ) {
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
	
	public function isAdmin ( ) {
		if ( !$this->loginChecked )
			$this->loggedIn();
		if ( !isset ( $this->userinfo->admin ) )
			return false;
		return $this->userinfo->{'admin'};
	}
	
	public function logInFunction ( ) {
		$form = '<form><h5>For at kunne tilmelde dig dette revymøde, skal du logge ind via vores nye <a href="http://ny.dikurevy.dk/">Drupal system</a>.  Returnér her når du har logget ind der.</h5></form>';
		return $form;
	}
}

$auth = new Authentication($database);
