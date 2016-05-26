<?php

function _errorMsg($err, $errMsg=''){
	global $_ErrorMsgs;
	return array(
		'error' => $err,
		'errorMsg' => $errMsg=='' ? $_ErrorMsgs[$err] : $errMsg
	);
}

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
			$uri = substr($uri,0,$qmpos) ;
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