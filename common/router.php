<?php

$pathArray = getPathArray();

if(isPost()){
	require_once(DIR_ROOT.'/upload.php');
}else{
	require_once(DIR_ROOT.'/get.php');
}
exit;