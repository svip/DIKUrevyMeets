<?php

class MessageHandler {

	public function MessageHandler ( $database, $auth ) {
		global $DefaultLanguage;
		
		$language = isset($_GET['uselang'])
			? self::cleanLanguageCode($_GET['uselang'])
			: ($auth->loggedIn()
				? $auth->getInformation('language')
				: $DefaultLanguage);
		
		echo $language;
		
		$this->getMessages( strtolower($language) );
	}
	
	public function getMessages ( $language ) {
		global $messages, $backupmessages, $DefaultLanguage, $ScriptDirectory;
		
		$backupLanguage = 'da';
		
		// Danish is the back up language.
		require ( $ScriptDirectory.'/includes/messages/Messages'.ucfirst($backupLanguage).'.php' );
		$backupmessages = $messages;
		
		// Then get the real language.
		// If the language provided does not exist, ignore it.
		if ( !file_exists($ScriptDirectory.'/includes/messages/Messages'.ucfirst($language).'.php') )
			$language = $DefaultLanguage;
		
		// If the languages match, there is no need to load the file twice.
		if ( $language == $backupLanguage )
			return;
		
		require ( $ScriptDirectory.'/includes/messages/Messages'.ucfirst($language).'.php' );
	}
	
	public static function cleanLanguageCode ( $code ) {
		return preg_replace('@[^a-z]@i', '', $code);
	}
}

$messages = array();
$backupmessages = array();
$messageHandler = new MessageHandler($database, $auth);
