<?php

require_once ( 'includes/GlobalVariables.php' );

require_once ( 'config.php' );

require_once ( 'includes/Database.php' );

require_once ( 'includes/GlobalFunctions.php' );

require_once ( 'includes/Authentication.php' );

require_once ( 'includes/MessageHandler.php' );

require_once ( 'includes/Page.php' );

$page = 'Front';

if ( isset ( $_GET['meeting'] ) 
	&& preg_match ( '@[0-9]{4}-[0-9]{2}-[0-9]{2}@', $_GET['meeting'] ) ) {
	$page = 'Meeting';
} elseif ( isset ( $_GET['do'] ) ) {
	$page = 'Actions';
} elseif ( isset ( $_GET['admin'] ) ) {
	$page = 'Admin';
} elseif ( isset ( $_GET['page'] ) ) {
	switch ( $_GET['page'] ) {
		case 'ical':
			$page = 'Ical';
			break;
		default:
			$page = 'Front';
			break;
	}
}

require_once ( "includes/pages/$page.php" );

$page = new $page($database, $auth);

require_once ( 'includes/Output.php' );
