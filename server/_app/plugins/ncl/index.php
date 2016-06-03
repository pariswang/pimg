<?php

function getFile( $requestURL, &$rawFilename, &$content ){
	// 权限验证
	
	// 获取真实文件
	if( strpos( $rawFilename, DIRECTORY_SEPARATOR . 'ncl' . DIRECTORY_SEPARATOR ) === 0 ){
		$rawFilename = str_replace( DIRECTORY_SEPARATOR . 'ncl' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . '_ncl' . DIRECTORY_SEPARATOR, $rawFilename);
	}
	
	// 下载记录
}
function uploadFile( $module, $src, &$dest, &$writeToDisk ){
	if($module=='ncl'){
		$dest = str_replace( DIRECTORY_SEPARATOR . 'ncl' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . '_ncl' . DIRECTORY_SEPARATOR, $dest);
	}
}


add_action( 'uploadFile', 'uploadFile', 1 );
add_action( 'getFile', 'getFile', 1 );
