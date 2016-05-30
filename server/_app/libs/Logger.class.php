<?php
/*   
 *   pimg - a PHP image storage & processing server
 *   
 *   Copyright (c) 2016-2017, Paris wang <suppersoft@gmail.com>.
 *   All rights reserved.
 *   
 * 
 */
require_once( LIB_PATH . 'File.class.php' );

Logger::setLogDir( LOG_PATH );

class Logger{
	
	const EXT = '.log';
	const DEBUG = '.debug';
	const INFO = '.info';
	const ERROR = '.error';
	const DEVIDER = '.';
	
	private static $dir;
	private static $prefix = '';
	
	public static function setLogDir( $dir ){
		self::$dir = $dir;
	}
	
	public static function setPrefix( $str ){
		self::$prefix = $str . '_';
	}
	
	private static function lineFormat( $str ){
		return sprintf( "[%s] %s\n", date( 'H:i:s' ), $str );
	}
	
	public static function debug( $str ){
		$filename = self::$dir . DIRECTORY_SEPARATOR  . self::$prefix . date( 'Y_m_d' ) . self::DEBUG . self::EXT;
		$line = self::lineFormat( $str );
		File::appendFile( $filename, $line );
	}
	
	public static function info( $str ){
		$filename = self::$dir . DIRECTORY_SEPARATOR  . self::$prefix . date( 'Y_m_d' ) . self::INFO . self::EXT;
		$line = self::lineFormat( $str );
		File::appendFile( $filename, $line );
	}
	
	public static function err( $str ){
		$filename = self::$dir . DIRECTORY_SEPARATOR  . self::$prefix . date('Y_m_d') . self::ERROR . self::EXT;
		$line = self::lineFormat( $str );
		File::appendFile( $filename, $line );
	}
}