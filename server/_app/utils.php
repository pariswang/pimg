<?php
/*   
 *   pimg - a PHP image storage & processing server
 *   
 *   Copyright (c) 2016-2017, Paris wang <suppersoft@gmail.com>.
 *   All rights reserved.
 *   
 * 
 */


function request_raw_uri(){
	if (isset($_SERVER['REQUEST_URI'])){
		$uri = $_SERVER['REQUEST_URI'];
	}else{
		if (isset($_SERVER['argv'])){
			 $uri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['argv'][0];
		}else{
		 	 $uri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING'];
	    }
	}
	if(empty($uri))$uri = '/';
	return $uri;
}

function request_uri(){
	$uri = request_raw_uri();
	if(!empty($uri)){
		$qmpos = strpos($uri,"?");
		if(!empty($qmpos)){
			$uri = substr($uri,0,$qmpos);
		}
	}
	return $uri;
}

function getPathArray(){
   $pathArray = request_uri();
	if ($pathArray === '/'){
		$pathArray = array();
	}else{
		$pathArray = explode('/', $pathArray);
	}
  return $pathArray;
}

function isPost() {
	return $_SERVER ['REQUEST_METHOD'] === 'POST' ? TRUE : FALSE;
}

function returnOk( $data = array(), $format = 'json' ){
	return json_encode( array( 'res' => 0, 'data' => $data ) );
}

function returnErr( $err, $msg = '', $format = 'json' ){
	if( $msg == '' ){
		$msg = _errorMsg( $err );
		$msg = $msg['errorMsg'];
	}
	return json_encode( array( 'res' => $err, 'msg' => $msg ) );
}

function isImage( $file ){
	$imgInfo = getimagesize( $file );
	if( ! $imgInfo ){
		return false;
	}
	$type = strtolower( image_type_to_extension( $imgInfo[2], false ) );
	
	$allowImageTypes = C( 'allow_image_type' );
	$allowImageTypes = explode( ',', $allowImageTypes );
	foreach( $allowImageTypes as &$t ){
		$t = trim( $t );
	}
	 if( ! in_array( $type, $allowImageTypes ) ){
		return false;
	}
	return true;
}


require_once( LIB_PATH . 'ConfigReader.class.php' );
function C( $name ){
	if( empty( ConfigReader::$_cache ) ){
		ConfigReader::init();
	}
	
	return ConfigReader::fetch( $name );
}