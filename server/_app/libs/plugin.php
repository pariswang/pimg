<?php
/*   
 *   pimg - a PHP image storage & processing server
 *   
 *   Copyright (c) 2016-2017, Paris wang <suppersoft@gmail.com>.
 *   All rights reserved.
 *   
 * 
 */

global $pimg_actions;

if ( ! isset( $pimg_actions ) ){
	$pimg_actions = array();
}

function add_action( $tag, $function_to_add, $priority = 10 ) {
	global $pimg_actions;
	
	$pimg_actions[$tag][$priority][] = array(
		'function' => $function_to_add
	);
	return true;
}

function do_action($tag, $args) {
	global $pimg_actions;
	
	if ( !isset($pimg_actions[$tag]) ) {
		return;
	}

	ksort($pimg_actions[ $tag ] );
	reset( $pimg_actions[ $tag ] );

	do {
		foreach ( (array) current($pimg_actions[$tag]) as $the_ ){
			if ( !is_null($the_['function']) ){
				call_user_func_array($the_['function'], $args);
			}
		}
	} while ( next($pimg_actions[$tag]) !== false );
}

function plugin_sandbox_scrape( $plugin ) {
	include( $plugin );
}



ob_start();
if( $pluginsDir = opendir( PLUGIN_PATH ) ){
	while( false !== ( $onePlugin = readdir( $pluginsDir ) ) ){
		if( $onePlugin == '.' || $onePlugin == '..' ){
			continue;
		}
		
		if( is_dir( PLUGIN_PATH . $onePlugin ) ){
			$plugin = PLUGIN_PATH . $onePlugin . DIRECTORY_SEPARATOR . 'index.php';
			if( ! is_file( $plugin ) ){
				continue;
			}
			if( ! is_readable( $plugin ) ){
				continue;
			}
			
			plugin_sandbox_scrape( $plugin );
		}
	}
}
ob_end_clean();