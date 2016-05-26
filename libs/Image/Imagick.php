<?php
require_once('Abstract.php');
/**
 * @desc 对Linux的ImageMagick进行扩展，对图片进行处理 (需要Linux安装ImageMagick)
 * http://www.1024i.com  学习网站
 * @author Bear
 * @copyright xiqiyanyan.com
 * @version 2.0.0 2012-07-16 14:16
 * @created 2012-06-30 09:49
 */
class Common_Imagick extends Common_Image_Abstract
{
	/**
	 * 允许的图片类型
	 * @var array
	 */
    private $_allowExt = array('.gif', '.jpg', '.jpeg', '.png', '.bmp');
    
    /**
     * 分解gif图片 (GIF动画为了压缩，会以第一帧为模板，其余各帧按照适当的偏移量依次累加，并只保留不同的像素，结果是导致各帧尺寸不尽相同)
     * @param string $targetFolder 分解后保存的文件夹（目录） (后面可以带/，也可以不带)
     * @param string $prefix 生成gif图片的前缀名
     */
    public function gifDecay($targetFolder, $prefix = '') {
        if (!$this->_im) {
            return false;
        }
        $targetFolder = $this->formatPath($targetFolder);
        $i = 0;
        foreach ($this->_im as $image) {
            $i++;
            $targetPath = $targetFolder . $prefix . $i . '.gif';
            $image->writeImage($targetPath);
        }
    }
    
    /**
     * 等比例生成gif缩略图
     * gif缩略图暂时不提供变形压缩和裁剪
     * 如果源图片尺寸比目标图片尺寸还要小，将等比例拉伸
     * 可按一边等比例缩放
     * @param integer $width 缩略图的宽
     * @param integer $height 缩略图的高
     * @param boolean $constrain 是否等比例缩放
     * @return boolean
     */
    public function gifThumbnail($width, $height, $constrain = true) {
        if (!$this->_isExistIm()) {
            return false;
        }
        $this->_im = $this->_im->coalesceImages(); // coalesceimages 确保各帧尺寸一致，详见手册 Imagick::coalesceImages
        if (!$width || !$height) {
            $constrain = false;
        }
        foreach ($this->_im as $frame) { /* Resize all frames */
            $frame->thumbnailImage($width, $height, $constrain); // 这里设为false时，才能另一边传0不报错，才能实现按一边等比例缩放
        }
        $this->_im = $this->_im->optimizeImageLayers(); // optimizeImageLayers 它删除重复像素内容，也可以参考下手册
        // 注意：不管是coalesceimages，还是optimizeImageLayers，都是返回新的Imagick对象！
        return true;
        // 如果要求更完美一点，可以使用quantizeImages方法进一步压缩(不知道怎么用)
    
    
        // exec("convert $dir -coalesce $dir"); // 对gif进行整合 (不知道这句话什么意思)
        // exec("convert -size $width"."x"."$height $dir -esize $max_width"."x"."$max_height $dir"); (这句话也没有来得及测试)
        //     	$str = "convert $this->_sourcePath -coalesce -thumbnail {$width}X{$height} -layers optimize $targetPath";
        //     	exec($str); // 这个命令行是等比例缩略
    
    
        //$im = new Imagick($sourcePath); // Create a new imagick object and read in GIF
        /* foreach ($this->_im as $frame) { // Resize all frames
        $frame->thumbnailImage($width, $height, true);
        $frame->setImagePage($width, $height, 0, 0); //Set the virtual canvas to correct size
        }
        return $this->writes($targetPath); */
        // 后面这个能生成gif动画，但是不是等比例的，貌似不对，还有透明东东
        // Notice writeImages instead of writeImage
    }
    
    /**
     * 载入一张图片
     * @param string $sourcePath 源图片路径
     * @return boolean
     */
    public function read($sourcePath) {
    	if (!is_file($sourcePath)) {
    		$this->_error = '源文件不存在';
    		return false;
    	}
    	try {
            $this->_im = new Imagick($sourcePath); // 其实这里也可以当验证图片类型
            /* Create a new imagick object and read in */
            $this->_sourceType = strtolower($this->_im->getimageformat()); 
            // 如果要设置图片类型用setImageFormat.  注意了：getformat 和 getImageFormat 是不一样的
            // 真的很奇怪， 如果后缀名是jpeg的话，这个地方无法获取到图片类型; 
            // 后来证实是没有安装libpng、libjpeg，其实是支持jpeg的
            // $ext = $this->_im->getImageType(); // 这个也可以获取图片的类型；取值：5：gif；6：bmp
            // 源图片类型，图片本质是gif，如果仅修改后缀成jpg，不会对这个有影响
            if (!in_array('.' . $this->_sourceType, $this->_allowExt)) {
            	$this->_error = '不支持的图片文件格式，只支持 ' . join('、', $this->_allowExt) . ' 格式的图片';
            	$this->_im = null;
            	return false;
            }
            if ($this->_sourceType == self::TYPE_GIF) {
                $this->_im = $this->_im->coalesceImages();
            }
            $widthAndHeight = $this->_im->getImageGeometry(); // 获取图片的宽和高(如果是gif图片，取得的高和宽将是最后一帧的宽和高)
            // Array ( [width] => 588 [height] => 436 )   $this->_im->getimagewidth();获取图片的宽
            // $this->_im->getImagePage(); 获取图片的部分信息，返回如：array{["width"]=>int(500), ["height"]=>int(282), ["x"]=>int(0), ["y"]=>int(0)}
            $this->_sourceWidth = (int) $widthAndHeight['width'];
            $this->_sourceHeight = (int) $widthAndHeight['height'];
            $this->_sourcePath = $sourcePath;
    	} catch (Exception $e) {
            $this->_error = '读取图片文件出错，不支持的图片文件格式';
            $this->_im = null;
            return false;
    	}
    }
    
