<?php

function getFile( $requestURL, &$rawFilename, &$content ){
	//var_dump($module, $src, $dest, $writeToDisk );
	//$rawFilename = '/d/20160531/86495707a1e12db3bcc55f4350367a5a1.pdf';
	//$content = file_get_contents( DIR_ROOT . 'd/20160531/86495707a1e12db3bcc55f4350367a5a1.pdf' );
}
add_action( 'uploadFile', 'uploadFile' );
add_action( 'getFile', 'getFile' );