# 插件可以使用的action列表

上传

* uploadFile( $module, $src, &$dest, &$writeToDisk );  
	时机：上传文件时，文件写入前  
	参数：模块、源文件、目标文件、是否写入磁盘  
* uploadFileEnd  ( $module, &$src, &$dest );  
	时机：上传文件完毕，文件已写入  
	参数：模块、源文件、目标文件  

下载  

* getFile( $requestURL, &$rawFilename, &$content );
	时机：读取文件前
	参数：请求地址、文件地址、文件内容