    /**
     * 生成缩略图（压缩图片）(支持gif动画);如果 $bestfit 为true，宽、高都不会超过给定到值，如果为false，就是固定给定的值
     * 如果只传入来宽或高的一个（另一个传null或0），就会按照宽或高等比例缩放
     * 如果源图片尺寸比目标图片尺寸还要小，将进行拉伸
     * 如果宽和高都不传，将保持源图片的宽和高
     * @param integer $width 目标宽 (一定是整数或null)
     * @param integer $height 目标高 (一定是整数或null)
     * @param boolean $bestfit 是否变形压缩，默认true：不变形，false：变形
     * @return boolean
     * @see Lib_Image_Abstract::thumbnail
     */
    public function thumbnail($width = null, $height = null, $bestfit = true) {
    	if (!$this->_isExistIm()) {
    		return false;
    	}
    	if ($width == null && $height == null) {
    		return true;
    	}
    	if ($this->_sourceType == self::TYPE_GIF) {
    		return $this->gifThumbnail($width, $height, $bestfit);
    	}
    	if (!$width || !$height) {
    		$bestfit = false;
    	}
    	return $this->_im->thumbnailimage($width, $height, $bestfit);
    }

    /**
     * 裁剪图片(gif动画也能裁剪)
     * @param integer $width 目标宽，不能为负数(如果裁剪的宽和高都不传，将取原图片的宽和高)
     * @param integer $height 目标高，不能为负数（如果裁剪（目标）的宽或高只传一个，另一个将取原图片的）
     * @param integer $x 裁剪位置x轴，可以为负数；如为负数则在坐标轴原点的左边
     * @param integer $y 裁剪位置y轴，可以为负数；其图片左上角为坐标轴原点（0，0）
     * @return boolean
     */
    public function crop($width = 0, $height = 0, $x = 0, $y = 0) {
        /* if ($this->_sourceWidth <= $x || $this->_sourceHeight <= $y) {
        	$this->_error = '裁剪坐标大于源图片的宽或高，无法进行裁剪！';
        	$this->_im = null;
            return false;
        } */
    	if (!$this->_isExistIm()) {
    		return false;
    	}
        $width = (int) $width;
        $height = (int) $height;
        $x = (int) $x;
        $y = (int) $y;
        if (!$width) {
            $width = $this->_sourceWidth;
        }
        if (!$height) {
            $height = $this->_sourceHeight;
        }
        if ($width < 1) {
        	$this->_error = '裁剪的宽度不能小于1';
        	$this->_im = null;
            return false;
        }
        if ($height < 1) {
        	$this->_im = null;
            $this->_error = '裁剪的高度不能小于1';
            return false;
        }
        if ($this->_sourceType == self::TYPE_GIF) {
            $this->_im = $this->_im->coalesceImages(); // 确保每帧的宽度和高度一致
            foreach ($this->_im as $frame) {
                $frame->cropImage($width, $height, $x, $y);
                $frame->setImagePage($frame->getImageWidth(), $frame->getImageHeight(), 0, 0); /* Set the virtual canvas to correct size */
            }
            return true;
    
            /* $unitl = $image->getNumberImages (); // 貌似提速gif处理 不知道什么意思
            for($i = 0; $i < $unitl; $i ++) {
                $image->setImageIndex ( $i );
                $image->compositeImage ( $numimage, Imagick::COMPOSITE_OVER, 0, 0 );
                //降低色彩量化的质量
                $image->quantizeImage(256, imagick::COLORSPACE_RGB, 4, false, false);
            } */
        } else {
            $boolean = $this->_im->cropImage($width, $height, $x, $y);
            if ($boolean) {
                return $this->_im->setimagepage($this->_im->getimagewidth(), $this->_im->getimageheight(), 0, 0);
                // 重置图片的显示信息，删除多余的透明层
            } else {
                $this->_error = '裁剪图片出错';
                $this->_im = null;
                return false;
            }
        }
    }

    /**
     * 添加文字水印（不支持gif动画，操处理gif图片有点慢）
     * 此函数依赖getDrawForText函数
     * @param string $text 水印文字
     * @param string $srcPath 需要添加水印图片的绝对路径
     * @param string $dstPath 打好水印后保存文件路径，如果想覆盖原图片，设成和源图片路径一样就OK
     * @param string $place 水印位置，详见常量  TODO 未完成
     * @param integer $x 水印位置（也是偏移量）
     * @param integer $y 水印位置（也是偏移量）
     * @param integer $angle 水印旋转角度，正数顺时针，负数逆时针
     * @param array $style 水印文字样式
     * $style['fontFile'] 水印文字字体 ； 如：APPLICATION_PATH . '/../public/Fonts/msyhbd.ttf'
     * （如果水印文字有中文，必须要中文字体才能支持，不然会有乱码）
     * $style['fontSize'] 水印文字的大小，默认15
     * $style['fillColor'] 水印文字的颜色，默认黑色 #000000
     * $style['fillOpacity'] 水印文字的透明度，取值范围0(全透明)-1(完全不透明);默认：0.58 半透明 
     * $style['underColor'] 水印文字背景色，默认无
     * @return boolean
     */
    public function watermarkText($text, $place = null, $x = 15, $y = 25, $angle = 0, array $style = array()) {
        if (!$this->_isExistIm()) {
            return false;
        }
        $font = isset ( $style ['fontFile'] ) ? $style ['fontFile'] : null;
        $size = isset ( $style ['fontSize'] ) ? $style ['fontSize'] : 15;
        $color = isset ( $style ['fillColor'] ) ? $style ['fillColor'] : 'black';
        // $pixel = new ImagickPixel('gray');
        // $draw->setfillcolor($pixel);
        $opacity = isset ( $style ['fillOpacity'] ) ? $style ['fillOpacity'] : 0.58;
        $underColor = isset ( $style ['underColor'] ) ? $style ['underColor'] : null;
        $draw = $this->getDrawForText ( $font, $size, $color, $opacity, $underColor );
        if (!$draw) {
            $this->_im = null;
            return false;
        }
        if ($place !== null) {
            $watermarkWH = $this->_im->queryfontmetrics ( $draw, $text );
            switch ($place) {
                case self::PLACE_NORTHWEST : break;
                case self::PLACE_NORTHEAST :
                    $x = intval( $this->_sourceWidth - ($watermarkWH ['textWidth'] + $x) ); break;
                case self::PLACE_CENTER    :
                    $x = ( int ) ($this->_sourceWidth - $watermarkWH ['textWidth']) / 2;
                    $y = ( int ) ($this->_sourceHeight - $watermarkWH ['textHeight']) / 2; break;
                case self::PLACE_SOUTHWEST :
                    $y = intval( $this->_sourceHeight - ($watermarkWH ['textHeight']+$y) ); break;
                default :
                    $x = intval( $this->_sourceWidth - ($watermarkWH ['textWidth'] + $x) );
                    $y = intval( $this->_sourceHeight - ($watermarkWH ['textHeight'] + $y) ); break;
            }
        }
        if ($this->_sourceType == self::TYPE_GIF) { // 处理gif
            $this->_im = $this->_im->coalesceimages();
            $boolean = true;
            foreach ($this->_im as $frame) {
                $flag = $frame->annotateimage($draw, $x, $y, $angle, $text);
                if (!$flag) {
                    $boolean = false;
                    $this->_im = null;
                    $this->_error = '处理图片出错';
                    break;
                }
            }
            return $boolean;
        } else {
            return $this->_im->annotateimage($draw, $x, $y, $angle, $text);
            // TODO 不知道为什么，在ubuntu 32位系统上没有解决中文字乱码的问题.真想不明白背景色也没有用
        }

        // 还有下面的方法：
        // $draw->setGravity(Imagick::GRAVITY_SOUTHEAST); //设置水印位置
        // (特别注意了，原生态的打水印的位置很好用哦)
        // $draw->annotation(0, 0, 'xiqiyanyan.com');
        // $canvas->drawImage($draw);
    }   
    
