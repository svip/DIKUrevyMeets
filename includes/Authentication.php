<?php

class Authentication {
	
	public $userinfo = array();
	private $database = null;
	private $loginChecked = false;
	private $login = false;

	function __construct ( $database ) {
		$this->database = $database;
	}
	
	public function setCookie ( $name, $value ) {
		global $CookieDomain;
		setcookie ( $name, $value, time() + 365*24*60*60, '/', $CookieDomain );
	}
	
	public function unsetCookie ( $name ) {
		global $CookieDomain;
		setcookie ( $name, '', 0, '/', $CookieDomain );
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
	
	private function drupalRegister ( $uid, $name, $nickname ) {
		global $Debug;
		if ( $this->database->insertUser ( $name, $nickname, 'drupal', $uid, null ) ) {
			if ( !$Debug ) // Never redirect in debug mode.
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
									array ( 'realname' => $result['value'],
										'nickname' => $result['name']) );
							}
							return true;
						}
					}
					$this->drupalRegister($result['uid'], $result['value'], $result['name']);
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
	
	public function getInformation ( $information ) {
		if ( isset($this->userinfo->{$information}) )
			return $this->userinfo->{$information};
		return null;
	}
	
	public function logInFunction ( ) {
		$form = gfRawMsg('<form><h5>$1</h5></form>',
			gfMsg('joinform-needslogin', 'http://dikurevy.dk/', 'Drupal system')
		);
		return $form;
	}
}

$auth = new Authentication($database);
