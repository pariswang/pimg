<?php
ini_set("display_errors",   "on");
error_reporting(E_ALL   ^   E_NOTICE);
date_default_timezone_set('Asia/Shanghai');

define('HTTP_REFERER', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
!$argc && header('Content-Type: text/html; charset=utf-8');

define('DIR_ROOT', str_replace("\\", '/', substr(dirname(__FILE__), 0, -7)));