    /**
     * 添加图片水印 
     * 有个别水印图片打不漂亮；貌似对jpg图片打出来还不错
     * @see Lib_Image_Abstract::watermarkImage()
     * @param string $watermarkPath 水印图片(最好是透明背景的png图片)
     * @param string $place 水印位置；具体对应参数如下（可参见常量）
     *         northwest            northeast 
     *                     center
     *         southwest            southeast
     * @param integer $x 水印位置 ； x（水平坐标轴）轴偏移量
     * @param integer $y 水印位置 ； y（垂直坐标轴）轴偏移量
     * @param integer $alpha 水印透明度。取值0（全透明）-100（完全不透明） ；默认52：半透明
     * @param integer $angle 水印图片旋转角度  TODO
     * @return boolean
     */
    public function watermarkImage($watermarkPath, $place = 'southeast', $x = 18, $y = 18, $alpha = 52, $angle = 0) {
        if (!is_file($watermarkPath)) {
            $this->_error = '水印文件不存在';
            $this->_im = null;
            return false;
        }
        if (!$this->_isExistIm()) {
            return false;
        }
        try {
            $watermark = new Imagick();
            $watermark->readImage($watermarkPath);
            $alpha = $alpha/100; 
            $watermark->setImageOpacity($alpha); // 设置透明度，不知道为什么这里如果设了透明度，有些png水印打出来的图片就不漂亮了
            // 在ubuntu 64 位上还是一样
            $watermarkW = $watermark->getimagewidth();
            $watermarkH = $watermark->getimageheight();
            switch ($place) {
                case self::PLACE_NORTHWEST : break;
                case self::PLACE_NORTHEAST : $x = $this->_sourceWidth-($watermarkW+$x); break;
                case self::PLACE_CENTER    : $x = intval(($this->_sourceWidth-$watermarkW)/2); 
                                               $y = intval(($this->_sourceHeight-$watermarkH)/2); break;
                case self::PLACE_SOUTHWEST : $y = $this->_sourceHeight-($watermarkH+$y); break;
                case self::PLACE_SOUTHEAST : $x = $this->_sourceWidth-($watermarkW+$x);
                                               $y = $this->_sourceHeight-($watermarkH+$y);break;
                default : $x = $this->_sourceWidth-($watermarkW+$x); $y = $this->_sourceHeight-($watermarkH+$y); break;
            }
            $compose = $watermark->getImageCompose();
            // 方法一
            //return $this->_im->compositeimage($watermark, $compose, $x, $y);
            // 方法二
            $draw = new ImagickDraw();
            $draw->composite($compose, $x, $y, $watermarkW, $watermarkH, $watermark);
            if ($this->_sourceType == self::TYPE_GIF) {
                $this->_im = $this->_im->coalesceImages();
                $flag = true;
                foreach ($this->_im as $frame) {
                    $boolean = $frame->drawImage($draw);
                    if (!$boolean) {
                        $flag = false;
                        $this->_im = null;
                        $this->_error = '添加水印出错！';
                        break;
                    }
                }
                return $flag;
            }
            return $this->_im->drawImage($draw);            
        } catch (Exception $e) {
            $this->_error = '处理水印图片出错';
            $this->_im = null;
            return false;
        }
    }

