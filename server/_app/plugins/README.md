# 插件可以使用的action列表

上传

* uploadFile( $module, $src, $dest, $writeToDisk );
	时机：上传文件时，文件写入前
	参数：模块、源文件、目标文件、是否写入磁盘
* uploadFileEnd
	时机：上传文件完毕，文件已写入
	参数：模块、源文件、目标文件

下载  