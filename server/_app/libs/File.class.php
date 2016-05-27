<?php

class File{
	
	private $handle = null;
	
	function __construct( $filename ){
		if( $filename ){
			$this->open( $filename );
		}
	}
	
	public function open( $filename, $flag='r' ){
		$this->handle = @fopen( $filename, $flag );
		return true;
	}
	
	public function close(){
		@fclose( $this->handle );
	}
	
	public function readLine(){
		return @fgets( $this->handle );
	}
	
	public function write( $data ){
		return @fwrite( $this->handle, $data );
	}
	
	public static function getFile( $filename ){
		return @file_get_contents( $filename );
	}
	
	public static function getBaseName(){
		return pathinfo($file, PATHINFO_BASENAME);
	}
	
	public static function getDir(){
		return pathinfo($file, PATHINFO_DIRNAME);
	}
	
	public static function getExt(){
		return strtolower(pathinfo($file, PATHINFO_EXTENSION));	
	}
}