    /**
     * 写入图片(gif动画也可以写入)
     * @param string $targetPath 目标路径（只能是绝对路径）
     * @param boolean $flag  是否覆盖已存在的图片；默认true：覆盖；false：不覆盖
     * @return boolean
     */
    public function write($targetPath = null, $flag = true) { // 貌似实现抽象方法时，参数个数只能多，不能少(可能也是php的bug，不会报错)，且前面的参数的默认值需一致
        if ($targetPath != null) {
            $this->_targetPath = str_replace('\\', '/', $targetPath);
        }
        if ($this->_targetPath == null) {
        	$this->_error = '生成图片的目标路径不能为空';
        	return false;
        }
        if (!$this->_isExistIm()) {
        	return false;
        }
        // 下面检测路径是否有写的权限，和创建没有的文件夹
        $directory = pathinfo($this->_targetPath, PATHINFO_DIRNAME); // pathinfo 默认返回所有文件路径信息； 这里返回文件夹信息（返回目录部分）
        $boolean = $this->createdDirectory($directory);
        if (!$boolean) {
        	return false;
        }
        // 下面判断是否覆盖已有的文件，已存在是否有写权限
        if (is_file($this->_targetPath)) {
        	if ($flag) {
        		if (!is_writeable($this->_targetPath)) {
        			$this->_error = '图片文件 ' . $this->_targetPath . ' 已存在且不可写！无法保存图片文件！';
        			return false;
        		}
        	} else {
        		$this->_error = '目标文件已存在';
        		return false;
        	}
        }
        // 判断已存在的目录是否可写
        $dir = pathinfo($this->_targetPath, PATHINFO_DIRNAME); // pathinfo 默认返回所有文件路径信息； 这里返回文件夹信息（返回目录部分）
        if (!is_writable($dir)) {
        	$this->_error = '目录 ' . $dir . ' 不可写！无法保存图片！';
        	return false;
        }
        // 是否是支持的文件类型
        $ext = strtolower(pathinfo($this->_targetPath, PATHINFO_EXTENSION)); // 图片类型
        if (!in_array('.' . $ext, $this->_allowExt)) {
        	$this->_error = '不支持的图片文件格式，无法保存图片；只支持 ' . join('、', $this->_allowExt) . ' 格式的图片';
        	return false;
        }
        // 写入图片
        if ($this->_sourceType == self::TYPE_GIF) { // gif 类型
        	// 如果目标路径图片的类型和源图片不一致，需要进行重命名处理
        	$flag = false;
        	$ext = strrchr($this->_targetPath, '.');
        	if (strtolower($ext) != '.' . self::TYPE_GIF) {
        		$flag = true;
        		$oldFileName = $this->_targetPath;
        		$this->_targetPath = str_replace($ext, '.' . self::TYPE_GIF, $this->_targetPath);
        	}
            $boolean = $this->_im->writeImages($this->_targetPath, true);
            if ($boolean) {
            	if ($flag) { // 重命名
            		if (file_exists($this->_targetPath)) { // 这句多余的判断
                        if (rename($this->_targetPath, $oldFileName)) {
                        	$this->_targetPath = $oldFileName;
                        	return true;
                        } else {
                        	$this->_error = '无法重命名文件';
                        	return false;
                        }
            		}
            	}
            	return true;
            } else { 
            	$this->_error = '写入文件出错';
            	return false;
            }
        } else { // 其他类型
            if ($this->_sourceType != self::TYPE_BMP) {
                $this->_im->setImageCompressionQuality($this->_quality); // 设置图片质量
                // 图片质量 （只对jpg和png有用，取值0-100，jpg 值越大质量越好文件越大，png 反之，不过png影响不大,貌似png有问题，这里最好不要传，默认88就好）
            }
            return $this->_im->writeImage($this->_targetPath); // Write the image to disk
        }
    }
    
    /**
     * 输出图像到浏览器
     * @see Lib_Image_Abstract::show()
     */
    public function show() {
        switch ($this->_sourceType) {
            case self::TYPE_GIF  : $imgType = 'image/gif'; break;
            case self::TYPE_JPEG : 
            case self::TYPE_JPG  : $imgType = 'image/jpeg'; break;
            case self::TYPE_BMP  : $imgType = 'image/bmp'; break;
            default : $imgType = 'image/png'; break;
        }
        header('Content-Type:' . $imgType);
        if ($this->_sourceType == self::TYPE_GIF) {
            echo $this->_im->getimagesblob();
        } else {
            echo $this->_im->getimageblob();
        }
    }
    
    /**
     * 水平翻转(左右翻转)或垂直翻转(上下翻转)
     * 支持gif图片
     * @see Lib_Image_Abstract::rotateHV()
     * @param string $hv 默认H：水平翻转；V：垂直翻转
     * @return boolean
     */
    public function rotateHV($hv = 'H') {
        if (!$this->_isExistIm()) {
            return false;
        }
        if ($this->_sourceType == self::TYPE_GIF) {
            $this->_im = $this->_im->coalesceImages(); // 确保每帧的宽度和高度一致
            $boolean = true;
            foreach ($this->_im as $frame) {
                $flag = $this->_rotate($frame, $hv);
                if (!$flag) {
                    $boolean = false;
                    break;
                }
                //$frame->setImagePage($frame->getImageWidth(), $frame->getImageHeight(), 0, 0);
            }
            if ($boolean) {
                return true;
            } else {
                $this->_im = null;
                return false;
            }
            return true;
        }
        return $this->_rotate($this->_im, $hv);
    }
    
    /**
     * 水平翻转(左右翻转)或垂直翻转(上下翻转)
     * @param Imagick $im
     * @param string $hv
     * @return boolean
     */
    private function _rotate(&$im, $hv) {
        if (self::ROTATE_H == $hv) {// 水平翻转
            $boolean = $im->flopImage();
        } elseif(self::ROTATE_V == $hv) {// 垂直翻转
            $boolean = $im->flipImage();
        } else {
            $this->_error = '参数错误';
            $this->_im = null;
            return false;
        }
        if ($boolean) {
            return true;
        } else {
            $this->_error = '翻转失败';
            $this->_im = null;
            return false;
        }
    }  
    
    /**
     * 任意角度旋转图片
     * @see Lib_Image_Abstract::rotate()
     * @param integer $angle 旋转角度，正数顺时针，负数逆时针
     * @param string $color 如果旋转后有空的地方用什么颜色填充，默认全透明（要想透明，文件类型必须为png，不然就成黑色了）
     * 可以为颜色单词，如：white或pink；可以为字符串，如：#FFF或#FFF000
     * @return boolean
     */
    public function rotate($angle, $color = 'transparent') {
        if (!$this->_isExistIm()) {
            return false;
        }
        return $this->_im->rotateImage(new ImagickPixel($color), $angle); // 其实 new ImagickPixel() 建的是黑色背景到图片，并不是透明色
    }
    
