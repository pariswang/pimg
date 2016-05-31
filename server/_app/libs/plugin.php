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

function add_action( $tag, $function_to_add, $accepted_args = 1, $priority = 10 ) {
	global $pimg_actions;
	
	$pimg_actions[$tag][$priority][] = array(
		'function' => $function_to_add,
		'accepted_args' => $accepted_args
	);
	return true;
}

function do_action($tag, $arg = '') {
	global $pimg_actions;
	
	if ( !isset($pimg_actions[$tag]) ) {
		return;
	}
	
	$args = array();
	if ( is_array($arg) && 1 == count($arg) && isset($arg[0]) && is_object($arg[0]) ){
		$args[] =& $arg[0];
	}else{
		$args[] = $arg;
	}
	
	for ( $a = 2; $a < func_num_args(); $a++ ){
		$args[] = func_get_arg($a);
	}
	
	ksort($pimg_actions[ $tag ] );
	reset( $pimg_actions[ $tag ] );

	do {
		foreach ( (array) current($pimg_actions[$tag]) as $the_ ){
			if ( !is_null($the_['function']) ){
				call_user_func_array($the_['function'], array_slice($args, 0, (int) $the_['accepted_args']));
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