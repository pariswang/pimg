<?php
/*   
 *   pimg - a PHP image storage & processing server
 *   
 *   Copyright (c) 2016-2017, Paris wang <suppersoft@gmail.com>.
 *   All rights reserved.
 *   
 * 
 */

class Application{
	const FILEINFO_SEPARATOR = '-';
	
	private $module = '';
	private $pathArray = [];
	private $md5 = '';
	private $file_full_name = '';
	private $file_url = '';
	
	function __construct(){
		$this->module = C( 'default_file_dir' );
	}
	
	private function uploadFile(){
		if( count($_FILES) == 1 ){
			$r = $this->uploadOneFile( $_FILES[0] );
			if( isError( $r ) ){
				echo returnErr( $r['error'] );
			}else{
				echo returnOk( array(
					'md5' => $this->md5,
					'url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $this->file_url,
				) );
			}
		}else{
			$this->uploadMultiFile();
		}
	}
	
	private function uploadMultiFile(){
		$results = array();
		$sucCount = 0;
		foreach( $_FILES as $file ){
			$r = $this->uploadOneFile( $file );
			if( isError( $r ) ){
				$results[] = $r;
			}else{
				++$sucCount;
				$results[] = array(
					'error' => 0,
					'md5' => $this->md5,
					'url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $this->file_url,
				);
			}
		}
		
		if( $sucCount > 0 ){
			echo returnOk( $results );
		}else{
			echo returnErr( $results[0]['error'] );
		}
	}
	
	private function uploadOneFile( $file ){
		if( (int) $file['error'] != 0 ){
			return _errorMsg( $file['error'] );
		}
		
		if( ! is_uploaded_file( $file['tmp_name'] ) ) {
			return _errorMsg( 4, '找不到文件：'.$file['name'] );
		}
		
		$ext  = File::getExt( $file['name'] );
		
		if ( $file['size'] > MAX_FILE_SIZE ) {
			return _errorMsg( 8 );
		}
		
		$this->md5 = md5( file_get_contents( $file['tmp_name'] ) );
		
		$date = date('Ymd');
		
		$this->file_full_name = DIR_ROOT . $this->module . DIRECTORY_SEPARATOR . $date . DIRECTORY_SEPARATOR . $this->md5 . '.' . $ext;
		$this->file_url = '/' . $this->module . '/' . $date . '/' . $this->md5 . '.' . $ext;
		
		$dir = dirname($this->file_full_name);
		if ( !is_dir( $dir ) ){
			mkdir( $dir, 0777, true );
		}
		
		if ( ! move_uploaded_file( $file['tmp_name'], $this->file_full_name ) ){
			return _errorMsg(9);
		}
		
		return true;
	}
	
	private function getFile(){
		$this->requestURL = request_uri();
		
		$file = substr( $this->requestURL, strrpos( $this->requestURL, '/' ) + 1 );
		$ext = substr( $file, strrpos( $file, '.' ) + 1 );
		$fileArr = explode( self::FILEINFO_SEPARATOR, $file );
		$rawFileURL = str_replace( $file, $fileArr[0] . '.' . $ext, $this->requestURL );
		
		if( ! is_file( DIR_ROOT . $rawFileURL ) ){
			echo returnErr(102);
			exit;
		}
		
		
	}
	
	private function getImage(){
		
	}
	
	public function run(){
		$this->pathArray = getPathArray();
		
		if( isset( $this->pathArray[1] ) && $this->pathArray[ 1 ] ){
			if( is_dir( DIR_ROOT . $this->pathArray[1] ) ){
				$this->module = $this->pathArray[1];
			}
		}
		
		if( isPost() ){
			$this->uploadFile();
		}else{
			$this->getFile();
		}
	}
}