    /**
     * 等比例缩放图片后进行裁剪，即生成指定大小的图片，多余部分进行裁剪
     * @see Lib_Image_Abstract::resize()
     * @param integer $width 目标图片的宽(如果目标宽和高只传一个【另一个传null或0】将按等比例缩放)
     * @param integer $height 目标图片的高(如果目标宽和高只传一个【另一个传null或0】将按等比例缩放)
     * @param string $tooWideCutPosition 如果缩率图的宽大于目标宽，进行裁剪的位置，取值见常量。默认center：居中裁剪；west：居左裁剪；east：居右裁剪
     * @param string $tooHighCutPosition 如果高超出设定值，需要从哪个位置开始裁剪，取值见常量PLACE_NORTH等。默认center:居中;north:上面;south:下面;
     * @return boolean
     */
    public function resize($width, $height, $tooWideCutPosition = 'center', $tooHighCutPosition = 'center') {
        if (!$this->_isExistIm()) {
            return false;
        }
        $width = (int) $width;
        $height = (int) $height;
        if ($width < 0 || $height < 0) {
            $this->_error = '目标宽或高不能小于0';
            $this->_im = null;
            return false;
        }
        if (!$width && !$height) {
            return true;
        }
        if (!$width || !$height) { // 只传入了宽或高
            return $this->thumbnail($width, $height);
        }
        
        // 方法一：先缩略，后裁剪
        /* $ratio = $this->getProportionality($this->_sourceWidth, $this->_sourceHeight, $width, $height, 'small');
        if (!$ratio) {
            return false;
        }
        $newWidth = floor($this->_sourceWidth*$ratio);
        $newHeight = floor($this->_sourceHeight*$ratio);
        $boolean = $this->thumbnail($newWidth, $newHeight);
        if (!$boolean) {
            return false;
        }//return true;
        $x = 0;
        $y = 0;
        if ($width < $newWidth) { // 宽多了
            switch ($tooWideCutPosition) {
                case self::PLACE_EAST: $x = (int) ($newWidth-$width); break;
                case self::PLACE_WEST: break;
                default : $x = (int) ($newWidth-$width)/2; break;
            }
        }
        if ($height < $newHeight) { // 高多了
            switch ($tooHighCutPosition) {
                case self::PLACE_NORTH : break;
                case self::PLACE_SOUTH : $y = (int) ($newHeight-$height); break;
                default : $y = (int) ($newHeight-$height)/2; break;
            }
        }//var_dump($x);var_dump($y);exit;
        return $this->crop($width, $height, $x, $y); */
        
        // 方法二：先裁剪，后缩略
        $cutX = 0;
        $cutY = 0;
        $cutW = $this->_sourceWidth;
        $cutH = $this->_sourceHeight;
        if ($this->_sourceWidth*$height > $this->_sourceHeight*$width) { // 需裁宽
            $cutW = intval($this->_sourceHeight*$width/$height);
        } else { // 需裁高
            $cutH = intval($this->_sourceWidth*$height/$width);
        }
        switch ($tooWideCutPosition) {
            case self::PLACE_WEST : break;
            case self::PLACE_EAST : $cutX = (int) ($this->_sourceWidth-$cutW); break;
            default: $cutX = (int) ($this->_sourceWidth-$cutW)/2; break;
        }
        switch ($tooHighCutPosition) {
            case self::PLACE_NORTH: break;
            case self::PLACE_SOUTH: $cutY = intval($this->_sourceHeight-$cutH); break;
            default : $cutY = intval(($this->_sourceHeight-$cutH)/2); break;
        }
        $boolean = $this->crop($cutW, $cutH, $cutX, $cutY);
        if ($boolean) {
            
        } else {
            return false;
        }
        return $this->thumbnail($width, $height);
        //convert /home/xiqiyanyan/www/phpBase/img/ddd.jpg -gravity east -crop 300X300+0+0 +repage /home/xiqiyanyan/www/phpBase/img/west.png
        //$convert = "convert $sourcePath -crop {$cropW}X{$cropH}+{$cropX}+{$cropY} +repage $targetPath";
        //$str = "convert $targetPath -coalesce -thumbnail {$width}X{$height} -layers optimize $targetPath";
    }
    
    /**
     * 获取文字的描绘对象
     * @param string $font 字体名称或字体路径
     * @param integer $size 字体大小
     * @param string | ImagickPixel $color 字颜色
     * @param float $opacity 文字的透明度，取值范围0（全透明）-1（不透明）
     * @param string | ImagickPixel $underColor 字的背景颜色
     * @return ImagickDraw | false
     */
    public function getDrawForText($font = null, $size = 8, $color = 'transparent', $opacity = 0.58, $underColor = null) {
        $draw = new ImagickDraw ();
        // $draw->setGravity(Imagick::GRAVITY_CENTER);
        // 特别注意这里了，如果这里设置了文字水印的位置的话，那么在其写入别到文件时就会起作用，且其他设置的坐标都没有用
        if ($font !== null) {
            if (is_file($font)) {
                $draw->setfont ( $font );
            } else {
                $this->_error = '字体文件不存在';
                return false;
            }
        }
        $draw->setfontsize ( $size );
        $draw->setFillColor ( $color );
        $draw->setfillopacity ( $opacity ); // 貌似和这个是一样的： $draw->setFillAlpha(0.5);
        if ($underColor !== null) {
            $draw->settextundercolor ( $underColor );
        }
        // $draw->settextalignment(2); //文字对齐方式，2为居中
        return $draw;
    }
    
