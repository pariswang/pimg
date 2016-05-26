<?php
require_once(LIB_PATH . '/Upload.class.php');

$

$module = 'data';
if($pathArray[0]){
	if(is_dir(DIR_ROOT.'/'.$pathArray)){
		$module = $pathArray;
	}
}


$result = array();

foreach($_FILES as $name => $file){
	if( (int)$file['error'] != 0 ){
		$result[] = _errorMsg($file['error']);
		continue;
	}
	
	if( ! is_uploaded_file($file['tmp_name'])) {
		$result[] = _errorMsg(4, '找不到文件'.$file['name']);
		continue;
	}
	
	$ext  = File::getExt($file['name']);
	
	if ( $file['size'] > MAX_FILE_SIZE) {
		$result[] = _errorMsg(8);
		continue;
	}
	
	$md5 = md5(file_get_contents($file['tmp_name']));
	
	$file_name = DIR_ROOT.'/'.$module.'/'.date('Ymd').'/'.$md5.'.'.$ext;
	
	$dir = dirname($file_name);
	if (!is_dir($dir)) {
		mkdir($dir, 0777, true);	
	}
	
	if (!move_uploaded_file($file['tmp_name'], $file_name)) {
		$result[] = _errorMsg(9);
		continue;
	}
	
	$result[] = _errorMsg(0);
}

echo json_encode($result);