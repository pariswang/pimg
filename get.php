<?php

$module = $pathArray[1];
if(!is_dir(DIR_ROOT.'/'.$module)){
	echo json_encode(_errorMsg(101));
	exit;
}

$file = $pathArray[2];
$dir = substr($file, 0, 8);
if(!is_dir(DIR_ROOT.'/'.$module.'/'.$dir)){
	echo json_encode(_errorMsg(101));
	exit;
}

list($file, $type) = explode('.', substr($file,8));
$format = explode('-', $file);
$md5 = $format[0];
unset($format[0]);
if(!is_file(DIR_ROOT.'/'.$module.'/'.$dir.'/'.$md5.'.'.$type)){
	echo json_encode(_errorMsg(102));
	exit;
}

require_once(LIB_PATH . '/Image/Image.php');

$image = new Common_Image(DIR_ROOT.'/'.$module.'/'.$dir.'/'.$md5.'.'.$type);
if(!$image->isReady){
	echo json_encode(_errorMsg(10));
	exit;
}

$destSize = $format[1];
$mode = $format[2];
$destSize = explode(',', $destSize);

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