    /**
     * 等比例缩略图，留空的地方用其他颜色填充(这个函数貌似运行很慢，要1秒钟)   ( 特别注意了， 这里因为需求不大所以就没有改，这个还不能用)
     * TODO GIF动画考虑
     * @param string $source 源图片
     * @param string $target 目标图片
     * @param integer $width 缩略图的长 Define width and height of the thumbnail
     * @param integer $height 缩略图的宽
     * @param mixed $color 颜色 (默认白色)。 这个参数可以传入如下形式的参数：
     *         string 'pink'
     *         string '#FFF'
     *         object new ImagickPixel('red');
     * @return boolean
     */
    public function thumbnailAndStuffColour($width, $height, $color = 'white') {
        $this->thumbnail($width, $height, true); // 等比例缩放
        $im = new Imagick (); // Instanciate and read the image in
        $im->newimage ( $width, $height, $color, 'png' ); // 按照缩略图大小创建一个有颜色的图片 .  pink : 粉红， red：红色，gray：灰色
        //$im->newimage ( $width, $height, $color, 'jpg' ); // 这里的$color可以等于rgba('255(红)','255(绿)','255(蓝)','透明度(0完全透明-127不透明)')
        // 实践证明，无法新建一个半透明背景的图片，只能为0时，全透明，为正数时，不透明
        $geometry = $this->_im->getImageGeometry (); // 取得缩略图的实际大小
        $x = ($width - $geometry ['width']) / 2; // 计算合并坐标
        $y = ($height - $geometry ['height']) / 2;
        $im->compositeImage ( $this->_im, Imagick::COMPOSITE_OVER, $x, $y ); // 合并两图片。 貌似和composite方法一样
        $this->_im = $im;
        return true;
    }
    
    /**
     * 添加一个边框
     * @param integer $leftRight 左右边框宽度
     * @param integer $topBottom 上下边框宽度
     * @param string $color 边框颜色；取值可以为： rgb(255,255,0) 或 '#FF00FF' 或 'white/red' 或 cmyk(100，100，100，10)
     * @return boolean
     */
    public function border($leftRight, $topBottom, $color = 'rgba(220,220,220,127)') { // 透明度貌似在这里没有用
        //$pixel = new ImagickPixel();
        //$pixel->setcolor($color);
        //$color = new ImagickPixel($color);
        return $this->_im->borderimage($color, $leftRight, $topBottom);
        //$this->_im->raiseimage(8, 8, 8, 8, 0);//加半透明边框 ; 
        //哈哈，这个真可以加一个半透明的边框，这个是在里面加了个类似突起的线框, 最后一个参数（boolean）是凸线还是凹线
    }
    
    /**
     * 批量生成某个文件夹下某一种类型图片的缩略图
     * TODO GIF动画
     * @param string $sourcePath
     *            源文件夹路径
     * @param string $targetPath
     *            目标文件夹路径 (注意如果保存在同一个目录下，运行两遍就有可能翻倍哦)
     * @param integer $width
     *            缩略图宽 (注意宽或高 一个都不能为零)
     * @param integer $height
     *            缩略图高
     * @param string $type
     *            图片类型，如：jpg，png等 （注意：linux对大小写敏感）
     * @param boolean $isScale
     *            是否等比例缩放，true（默认）：是； false：否
     */
    public function batchThumbnail($sourcePath, $targetPath, $width, $height, $type = 'png', $isScale = true) {
        $sourcePath = $this->formatPath ( $sourcePath );
        $targetPath = $this->formatPath ( $targetPath );
        $sourcePath = $sourcePath . '*.' . $type;
        $images = new Imagick ( glob ( $sourcePath ) ); 
        // array glob(string $pattern[, int $flags]) // 寻找与模式匹配的文件路径
        $i = 0;
        foreach ( $images as $image ) {
            $image->thumbnailImage ( $width, $height, $isScale );
            $savePath = $targetPath . ++ $i . '.' . $type;
            $image->writeImage ( $savePath );
        }
    }
    
    /**
     * 对比度调节 (这个用得也不多，所以没有调，还不能用)
     * TODO
     * @param string $srcPath
     *            需要调整对比度的源图片
     * @param string $dstPath
     *            调整后保存的路径（处理后的目标图片存储位置）
     * @param boolean $sharpen
     *            所否增强对比度，默认true:增加对比度，false：减少对比度；
     *            貌似这里传0-9的数字没有什么用，这里的对比度所通过多次调用才能达到你想要到效果
     * @param boolean $apply
     *            作用区域； 默认false：全局作用，true：局部作用；全局作用时，后面到参数（宽、高、x、y）都没有用
     * @param integer $width
     *            局部作用宽
     * @param integer $height
     *            局部作用高
     * @param integer $x
     *            局部作用到坐标轴x
     * @param integer $y
     *            局部作用到坐标轴y
     * @return boolean
     */
    public function contrast($srcPath, $dstPath, $sharpen = true, $apply = false, $width = 0, $height = 0, $x = 0, $y = 0) {
        $this->readImage ( $srcPath );
        if ($apply) {
            $region = $this->getimageregion ( $width, $height, $x, $y ); // 获取图片的局部块
            $region->contrastimage ( $sharpen );
            $this->compositeImage ( $region, $region->getImageCompose (), $x, $y );
            $region->destroy ();
        } else {
            $this->contrastimage ( $sharpen );
        }
        return $this->writeImage ( $dstPath );
    }
    
    /**
     * 合并两张图片（把图二合并到图一来） (ImageMagick 有个奇怪的问题，第一次运行会有点慢)
     * TODO GIF动画
     * @param string $onePath
     *            需要合并的图片一
     * @param string $twoPath
     *            需要合并的图片二
     * @param string $dstPath
     *            合并后的保存地址
     * @param integer $x
     *            合并坐标轴x
     * @param integer $y
     *            合并坐标轴y
     * @return boolean
     */
    public function join($onePath, $twoPath, $dstPath, $x = 0, $y = 0) {
        $im1 = new Imagick ();
        $im1->readimage ( $onePath );
        $im2 = new Imagick ( $twoPath );
        $im2->setimageformat ( 'png' );
        $composite = $im2->getImageCompose ();
        $im1->compositeimage ( $im2, $composite, $x, $y );
        $im2->destroy ();
        return $im1->writeImage ( $dstPath );
    }

