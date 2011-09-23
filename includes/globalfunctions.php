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

function gfCallFunction ( $array ) {
	if ( !isset($GLOBALS[$array[0]]) )
		throw new CKException("No such class, '{$array[0]}'.");
	return $GLOBALS[$array[0]]->$array[1]((isset($array[2])?$array[2]:null));
}

function gfLink ( $subpages=null ) {
	$gvPageLocation = './';
	if ( is_null($subpages) ) {
		return $gvPageLocation;
	} else {
		if ( !is_array($subpages) )
			return "$gvPageLocation?page=$subpages";
		$tmp = '';
		foreach ( $subpages as $var => $value ) {
			if ( $tmp != '' ) $tmp .= '&amp;';
			$tmp .= "$var=$value";
		}
		return "$gvPageLocation?$tmp";
	}
}

function gfTestFlag ( $flag, $flags ) {
	return ($flag & $flags) == $flag;
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
