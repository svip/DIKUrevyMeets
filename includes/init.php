<?php

require ( 'page.php' );

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

require ( "includes/pages/$page.php" );
