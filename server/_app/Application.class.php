<?php

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