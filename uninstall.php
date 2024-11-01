<?php
/*
Uninstall procedure (Removes the plugin cleanly)
*/


// Checks if it is accessed from Wordpress Admin
if ( ! function_exists( 'add_action' ) ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
	
}


// Make sure that we are uninstalling
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}



// Delete options from DB
delete_option( 'ppropagator_sites' );
delete_option( 'ppropagator_version' );


// Bye! See you soon!

?>
