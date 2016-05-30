<?php
/*   
 *   pimg - a PHP image storage & processing server
 *   
 *   Copyright (c) 2016-2017, Paris wang <suppersoft@gmail.com>.
 *   All rights reserved.
 *   
 * 
 */

class Application{
	
	private function uploadFile(){
		
	}
	
	private function getFile(){
	}
	
	private function getImage(){
	}
	
	public function run(){
		if(isPost()){
			$this->uploadFile();
		}else{
			$this->getFile();
		}
	}
}