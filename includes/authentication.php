<?php

class Authentication {
	
	private $userinfo = array();

	function __construct ( ) {
	
	}
	
	public function loggedIn ( ) {
		return !empty ( $this->userinfo['username'] );
	}
	
	public function logInFunction ( ) {
		return '<form method="post" id="login">
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
<fieldset>
<legend>Log ind</legend>
<label for="login-openid-username">Brugernavn:</label>
<input type="text" id="login-openid-username" name="login-openid-username" />
<label for="login-openid-password">OpenID løsen:</label>
<input type="password" id="login-openid-password" name="login-openid-password" />
<input type="submit" name="login-openid-submit" value="Log ind" />
</fieldset>
<fieldset>
<legend>Opret ny bruger</legend>
<label for="register-openid-username">Ønsket brugernavn:</label>
<input type="text" id="register-openid-username" name="register-openid-username" />
<label for="register-openid-url">OpenID URL:</label>
<input type="text" id="register-openid-url" name="register-openid-url" />
<label for="login-openid-password">OpenID løsen:</label>
<input type="password" id="register-openid-password" name="register-openid-password" />
<input type="submit" name="register-openid-submit" value="Opret ny bruger" />
</fieldset>
</fieldset>
<fieldset id="login-google">
<legend>Google Konto</legend>
<fieldset>
<legend>Log ind</legend>
</fieldset>
<fieldset>
<legend>Opret ny bruger</legend>
</fieldset>
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
	}
}

$auth = new Authentication();