    /**
     * jpg质量压缩 （此函数在安全模式下不能运行）
     * 对其他类型到图片压缩率没有什么作用
     * @param string $src 源图片
     * @param string $dst 目标图片
     * @param integer $quality 质量（压缩比率） （后证实压缩率对gif毫无影响； 对png有一点影响，不过默认就好）
     */
    public function zip($src, $dst, $quality = 88) {
        exec("convert -quality {$quality} {$src} {$dst}");
    }
    
    /**
     * 将字符串生成图片
     * 此函数依赖 getDrawForText 函数
     * TODO 继续优化
     * @param string $text 需要生成图片的文字 （如果文字中有中文，那么就要用中文字体，不然有乱码）
     * @param mixed $color 文字颜色；可以如：'#FFF'; 'red'; 'rgb(0,0,255)';透明度在这里也是没有用的
     * @param integer $size 文字大小
     * @param string $font 字体；格式如： /home/bear/msyh.ttf
     * @param float $opacity 文字透明度，取值0-1；默认1：不透明； 0：全透明
     * @param integer $angle 文字角度
     * @param string $underColor 文字背景颜色，默认null：无   TODO ubuntu 32位上没有成功
     * @param string $background 画布颜色, transparent 透明
     * @return boolean
     */
    public function text($text, $color = 'black', $size = 8, $font = null, $opacity = 1, $underColor = null, $background = 'transparent') {
        $draw = $this->getDrawForText($font, $size, $color, $opacity, $underColor);
        $this->_im = new Imagick();
        //$im->setImageFormat('png');
        $properties = $this->_im->queryFontMetrics($draw, $text);
        $this->_im->newimage(intval($properties['textWidth']+15), intval($properties['textHeight']+15), new ImagickPixel($background));
        // 不能建一个半透明的图层，只能全透明和不透明
        $x = 7;
        $y = 32;
        return $this->_im->annotateImage($draw, $x, $y, 0, $text); // $angle 第三个参数是文字旋转角度， TODO
    }
    
    	 	
    

    
    
    	 	// 下面5个方法, 后面只有resize02修改过，可以用，其他的不能用
    	 	/**
    	 	* 缩小图片尺寸（缩略图）
    	 	* @param string $sourcePath 待处理的图片
    	 	* @param string $targetPath 保存处理后图片的路径
    	 	* @param integer $width 处理后图片尺寸的宽度（单位px）
    	 	* @param integer $height 处理后图片尺寸的高度（单位px）
    	 	* @param boolean $isCrop 是否裁剪图片；默认false：不裁剪图片；true：裁剪图片
    	 	* @return boolean
    	 	*/
    	 	//     public function resize01($sourcePath, $targetPath, $width, $height, $isCrop = false) {
    	 	//     	//$this->readImageBlob($sourcePath); // 从一个二进制到字符串中读取图像
    	 	//     	$this->readImage($sourcePath);
    	 	//     	$w = $this->getImageWidth();
    	 	//     	$h = $this->getImageHeight();
    	 	//     	if ($w > $width || $h > $height) {
    	 	//     		if ($isCrop) {
    	 	//     			$this->cropThumbnailImage($width, $height);
    	 	//     		} else {
    	 	//     			$this->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1, true);
    	 	//     		}
    	 	//     	}
    	 	//     	//$image = $this->getImageBlob();
    	 	//         return $this->writeImage($targetPath);
    	 	//     }
    
    	 	/**
    	 	* 缩小图片尺寸（缩略图） (特别注意了：后经测试，这个是最值得用的，速度和压缩都平衡) (jpg比png压缩率大很多)
    	 	* @param string $sourcePath 待处理的图片
    	 	* @param integer $width 处理后图片尺寸的宽度（单位px）
    	 	* @param integer $height 处理后图片尺寸的高度（单位px）
    	 	* @param string $targetPath 保存处理后图片的路径
    	 	* @param boolean $isCrop 是否裁剪图片；默认false：不裁剪图片；true：裁剪图片（会生成你给定的宽和高，居中裁剪）
    	 	* @param integer $quality 压缩质量 (很奇怪的是如果保存为png时，$quality为90时快些，但文件大很多)
    	 	* @return boolean
    	 	*/
    	 	//     public function resize02($sourcePath, $width, $height, $targetPath = null, $isCrop = false, $quality = 88) {
    	 	//     	$this->_im = new Imagick();
    	 	//     	$this->_im->readImage($sourcePath);
    	 	//     	$w = $this->_im->getImageWidth();
    	 	//     	$h = $this->_im->getImageHeight();
    	 	//     	$widthRate = $w/$width;
    	 	//     	$heightRate = $h/$height;
    	 	//     	$inputWidth = $width;
    	 	//     	$inputHeight = $height;
    	 	//     	if ($widthRate > 1 || $heightRate > 1) {
    	 	//     		if ($isCrop) {
    	 	//     			if ($widthRate > $heightRate) {
    	 	//     				$width = $w/$heightRate;
    	 	//     			} else {
    	 	//     				$height = $h/$widthRate;
    	 	//     			}
    	 	//     		} else {
    	 	//     			if ($widthRate > $heightRate) {
    	 	//     				$height = $h/$widthRate;
    	 	//     			} else {
    	 	//     				$width = $w/$heightRate;
    	 	//     			}
    	 	//     		}
    	 	//     		$this->_im->resizeImage($width, $height, Imagick::FILTER_CATROM, 1, false);
    	 	//     		if ($isCrop) {
    	 	 //     			if ($width > $inputWidth) {
    	 	 //     				$this->_im->cropImage($inputWidth, $height, ($width-$inputWidth)/2, 0);
    	 	 //     			} else if ($height > $inputHeight) {
    	 	 //     				$this->_im->cropImage($width, $inputHeight, 0, ($height-$inputHeight)/2);
    	 	//     			}
    	 	//     		}
    	 	//     	}
    	 	//     	$this->_im->setImageFormat('jpeg');
    	 	//     	$this->_im->setImageCompression(Imagick::COMPRESSION_JPEG);
    	 	//         $this->_im->setImageCompressionQuality($quality);
    	 	//         $this->_im->stripImage(); // 去除Exif信息
    	 	//         return $this->writeImage($targetPath);
    	 	//     }
    
