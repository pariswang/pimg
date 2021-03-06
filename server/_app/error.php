<?php
/*   
 *   pimg - a PHP image storage & processing server
 *   
 *   Copyright (c) 2016-2017, Paris wang <suppersoft@gmail.com>.
 *   All rights reserved.
 *   
 * 
 */

$_ErrorMsgs = array(
	0 => '',
	1 => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
	2 => '上传文件的大小超过了限制',
	3 => '文件只有部分被上传',
	4 => '没有文件被上传',
	5 => '找不到临时文件夹',
	6 => '文件写入失败',
	7 => '不允许的文件类型',
	8 => '文件大小超过限制',
	9 => '文件成功上传,但是保存失败',
	10 => '未知错误',
	101 => '地址错误！',
	102 => '找不到文件',
);

function _errorMsg($err, $errMsg=''){
	global $_ErrorMsgs;
	return array(
		'error' => $err,
		'errorMsg' => $errMsg=='' ? $_ErrorMsgs[$err] : $errMsg
	);
}

function isError($rt){
	if( $rt === true ){
		return false;
	}
	return isset($rt['error']) && $rt['error'] > 0;
}