<?php
require_once( 'File.class.php' );

class ConfigReader{
	
	const FILENAME = 'config';
	const EXT = '.json';
	public static $_cache = null;
	
	public static function init( $env = '' ){
		if( $env != '' ){
			$env = '.' . $env;
		}
		
		$file = self::FILENAME . $env . self::EXT;
		self::readConfig( $file );
	}
	
	public static function readConfig( $file ){
		$source = '';
		
		$f = new File( CONFIG_PATH . '/' . $file );
		while( ( $line = $f->readLine() ) !== false ){
			$pos = strpos( $line, '//' );
			if( false !== $pos ){
				$line = substr( $line, 0, strpos( $line, '//' ) );
			}
			$source .= trim( $line );
		}
		$f->close();
		
		self::$_cache = json_decode( $source, true );
	}
	
	public static function fetch( $name ){
		return self::$_cache[ $name ];
	}
}