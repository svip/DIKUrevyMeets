<?php

require ( 'page.php' );

if ( isset ( $_GET['meeting'] ) 
	&& preg_match ( '@[0-9]{4}-[0-9]{2}-[0-9]{2}@', $_GET['meeting'] ) ) {
	require ( 'includes/pages/Meeting.php' );
} elseif ( isset ( $_GET['do'] ) ) {
	require ( 'includes/pages/Actions.php' );
} elseif ( isset ( $_GET['admin'] ) ) {
	require ( 'includes/pages/Admin.php' );
} else {
	require ( 'includes/pages/Front.php' );
}
