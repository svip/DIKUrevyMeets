<?php

function gfMsg ( ) {
	global $messages, $backupmessages;
	$args = func_get_args();
	if ( !isset($messages[$args[0]]) ) {
		if ( !isset($backupmessages[$args[0]]) ) {
			return "&lt;{$args[0]}&gt;";
		}
		$msg = $backupmessages[$args[0]];
	} else {
		$msg = $messages[$args[0]];
	}
	foreach ( $args as $i => $arg ) {
		if ( $i == 0 ) continue;
		while ( preg_match('@\$'.$i.'([^0-9]|$)@s', $msg) )
			$msg = preg_replace( '@\$'.$i.'([^0-9]|$)@s', "$arg$1", $msg );
	}
	return $msg;
}

function gfRawMsg ( ) {
	$args = func_get_args();
	$msg = $args[0];
	foreach ( $args as $i => $arg ) {
		if ( $i == 0 ) continue;
		while ( preg_match('@\$'.$i.'([^0-9]|$)@s', $msg) )
			$msg = preg_replace( '@\$'.$i.'([^0-9]|$)@s', "$arg$1", $msg );
	}
	return $msg;
}

function gfGetDB ( ) {
	global $DB;
	return $DB;
}

function gfDBQuery ( $query ) {
	global $gvDatabaseQuery;
	$DB = gfGetDB();
	$i = $gvDatabaseQuery;
	$DB->query ( $query, $i );
	$gvDatabaseQuery++;
	return $i;
}

function gfDBSanitise ( $variable ) {
	if ( is_numeric ( $variable ) ) {
		return $variable;
	}
	while ( $variable != stripslashes($variable) )
		$variable = stripslashes($variable);
	return addslashes($variable);	
}

function gfDBGetResult ( $i ) {
	global $DB;
	return $DB->get_result($i);
}

function gfDBGetNumRows ( $i ) {
	global $DB;
	return $DB->get_num_rows($i);
}
