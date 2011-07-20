<?php

require ( 'openid.php' );

class Authentication {
	
	public $userinfo = array();
	private $database = null;
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
	
	public function loggedIn ( ) {
		if ( !empty ( $_COOKIE['rym-openid-identity'] )
			&& !empty ( $_COOKIE['rym-openid-sig'] ) ) {
			$identity = $_COOKIE['rym-openid-identity'];
			$signature = $_COOKIE['rym-openid-sig'];
			foreach ( $this->database->getUsers() as $user ) {
				if ( $user->{'identity'} == $identity
					&& $user->{'service'} == 'openid'
					&& $user->{'signature'} == $signature ) {
					$this->userinfo = $user;
					return true;
				}
			}
		}
		if ( !empty ( $_COOKIE['rym-google-identity'] )
			&& !empty ( $_COOKIE['rym-google-sig'] ) ) {
			$identity = $_COOKIE['rym-google-identity'];
			$signature = $_COOKIE['rym-google-sig'];
			foreach ( $this->database->getUsers() as $user ) {
				if ( $user->{'identity'} == $identity
					&& $user->{'service'} == 'google'
					&& $user->{'signature'} == $signature ) {
					$this->userinfo = $user;
					return true;
				}
			}			
		}
		return false;
	}
	
	public function isAdmin ( ) {
		return $this->userinfo->{'admin'};
	}
	
	public function logInFunction ( ) {
		$openidTruth = $this->openidLogin;
		$googleTruth = $this->googleLogin;
		$script = '';
		if ( $openidTruth ) {
			$script .= 'loginSelect(\'openid\');';
		}
		$form = '<form method="post" id="login">
<h5>For at kunne tilmelde dig dette revymøde, skal du først logge ind via en af de følgende systemer.</h5>
<h4>Hvem har du solgt din sjæl til?</h4>
<ul id="login-select">
<li id="login-select-openid" onclick="loginSelect(\'openid\');">OpenID</li>
<li id="login-select-google" onclick="loginSelect(\'google\');">Google Konto</li>
<li id="login-select-facebook" onclick="loginSelect(\'facebook\');">Facebook</li>
<li id="login-select-twitter" onclick="loginSelect(\'twitter\');">Twitter</li>
</ul>
<div class="clear"></div>
<fieldset id="login-openid">
<legend>OpenID</legend>
'.(!$openidTruth ? '
<fieldset>
<legend>Log ind</legend>
<label for="login-openid-url">OpenID-identitet:</label>
<input type="text" id="login-openid-url" name="login-openid-url" />
<input type="submit" name="login-openid-submit" value="Log ind" />
</fieldset>':'').'
'.($openidTruth ? '
<fieldset>
<legend>Opret ny bruger</legend>
<label for="register-openid-username">Ønsket brugernavn:</label>
<input type="text" id="register-openid-username" name="register-openid-username" />
<input type="submit" name="register-openid-submit" value="Opret ny bruger" />
</fieldset>
' : '').'
</fieldset>
<fieldset id="login-google">
<legend>Google Konto</legend>
'.(!$googleTruth ? '
<fieldset>
<legend>Log ind</legend>
<input type="submit" name="login-google-submit" value="Klik her for at logge ind" />
</fieldset>' : '' ).'
'.($googleTruth ? '<fieldset>
<legend>Opret ny bruger</legend>
<label for="register-google-username">Ønsket brugernavn:</label>
<input type="text" id="register-google-username" name="register-google-username" />
<input type="submit" name="register-google-submit" value="Opret ny bruger" />
</fieldset>' : '' ).'
</fieldset>
<fieldset id="login-facebook">
<legend>Facebook</legend>
<fieldset>
<legend>Log ind</legend>
</fieldset>
<fieldset>
<legend>Opret ny bruger</legend>
</fieldset>
</fieldset>
<fieldset id="login-twitter">
<legend>Twitter</legend>
<fieldset>
<legend>Log ind</legend>
</fieldset>
<fieldset>
<legend>Opret ny bruger</legend>
</fieldset>
</fieldset>
</form>';
		if ( $script != '' )
			$script = '<script>'.$script.'</script>';
		return $form.$script;
	}
}

$auth = new Authentication($database);
