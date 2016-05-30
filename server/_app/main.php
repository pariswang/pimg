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

define( 'LIB_PATH', DIR_ROOT . '_app' . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR );
define( 'CONFIG_PATH', DIR_ROOT . '_app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR );
define( 'LOG_PATH', DIR_ROOT . '_app' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR );
define( 'PLUGIN_PATH', DIR_ROOT . '_app' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR );
define( 'SCRIPT_PATH', DIR_ROOT . '_app' . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR );

require_once( 'utils.php' );
require_once( 'error.php' );
require_once( 'Application.class.php' );
require_once( LIB_PATH . '/Logger.class.php' );

define( 'MAX_FILE_SIZE', C( 'max_file_size' ) );

$app = new Application();
$app->run();