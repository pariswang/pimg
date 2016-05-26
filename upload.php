<?php
require_once(LIB_PATH . '/Upload.class.php');

$module = '_data';
if($pathArray[1]){
	if(is_dir(DIR_ROOT.'/'.$pathArray[1])){
		$module = $pathArray[1];
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
	
	$result[] = array(
		'error' => 0,
		'md5' => $md5,
		'url' => $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/'.$module.'/'.date('Ymd').$md5.'.'.$ext,
	);
}

if(count($result)==1){
	echo json_encode($result[0]);
}else{
	echo json_encode($result);
}