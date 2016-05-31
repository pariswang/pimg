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
	private $rawFileURL = '';
	private $requestURL = '';
	private $requestURI = '';
	
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
		
		$writeToDisk = true;
		do_action( 'uploadFile', array( $this->module, $file['tmp_name'], $this->file_full_name, &$writeToDisk ) );
		
		if ( $writeToDisk && ! move_uploaded_file( $file['tmp_name'], $this->file_full_name ) ){
			return _errorMsg(9);
		}
		
		do_action( 'uploadFileEnd', array( $this->module, $file['tmp_name'], $this->file_full_name ) );
		
		return true;
	}
	
	private function getFile(){
		$this->requestURI = request_uri();
		$this->requestURL = request_raw_uri();
		
		$file = substr( $this->requestURI, strrpos( $this->requestURI, '/' ) + 1 );
		$ext = substr( $file, strrpos( $file, '.' ) + 1 );
		$file = substr( $file, 0, strrpos( $file, '.' ) );
		$fileArr = explode( self::FILEINFO_SEPARATOR, $file );
		$this->rawFileURL = str_replace( $file, $fileArr[0], $this->requestURI );
		
		$fileContent = null;
		do_action( 'getFile', array( $this->requestURL, &$this->rawFileURL, &$fileContent ) );
		
		header( 'Content-Type:' . contentType( $ext ) );
		
		if( $fileContent ){
			echo $fileContent;
		}else{
			if( ! is_file( DIR_ROOT . $this->rawFileURL ) ){
				echo returnErr(102);
				exit;
			}
			
			if( isImage( DIR_ROOT . $this->rawFileURL ) ){
				$this->getImage();
			}else{
				echo File::getFile( DIR_ROOT . $this->rawFileURL );
			}
		}
	}
	
	private function getImage(){
		require_once( LIB_PATH . '/Image/Image.php' );
		$image = new Common_Image( DIR_ROOT . $this->rawFileURL );
		if( !$image->isReady ){
			echo returnErr(10);
			exit;
		}

		$file = substr( $this->requestURI, strrpos( $this->requestURI, '/' ) + 1 );
		$file = substr( $file, 0, strrpos( $file, '.' ) );
		$fileArr = explode( self::FILEINFO_SEPARATOR, $file );
		
		$destSize = isset( $fileArr[1] ) ? $fileArr[1] : array();
		$mode = isset( $fileArr[2] ) ? $fileArr[2] : 'sc';
		$destSize = explode( 'x', $destSize );
		$destSize[0] = isset( $destSize[0] ) ? (int) $destSize[0] : 0;
		$destSize[1] = isset( $destSize[1] ) ? (int) $destSize[1] : 0;

		if(substr($mode,0,2)=='sc'||empty($mode)){
			$scType = substr($mode,2);
			$wPosition = Common_Image::PLACE_WEST;
			$hPosition = Common_Image::PLACE_NORTH;
			switch($scType){
				case 1:
					$wPosition = Common_Image::PLACE_WEST;
					$hPosition = Common_Image::PLACE_NORTH;
					break;
				case 2:
					$wPosition = Common_Image::PLACE_WEST;
					$hPosition = Common_Image::PLACE_SOUTH;
					break;
				case 3:
					$wPosition = Common_Image::PLACE_EAST;
					$hPosition = Common_Image::PLACE_NORTH;
					break;
				case 4:
					$wPosition = Common_Image::PLACE_EAST;
					$hPosition = Common_Image::PLACE_SOUTH;
					break;
			}
			$image->resize($destSize[0], $destSize[1], $wPosition, $hPosition);
		}elseif(substr($mode,0,1)=='s'){
			$image->thumbnail($destSize[0], $destSize[1]);
		}elseif(substr($mode,0,1)=='e'){
			
		}elseif(substr($mode,0,1)=='c'){
			
		}

		$image->show();
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