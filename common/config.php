<?php
ini_set("display_errors",   "on");
error_reporting(E_ALL   ^   E_NOTICE);
date_default_timezone_set('Asia/Shanghai');

define('HTTP_REFERER', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
!$argc && header('Content-Type: text/html; charset=utf-8');

define('DIR_ROOT', str_replace("\\", '/', substr(dirname(__FILE__), 0, -7)));

define('LIB_PATH', DIR_ROOT.'/libs');
define('COMMON_PATH', DIR_ROOT.'/common');

define('MAX_FILE_SIZE', 1024*1024*10);


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
	101 => '地址错误！',
	102 => '找不到文件',
);	