    	 	/**
    	 	* 缩小图片尺寸（缩略图）
    	 	* @param string $sourcePath 待处理的图片
    	 	* @param string $targetPath 保存处理后图片的路径
    	 	* @param integer $width 处理后图片尺寸的宽度（单位px）
    	 	* @param integer $height 处理后图片尺寸的高度（单位px）
    	 	 * @param boolean $isCrop 是否裁剪图片；默认false：不裁剪图片；true：裁剪图片
    	 	 * @return boolean
    	 	 */
    	 	 //     public function resize03($sourcePath, $targetPath, $width, $height, $isCrop = false){
    	 	 //     	$this->readImage($sourcePath);
    	 	 //     	$this->setImageCompression(imagick::COMPRESSION_JPEG);
    	 	//     	$this->setImageCompressionQuality(80);
    	 	//     	if ($isCrop) {
    	 	//     		$this->cropThumbnailImage($width, $height);
    	 	//     	} else {
    	 	//     		$this->resizeImage($width, $height, Imagick::FILTER_CATROM, 1, true);
    	 	//     	}
    	 	//     	$this->stripImage();
    	 	//     	return $this->writeImage($targetPath);
    	 	//     }
    
    	 	/**
    	 	* 缩小图片尺寸（缩略图）
    	 	* @param string $sourcePath 待处理的图片
    	 	* @param string $targetPath 保存处理后图片的路径
    	 	* @param integer $width 处理后图片尺寸的宽度（单位px）
    	 	* @param integer $height 处理后图片尺寸的高度（单位px）
    	 	* @param boolean $isCrop 是否裁剪图片；默认false：不裁剪图片；true：裁剪图片
    	 	* @return boolean
    	 	*/
    	 	//     public function resize04($sourcePath, $targetPath, $width, $height, $isCrop = false){
    	 	//     	$this->readImage($sourcePath);
    	 	//     	if ($isCrop) {
    	 	 //     		$this->cropThumbnailImage($width, $height);
    	 	 //     	} else {
    	 	 //     		$this->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1, true);
    	 	 //     	}
    	 	 //     	$this->setImageFormat('jpeg');
    	 	 //     	$this->setImageCompression(Imagick::COMPRESSION_JPEG);
    	 	//     	$a = $this->getImageCompressionQuality()*0.75;
    	 	//     	if ($a == 0) {
    	 	//     	    $a = 75;
    	 	//     	}
    	 	//     	$this->setImageCompressionQuality($a);
    	 	//     	$geo = $this->getImageGeometry(); // 取得图像的宽和高
    	 	//     	$this->thumbnailImage($geo['width'], $geo['height']);
    	 	//     	$this->stripImage();
    	 	//     	return $this->writeImage($targetPath);
    	 	//     }
    
    	 	/**
    	 	* 缩小图片尺寸（缩略图）
    	 	* @param string $sourcePath 待处理的图片
    	 	* @param string $targetPath 保存处理后图片的路径
    	 	* @param integer $width 处理后图片尺寸的宽度（单位px）
    	 	* @param integer $height 处理后图片尺寸的高度（单位px）
    	 	* @param boolean $isCrop 是否裁剪图片；默认false：不裁剪图片；true：裁剪图片
    	 	* @return boolean
    	 	*/
    	 	//     public function resize($sourcePath, $targetPath, $width, $height, $isCrop = false) {
    	 	//     	$this->readImage($sourcePath);
    	 	//     	$w = $this->getImageWidth();
    	 	//     	$h = $this->getImageHeight();
    	 	//     	if ($w > $width || $h > $height) {
    	 	//     		if ($isCrop) {
    	 	//     			$this->cropThumbnailImage($width, $height);
    	 	//     		} else {
    	 	//     			$this->resizeImage($width, $height, Imagick::FILTER_CATROM, 1, true);
    	 	//     		}
    	 	//     	}
    	 	//     	$this->setImageFormat('JPEG');
    	 	//     	$this->setImageCompression(Imagick::COMPRESSION_JPEG);
    	 	//     	$a = $this->getImageCompressionQuality() * 0.75;
    	 	//     	if ($a ==0) {
//     		$a = 75;
    	 	//     	}
    	 	//     	$this->setImageCompressionQuality($a);
    	 	//     	$this->stripImage();
    //     	return $this->writeImage($targetPath);
    //     }
        // 下面是对本此5个方法（图片压缩）的总结：
        // 1、压缩率尽可能的小，这个要和业务部门商量，找到一个平衡点。（请注意最后方法设置品质方法使用获取到当前图片的压缩率然后再取75%，如果当前图片压缩率为60%，如果使用$imagick->setImageCompressionQuality(80)方法将使图片压缩率提高至80%，这会使图片变大！！！）
        // 2、一定要移除图片的exif信息！！！！  这部分内容详情请查看 http://baike.baidu.com/view/22006.htm
        // 3、压缩尺寸使用Imagick::FILTER_CATROM方法对速度有一定的提升，图片本身的品质没有大的变化。
        // 4、$imagick->setImageFormat(‘JPEG’)也很给力。
       
    /**
     * 释放资源
     */
    public function __destruct() {
        if ($this->_im instanceof Imagick) {
            $this->_im->destroy();
        }
    }
    
    /**
     * 格式化路径，把所有的 \ 转化成 / ， 并在最后加上 /
     * @param string $path
     * @return string
     */
    private function formatPath($path) {
        $path = str_replace ( '\\', '/', $path );
        if (substr ( $path, - 1 ) != '/') {
            $path = $path . '/';
        }
        return $path;
    }
        
	
}