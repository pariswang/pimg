<?php
/*   
 *   pimg - a PHP image storage & processing server
 *   
 *   Copyright (c) 2016-2017, Paris wang <suppersoft@gmail.com>.
 *   All rights reserved.
 *   
 * 
 */

define( 'DEBUG', true );

if( DEBUG ){
	ini_set( "display_errors",   "on" );
	error_reporting( -1 );
}else{
	ini_set( "display_errors",   "off" );
}

define( 'DIR_ROOT', str_replace( "\\", '/', dirname( __FILE__) ) );
define( 'LIB_PATH', DIR_ROOT . '/libs' );
define( 'CONFIG_PATH', DIR_ROOT . '/config' );
define( 'LOG_PATH', DIR_ROOT . '/logs' );
define( 'PLUGIN_PATH', DIR_ROOT . '/plugins' );
define( 'SCRIPT_PATH', DIR_ROOT . '/scripts' );

require_once( 'utils.php' );
require_once( 'Application.class.php' );
require_once( LIB_PATH . '/Logger.class.php' );

$app = new Application();
$app->run();