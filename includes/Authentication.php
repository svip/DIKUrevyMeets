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
	
	private function mojoliciousRegister ( $uid, $name, $nickname ) {
		global $Debug;
		if ( $this->database->insertUser ( $name, $nickname, 'mojolicious', $uid, null ) ) {
			if ( !$Debug ) // Never redirect in debug mode.
				if ( !empty($_SERVER['HTTP_REFERER']) )
					header('Location: '.$_SERVER['HTTP_REFERER']);
				else
					header('Location: ./');
		}
	}
	
	private function mojoliciousCookie ( ) {
		foreach ( $_COOKIE as $name => $cookie ) {
			if ( preg_match("@mojolicious@is", $name ) ) {
				list($data, $check) = explode('--', $cookie);
				if ( hash_hmac('sha1', $data, $CookieSecret) === $check ) {
					$userdata = json_decode(base64_decode($data));
					if ( $userdata !== null ) {
						$userid = $userdata->{'auth_data'};
						$i = gfDBQuery ( "SELECT `id`, `username`, `realname`
							FROM `users`
							WHERE `id` = '$userid'" );
						if ( gfDBGetNumRows($i) > 0 ) {
							$result = gfDBGetResult($i);
							foreach ( $this->database->getUsers() as $user ) {
								if ( is_object($user)
									&& $user->{'identity'} == $result['id'] ) {
									$this->userinfo = $user;
									$this->loginChecked = true;
									$this->login = true;
									if ( ($result['realname'] != null || !is_null($result['username']))
										&& ($result['realname'] != $user->name
										|| $result['username'] != @$user->nickname) ) {
										$this->database->updateUser ( $userid,
											array ( 'realname' => $result['realname'],
												'nickname' => $result['username']) );
									}
									return true;
								}
							}
						}
					}
					$this->mojoliciousRegister($result['id'], $result['realname'], $result['username']);
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
		return $this->mojoliciousCookie();
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
			gfMsg('joinform-needslogin', 'http://dikurevy.dk/', 'Mojolicious system')
		);
		return $form;
	}
}

$auth = new Authentication($database);
