<?php

function gfMsg ( ) {
	global $messages;
	$args = func_get_args();
	if ( !isset($messages[$args[0]]) ) {
		return "&lt;{$args[0]}&gt;";
	}
	$msg = $messages[$args[0]];
	foreach ( $args as $i => $arg ) {
		$msg = str_replace( "\$$i", $arg, $msg );
	}
	return $msg;
}

function gfRawMsg ( ) {
	$args = func_get_args();
	$msg = $args[0];
	foreach ( $args as $i => $arg ) {
		$msg = str_replace( "\$$i", $arg, $msg );
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
