<?php
require_once('Abstract.php');
/**
 * @desc 与图片有关的一些操作，如生成缩略图，不变形缩小图片，打水印，调整图片透明度，切割图片,合成图片
 * 这里主要用 GD 库来操作，目前只支持 .jpg, .png, .gif 格式的图片(不支持gif动画)
 * @author Bear
 * @version 2.0.0 2012-12-25 16:36
 * @copyright xiqiyanyan.com
 * @created 2011-11-1 14:15
 */
class Common_Image extends Common_Image_Abstract
{
	/**
	 * 源图片的部分信息，如：宽、高、类型 ; 
	 * Array ( [0] => 1000 [1] => 1539 [2] => 3 [3] => width="1000" height="1539" [bits] => 8 [mime] => image/png )
	 * 索引 2 的值是：1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，6 = BMP，7 = TIFF(intel byte order)，8 = TIFF(motorola byte order)，9 = JPC，10 = JP2，11 = JPX，12 = JB2，13 = SWC，14 = IFF，15 = WBMP，16 = XBM
	 * “bits”的值是碰到的最高的位深度
	 * @var array
	 */
	private $_imageInfo;

    /**
     * 允许的图片类型
     * @var array
     */
    private $_allowExt = array('.gif', '.jpg', '.jpeg', '.png'); // gd不支持bmp； 貌似别人有写过一个支持bmp的函数
	
    /**
     * 裁剪图片（除gif动画外，都能实现裁剪）
     * @see Lib_Image_Abstract::crop()
     * @param integer $width 目标宽，不能为负数(如果裁剪的宽和高都不传，将取原图片的宽和高)
     * @param integer $height 目标高，不能为负数（如果裁剪（目标）的宽或高只传一个，另一个将取原图片的）
     * @param integer $x 裁剪位置x轴，可以为负数；如为负数则在坐标轴原点的左边
     * @param integer $y 裁剪位置y轴，可以为负数；其图片左上角为坐标轴原点（0，0）
     * @return boolean
     */
    public function crop($width = 0, $height = 0, $x = 0, $y = 0) {
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
        $defactoWidthAndHeight = $this->getDefactoCropWidthAndHeight($this->_sourceWidth, $this->_sourceHeight, $width, $height, $x, $y); 
        $newImage = imagecreatetruecolor ( $defactoWidthAndHeight['width'], $defactoWidthAndHeight['height'] );
        // resource imagecreatetruecolor( int $x_size宽, int $y_size高)  新建一个真彩色黑色图像
        $boolean = imagecopyresampled ( $newImage, $this->_im, 0, 0, 0, 0, $defactoWidthAndHeight['width'], $defactoWidthAndHeight['height'], $defactoWidthAndHeight['width'], $defactoWidthAndHeight['height'] );
        // bool imagecopyresampled ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
        // 重采样拷贝部分图像并调整大小,将一幅图像中的一块正方形区域拷贝到另一个图像中，平滑地插入像素值，因此，尤其是，减小了图像的大小而仍然保持了极大的清晰度。成功时返回 TRUE， 或者在失败时返回 FALSE.
        //$boolean = imagecopy ( $newImage, $this->_im, 0, 0, $x, $y, $width, $height );
        // imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
        
        if ($boolean) {
            $this->_im = $newImage;
            return true;
        } else {
            $this->_error = '裁剪图片出错';
            $this->_im = null;
            return false;
        }
    }
    
    /**
     * 生成固定大小的缩略图，即可以把图像等比例缩小后进行裁剪; 
     * Previews(缩略图)
     * @see Lib_Image_Abstract::resize()
     * @param interger $width 生成图片(目标图片)的宽度
     * @param integer $height 生成图片(目标图片)的高度
     * @param string $tooWideCutPosition 如果宽度超出，按什么来裁剪图片，取值见常量，如：center(默认)：裁剪中间部分； west：居左裁剪； east：居右裁剪
     * @param string $tooHighCutPosition 如果高度超出，按什么来截取图片，取值见常量，如：center(默认)：截取中间部分； north：居上裁剪； south：居下裁剪
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
        if (!$width || !$height) { // 只传入了宽或高其中的一个
            return $this->thumbnail($width, $height);
        }
        
        $ratio = $this->getProportionality($this->_sourceWidth, $this->_sourceHeight, $width, $height, 'small');
        $cutW = intval($width/$ratio);
        $cutH = intval($height/$ratio);
        // 获取要裁剪的开始位置 $widthFlag 按什么位置裁剪宽
        $x = 0;
        $y = 0;
        switch ($tooWideCutPosition) {
            case self::PLACE_WEST : break;
            case self::PLACE_EAST : $x = (int) ($this->_sourceWidth-$cutW); break;
            default : $x = (int) ($this->_sourceWidth-$cutW)/2; break;
        }
        switch ($tooHighCutPosition) {
            case self::PLACE_NORTH : break;
            case self::PLACE_SOUTH : $y = (int) ($this->_sourceHeight-$cutH); break;
            default : $y = (int) ($this->_sourceHeight-$cutH)/2; break;
        }

        // 重新拷贝图像
        if (function_exists('imagecopyresampled')) {
            $tmpImage = imagecreatetruecolor($width, $height);// resource imagecreatetruecolor( int $x_size宽, int $y_size高) 新建一个真彩色黑色图像
            $boolean = imagecopyresampled($tmpImage, $this->_im, 0, 0, $x, $y, $width, $height, $cutW, $cutH);
            // bool imagecopyresampled ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
            // 重采样拷贝部分图像并调整大小,将一幅图像中的一块正方形区域拷贝到另一个图像中，平滑地插入像素值，因此，尤其是，减小了图像的大小而仍然保持了极大的清晰度。成功时返回 TRUE， 或者在失败时返回 FALSE.
        } else {
            $tmpImage = imagecreate($width, $height);// resource imagecreate ( int $x_size , int $y_size ) 新建一个基于调色板的空白图像
            $boolean = imagecopyresized($tmpImage, $this->_im, 0, 0, $x, $y, $width, $height, $cutW, $cutH); // 黑白
            // bool imagecopyresized ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
            // 拷贝部分图像并调整大小,将一幅图像中的一块正方形区域拷贝到另一个图像中 
        }
        if ($boolean) {
            $this->_im = $tmpImage;
            return true;
        } else {
            $this->_error = '重新采样拷贝图像时出错';
            $this->_im = null;
            return false;
        }
    }

    /**
     * 载入一张图片, 并检测文件类型
     * verification source image is exist and get source image infomation
     * @param string $sourcePath 源图片路径
     * @return boolean
     */
    public function read($sourcePath) {
    	if (!is_file($sourcePath)) {
    		$this->_error = '源图片文件不存在';
    		return false;
    	}
        $this->_imageInfo = getimagesize($sourcePath);
    	// array getimagesize ( string $filename [, array &$imageinfo ] )  取得图像位深度、类型、宽高; 本函数不需要 GD 图像库。
    	// getimagesize() 函数将测定任何 GIF，JPG，PNG，SWF，SWC，PSD，TIFF，BMP，IFF，JP2，JPX，JB2，JPC，XBM 或 WBMP 图像文件的大小并返回图像的尺寸以及文件类型和一个可以用于普通 HTML 文件中 IMG 标记中的 height/width 文本字符串。
    	// 如果不能访问 filename 指定的图像或者其不是有效的图像，getimagesize() 将返回 FALSE 并产生一条 E_WARNING 级的错误。
        if (!$this->_imageInfo) {
        	$this->_error = '源图片不是有效的图像';
        	return false;
        }
        $type = strtolower(image_type_to_extension($this->_imageInfo[2]));
        //$type = mime_content_type($sourcePath); // 获取文件类型(5.3后废弃,不建议使用) image/jpeg
        
        // 下面这个也是可以的
        //$finfo = new finfo(FILEINFO_MIME_TYPE);
        //$type = $finfo->file($sourcePath); // image/jpeg
        //$type = strtolower(finfo_file(finfo_open(FILEINFO_MIME_TYPE), $sourcePath)); // image/jpeg

        // image_type_to_extension 获取图片的后缀名，包括点在内（注意这里的参数）
        if (!in_array($type, $this->_allowExt)) {
        	$this->_error = '不支持的图片文件格式，只支持' . join('、', $this->_allowExt) . '格式的图片';
        	return false;
        }
        
    	$this->_sourceType = substr($type, 1);
        $this->_sourcePath = $sourcePath;
        $createFunction = 'imagecreatefrom' . $this->_sourceType;
        $this->_im = $createFunction($this->_sourcePath); // 通过源文件类型创建图像资源
        $this->_sourceWidth = (int) $this->_imageInfo [0];  // 源图像的宽度
        $this->_sourceHeight = (int) $this->_imageInfo [1]; // 源图像的高度
        // $width = imagesx($this->_im); // int imagesx ( resource $image )
        // 获取图像的宽度 # 获取源图像的宽度 （现在才知道， # 也可以做为php的注释）
        // $height = imagesy($this->_im); //int imagesy ( resource $image )
        // 获取图像的高度
        return true;
        /* switch ($this->_imageInfo[2]) { // IMAGETYPE_JPEG
            case 1 : return $this->_im = imagecreatefromgif($this->_sourcePath); break;
            case 2 : return $this->_im = imagecreatefromjpeg($this->_sourcePath); break;
    		// resource imagecreatefromjpeg( string $filename)  从 JPEG 文件或 URL 新建一图像, 返回一图像标识符
    	    case 3 : return $this->_im = imagecreatefrompng($this->_sourcePath); break;
    	    default : $this->setError('源图片错误；不支持的文件类型，只支持GIF、JPG、PNG的图片'); return false; break;
        } */
    }

    /**
     * 等比例生成缩略图(bind thumbnail image ; 不支持gif动画，能生成jpg、gif、png格式的缩略图，如果gif动画不需要动画的话，也可以生成)
     * 如果源图片的宽和高都小于给定缩略图的宽和高就不进行缩率
     * 这里保持了源图像的比例，如果需要截取图片并生成缩略图请调用 resize方法
     * 最好生成的是 jpg 图片，这样图片会小很多
     * 呵呵，这个函数还可以用来当上传图片使用
     * @see Lib_Image_Abstract::thumbnail
     * @param integer $width 缩略图的宽
     * @param integer $height 缩略图的高 （如果想只按宽或高进行缩放时，宽或高可以只传一个，另一个设为0或null）
     * @param string $type 指定生成图片的类型，默认jpg，如果保持源图片类型传入null
     * @param integer $quality 生成缩略图的质量，取值范围是 0(最差)-100（最好）;仅对jpg有效
     * @param boolean $flag 是否删除源图片，默认false:不删除， true:删除
     * @return false | string 如果成功返回完整的文件名，如果失败返回false，会有错误提示信息
     */
    public function thumbnail($width = null, $height = null, $bestfit = true) {
        // $_FILES['inputFileName']['type'] => 'image/jpeg'、'image/pjpeg'、'image/png'、'image/x-png'、'image/gif'、、、、
    	$width = (int) $width;
    	$height = (int) $height;
    	if (!$this->_isExistIm()) {
    		return false;
        }
        if ($width == null && $height == null) {
    		return true;
    	}
        if ($this->_sourceWidth <= $width && $this->_sourceHeight <= $height) {
        	return true;
        }
		$ratio = $this->getProportionality($this->_sourceWidth, $this->_sourceHeight, $width, $height);
		if (!$ratio) {
			return false;
		}
        $newWidth = floor($this->_sourceWidth * $ratio);
        $newHeight = floor($this->_sourceHeight * $ratio);
        if (function_exists ( 'imagecopyresampled' ) ) {
            $newImage = imagecreatetruecolor ( $newWidth, $newHeight );
            // resource imagecreatetruecolor( int $x_size宽, int $y_size高)
            // 新建一个真彩色黑色图像
            $boolean = imagecopyresampled ( $newImage, $this->_im, 0, 0, 0, 0, $newWidth, $newHeight, $this->_sourceWidth, $this->_sourceHeight );
            // bool imagecopyresampled ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
            // 重采样拷贝部分图像并调整大小,将一幅图像中的一块正方形区域拷贝到另一个图像中，平滑地插入像素值，因此，尤其是，减小了图像的大小而仍然保持了极大的清晰度。成功时返回 TRUE， 或者在失败时返回 FALSE.
        } else {
            $newImage = imagecreate ( $newWidth, $newHeight );
            // resource imagecreate ( int $x_size , int $y_size )
            // 新建一个基于调色板的空白图像(创建画布)
            // $newImage = imagecreatetruecolor($newWidth, $newHeight); // 貌似这个更好
            $boolean = imagecopyresized ( $newImage, $this->_im, 0, 0, 0, 0, $newWidth, $newHeight, $this->_sourceWidth, $this->_sourceHeight );
            // bool imagecopyresized ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
            // 拷贝部分图像并调整大小,将一幅图像中的一块正方形区域拷贝到另一个图像中
        }
        if ($boolean) {
        	$this->_im = $newImage;
        	return true;
        } else {
        	$this->_error = '处理图片出错';
        	return false;
        }
		// @imagedestroy($newImage); // imagedestroy(resource $image) 销毁一图像
		// 操，特别注意了，这个地方还不能销毁这图像，貌似这里赋值时是按引用传递的
    }

    /**
	 * 给图片打上文字水印
	 * png8貌似不能换颜色
	 * @param string $text 要打水印的文字（包括中文）,如果有中文的话，需要中文字体
	 * @param string $place 打水印的位置：取值见常量
	 * @param integer $x 水印位置，如果传入了$place,这个参数也有效，指X轴偏移量
     * @param integer $y 水印位置，如果传入了$place,这个参数也有效，指Y轴偏移量
     * @param integer $angle 文字旋转角度；正数顺时针，负数逆时针； 0 度为从左向右读的文本。例如 90 度表示从上往下读的文本。180（-180）度为从右到左的倒立文本
     * @param array $style 水印文字样式
     * $style['fontFile'] 水印文字字体 ； 如：APPLICATION_PATH . '/../public/Fonts/msyhbd.ttf'（如果水印文字有中文，必须要中文字体才能支持，不然会有乱码）
     * $style['fontSize'] 水印文字的大小，默认15
     * $style['fillColor'] 水印文字的颜色，默认黑色 #000000; 文字颜色(0-255)；取值如：$style['fillColor'] = array('red'=>255, 'green'=>0, 'blue'=>0); 
     * 这些参数是 0 到 255 的整数或者十六进制的 0x00 到 0xFF; 同样该参数也可以传入字符串，如：#F00 ; 不支持颜色单词 
     * $style['fillOpacity'] 水印文字的透明度，取值0(完全不透明)-127(完全透明);默认：68 半透明
     * $style['underColor'] 水印文字背景色，默认无 (这里暂不支持)
     * @return boolean
	 */
    public function watermarkText($text, $place = null, $x = 15, $y = 25, $angle = 0, array $style = array()) {
        if (!$text || !is_string($text)) {
            $this->_error = '水印文字不能为空且一定是字符串';
            return false;
        }
        if (!$this->_isExistIm()) {
            return false;
        }
        
        if (!isset($style['fontFile']) || !$style['fontFile']) {
            $style['fontFile'] = ROOT_PATH . '/fonts/msyhbd.ttf';
        }
        if (!is_file($style['fontFile'])) {
            $this->_error = '字体文件不存在';
            $this->_im = null;
            return false;
        }
        if (!isset($style['fontSize']) || !$style['fontSize']) {
            $style['fontSize'] = 15;
        }
        $angle = -$angle;
        $temp = imagettfbbox($style['fontSize'], $angle, $style['fontFile'], $text); // 取得使用 TrueType 字体的文本的范围
        $textW = $temp[2] - $temp[6]; // 取得字符串的宽
        $textH = $temp[3] - $temp[7]; // 取得字符串的高
        
        switch ($place) {
            case self::PLACE_CENTER    : $x = ($this->_sourceWidth-$textW)/2; $y = ($this->_sourceHeight-$textH)/2; break;
            case self::PLACE_NORTHEAST : $x = $this->_sourceWidth-($textW+$x); break;
            case self::PLACE_NORTHWEST : break;
            case self::PLACE_SOUTHEAST : $x = $this->_sourceWidth-($textW+$x); $y = $this->_sourceHeight-($textH+$y); break;
            case self::PLACE_SOUTHWEST : $y = $this->_sourceHeight-($textH+$y); break;
            default : break;
        }
        
        if (isset($style['fillColor'])) {
            if (is_string($style['fillColor'])) {
                $style['fillColor'] = strtoupper($style['fillColor']);
                if (substr($style['fillColor'], 0, 1) != '#') {
                    $this->_error = '文字颜色参数错误，应该如：#FFFFFF';
                    $this->_im = null;
                    return false;
                }
                if (isset($style['fillColor'][6])) {
                    $style['fillColor'] = array('red'   => hexdec(substr($style['fillColor'], 1, 2)), // 十六进制转换为十进制
                                             'green' => hexdec(substr($style['fillColor'], 3, 2)), 
                                             'blue'  => hexdec(substr($style['fillColor'], 5, 2)));
                } else {
                    $style['fillColor'] = array('red'   => hexdec($style['fillColor'][1].$style['fillColor'][1]), // 十六进制转换为十进制
                            'green' => hexdec($style['fillColor'][2].$style['fillColor'][2]),
                            'blue'  => hexdec($style['fillColor'][3].$style['fillColor'][3]));
                }
            } else if (is_array($style['fillColor'])) {
                // 数组不处理
            } else {
                $style['fillColor'] = array('red'=>0, 'green'=>0, 'blue'=>0);
            }
        } else {
            $style['fillColor'] = array('red'=>0, 'green'=>0, 'blue'=>0);
        }
        if (!isset($style['fillOpacity'])) {
            $style['fillOpacity'] = 68;
        }

//         $image = imagecreatetruecolor($textW, $textH);
        $color = imagecolorallocatealpha($this->_im, $style['fillColor']['red'], $style['fillColor']['green'], $style['fillColor']['blue'], $style['fillOpacity']);
        
        //$color = imagecolorallocate($this->_im, $style['fillColor']['red'], $style['fillColor']['green'], $style['fillColor']['blue']);
        //var_dump($color);exit;
        // int imagecolorallocate ( resource $image , int $red , int $green , int $blue ) 为一幅图像分配颜色。 首次调用将创建背景颜色
        //string iconv(string $in_charset, string $out_charset, string $str); // 字符串的字符集转换；如gb2312->utf-8 ：$string = iconv('GB2312', 'utf-8', $string);

        // imagefttext($im, $fontSize, 0, $startXY['x'], $startXY['y'], $color, $fontFile, $string); 
        // 使用 FreeType 2 字体将文本写入图像 ;array imagefttext ( resource $image , float $size , float $angle , int $x , int $y , int $col , string $font_file , string $text [, array $extrainfo ] )
        imagettftext($this->_im, $style['fontSize'], $angle, $x, $y, $color, $style['fontFile'], $text); // 用 TrueType 字体向图像写入文本 ;
        // array imagettftext ( resource $image , float $size , float $angle , int $x , int $y , int $color , string $fontfile , string $text )
        // image 图像资源。
        // size 字体大小。根据 GD 版本不同，应该以像素大小指定（GD1）或点大小（GD2）。
        // angle 角度制表示的角度，0 度为从左向右读的文本。更高数值表示逆时针旋转。例如 90 度表示从下向上读的文本。
        // x 由 x，y 所表示的坐标定义了第一个字符的基本点（大概是字符的左下角）。这和 imagestring() 不同，其 x，y 定义了第一个字符的左上角。例如 "top left" 为 0, 0。
        // y Y 坐标。它设定了字体基线的位置，不是字符的最底端。
        // color 颜色索引。使用负的颜色索引值具有关闭防锯齿的效果。见 imagecolorallocate()。
        // fontfile 是想要使用的 TrueType 字体的路径。 根据 PHP 所使用的 GD 库的不同，当 fontfile 没有以 / 开头时则 .ttf 将被加到文件名之后并且会在库定义字体路径中尝试搜索该文件名。 当使用的 GD 库版本低于 2.0.18 时，一个空格字符 而不是分号将被用来作为不同字体文件的“路径分隔符”。不小心使用了此特性将会导致一条警告信息：Warning: Could not find/open font。对受影响的版本来说唯一解决方案就是将字体移动到不包含空格的路径中去。
        // text 文本字符串。 可以包含十进制数字化字符表示（形式为：&#8364;）来访问字体中超过位置 127 的字符。UTF-8 编码的字符串可以直接传递。 如果字符串中使用的某个字符不被字体支持，一个空心矩形将替换该字符。
        // 此函数 imagettftext() 返回一个含有 8 个单元的数组表示了文本外框的四个角，顺序为左下角，右下角，右上角，左上角。这些点是相对于文本的而和角度无关，因此“左上角”指的是以水平方向看文字时其左上角。本函数同时需要 GD 库和 » FreeType 库。
        return true;
//     		if ($isCover) { // 覆盖源图片
//     			$this->_targetPath = $this->_sourcePath;
//     			$flag = false;
//     			$imageFileName = pathinfo($this->_targetPath, PATHINFO_BASENAME);
//     			$type = pathinfo($this->_targetPath, PATHINFO_EXTENSION);
//     		} else {

//     			$this->_targetPath = $this->_targetPath . $imageFileName;
//     		}
//     		$this->_fileName = null;


//     		return $imageFileName;
    		/* $imageFileName = $this->getFileName() . $this->_sourceExtension; // 获取文件名和后缀名
    		if (!$this->printImageByExt($this->_im, $this->_targetPath . $imageFileName)) {
    			$this->setError('保存图片文件出错。无法保存图片，不支持的图片格式或图片文件不可写');
    			return false;
    		} */
    }
    
    /**
     * 给源图片打上图片水印
     * （貌似有些打出来达不到想要的效果）
     * 水印图片如果是 png 的话，貌似全透明的效果出不来
     * 如果源图是png8貌似打出来效果不好
     * @see Lib_Image_Abstract::watermarkImage()
     * @param string $watermarkPath 水印图片的绝对路径
     * @param string $place 打水印的位置(position)；取值见常量。默认southeast：右下角；center:中间;southwest:左下角;northeast:右上角; northwest:左上角
     * @param integer $x 打水印的开始位置，X轴；如果传入了$place,此值将做为偏移量
     * @param integer $y 打水印的开始位置，Y轴；如果传入了$place,此值将做为偏移量
     * @param integer $alpha 水印图片的透明度，取值0（全透明）-100（不透明）；仅对 jpg 格式的水印图片有效(暂不支持)
     * @param integer $angle TODO 水印图片的旋转角度
     * @return boolean
     */
    public function watermarkImage($watermarkPath, $place = 'southeast', $x = 18, $y = 18, $alpha = 52, $angle = 0) {
        if (!is_file($watermarkPath)) {
            $this->_error = '水印图片不存在！';
            $this->_im = null;
            return false;
        }
        if (!$this->_isExistIm()) {
            return false;
        }
        $watermarkExt = strtolower(strrchr($watermarkPath, '.'));
        if (!in_array($watermarkExt, $this->_allowExt)) { // 仅通过后缀名进行判断和创建图像资源； TODO 真实的文件类型
            $this->_error = '水印图片文件类型不符，只支持 ' . implode('、', $this->_allowExt) . ' 格式的图片';
            $this->_im = null;
            return false;
        }
        $watermarkType = substr($watermarkExt, 1);
        if ($watermarkType == 'jpg') {
            $watermarkType = 'jpeg';
        }
        $imageFunction = 'imagecreatefrom' . $watermarkType;
        $watermarkImg = $imageFunction($watermarkPath);
        $watermarkW = imagesx($watermarkImg);
        $watermarkH = imagesy($watermarkImg);
        if ($this->_sourceWidth < $watermarkW || $this->_sourceHeight < $watermarkH) { // 多于判断，有时候没有必要考虑
            $this->setError('源图片的宽或高小于水印图片的宽或高，无法生成水印图！');
            $this->_im = null;
            return false;
        }
        switch ($place) {
            case self::PLACE_NORTHWEST : break;
            case self::PLACE_NORTHEAST : $x = $this->_sourceWidth-($watermarkW+$x); break;
            case self::PLACE_CENTER    : $x = ($this->_sourceWidth-$watermarkW)/2; $y = ($this->_sourceHeight-$watermarkH)/2; break;
            case self::PLACE_SOUTHWEST : $y = $this->_sourceHeight-($watermarkH+$y); break;
            case self::PLACE_SOUTHEAST : $x = $this->_sourceWidth-($watermarkW+$x); $y = $this->_sourceHeight-($watermarkH+$y); break;
            default : break;
        }
        imagealphablending($this->_im, true); // 设定图像的混色模式;  貌似这里没有什么用，用不用都一样
        $boolean = imagecopymergegray($this->_im, $watermarkImg, $x, $y, 0, 0, $watermarkW, $watermarkH, $alpha); // ubuntu 64 位下测试不行; ubuntu 32 位下测试可以 
        // 用灰度拷贝并合并图像的一部分;拷贝水印到目标文件
        //$boolean = imagecopy($this->_im, $watermarkImg, $x, $y, 0, 0, $watermarkW, $watermarkH); // ubuntu 64 位下测试还可以
        //$boolean = imagecopymerge($this->_im, $watermarkImg, $x, $y, 0, 0, $watermarkW, $watermarkH, 100); // ubuntu 64 位下测试不行
        //$boolean = imagecopyresized($this->_im, $watermarkImg, $x, $y, 0, 0, $watermarkW, $watermarkH, $watermarkW, $watermarkH); // ubuntu 64 位下测试还可以
        //$boolean = imagecopyresampled($this->_im, $watermarkImg, $x, $y, 0, 0, $watermarkW, $watermarkH, $watermarkW, $watermarkH); // ubuntu 64 位下测试还可以
        // int imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h); 拷贝图像的一部分
        //imagecopyresized($this->_im, $watermarkImg, $start['x'], $start['y'], 0, 0, $watermarkW, $watermarkH, $watermarkW, $watermarkH); // 这个写法就和上面的一模一样
        //int imagecopymerge ( resource dst_im, resource src_im, int dst_x, int dst_y, int src_x, int src_y, int src_w, int src_h, int pct ) 拷贝并合并图像的一部分
        // 将 src_im 图像中坐标从 src_x，src_y 开始，宽度为 src_w，高度为 src_h 的一部分拷贝到 dst_im 图像中坐标为 dst_x 和 dst_y 的位置上。两图像将根据 pct 来决定合并程度，其值范围从 0 到 100。
        // 当 pct = 0 时，实际上什么也没做 当为 100 时本函数和 imagecopy() 完全一样。  
        // imagecopymergegray 和 imagecopymerge() 完全一样只除了合并时通过在拷贝操作前将目标像素转换为灰度级来保留了原色度。
        if ($boolean) {
            return true;
        } else {
            $this->_error = '拷贝并合并图像时出错';
            $this->_im = null;
            return false;
        }
    }

    /**
     * 水平(左右翻转)或垂直(上下翻转)翻转图片
     * @see Lib_Image_Abstract::rotateHV()
     * @param string $hv 是水平还是垂直翻转，默认H：水平；V:垂直；
     * @return boolean
     */
    public function rotateHV($hv = 'H') {
        if (!$this->_isExistIm()) {
            return false;
        }
        $imgTmp = imagecreatetruecolor($this->_sourceWidth, $this->_sourceHeight);
        if ($hv == self::ROTATE_H) { // 水平翻转
            $startX = $this->_sourceWidth-1;
            $startY = 0;
            $srcX = -$this->_sourceWidth;
            $srcY = $this->_sourceHeight;
        } elseif ($hv == self::ROTATE_V) { // 垂直翻转
            $startX = 0;
            $startY = $this->_sourceHeight-1;
            $srcX = $this->_sourceWidth;
            $srcY = -$this->_sourceHeight;
        } else {
            $this->setError('参数错误');
            return false;
        }
        $boolean =  imagecopyresampled($imgTmp, $this->_im, 0, 0, $startX, $startY, $this->_sourceWidth, $this->_sourceHeight, $srcX, $srcY);
        if ($boolean) {
            $this->_im = $imgTmp;
            return true;
        } else {
            $this->_error = '翻转图片失败';
            return false;
        }
    }

    /**
     * 写入图片
     * @param string $targetPath 目标路径（只能是绝对路径）
     * @param boolean $flag 是否覆盖(isCover)已存在的图片；默认true：覆盖；false：不覆盖
     * @see Lib_Image_Abstract::write()
     * @return boolean
     */
    public function write($targetPath = null, $flag = true) {
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
    	return $this->writeImageByPath($this->_targetPath, $flag);
    }

    /**
     * 任意角度旋转图片
     * @see Lib_Image_Abstract::rotate()
     * @param integer $angle 旋转角度，负数逆时针，正数顺时针
     * @param array | string $color 背景颜色（可以实现半透明，但是保存图片必须为png）,默认transparent：全透明 。
     * 取值如：array('red'=>0, 'green'=>0, 'blue'=>255) 或 array('red'=>0xFF, 'green'=>0xFF, 'blue'=>0xFF); 或 #FFFFFF 或 #0f0 
     * @return boolean
     */
    public function rotate($angle, $color = 'transparent') {
        if (!$this->_isExistIm()) {
            return false;
        }
        if ($color == 'transparent') {
            //$color = imagecolorallocate($this->_im, 255, 255, 255);
            //$color = imagecolortransparent($this->_im, $color); // 将某个颜色定义为透明色 ; 这里貌似不行
            $color = imagecolorallocatealpha($this->_im, 255, 255, 255, 127); // 2147483647 2130706432 2133993502
            // 为一幅图像分配颜色+alpha； 失败返回false。
        } else {
            $error = '颜色参数错误';
            if (is_array($color)) { // 不处理
                if (!isset($color['red']) || !isset($color['green']) || !isset($color['blue'])) {
                    $this->_error = $error;
                    $this->_im = null;
                    return false;
                }
            } elseif (is_string($color)) {
                $color = strtoupper($color);
                if (substr($color, 0, 1) != '#') {
                    $this->_error = '文字颜色参数错误，应该如：#FFFFFF';
                    $this->_im = null;
                    return false;
                }
                if (isset($color[6])) {
                    $color = array('red'   => hexdec(substr($color, 1, 2)), // 十六进制转换为十进制
                            'green' => hexdec(substr($color, 3, 2)),
                            'blue'  => hexdec(substr($color, 5, 2)));
                } else {
                    $color = array('red'   => hexdec($color[1].$color[1]), // 十六进制转换为十进制
                            'green' => hexdec($color[2].$color[2]),
                            'blue'  => hexdec($color[3].$color[3]));
                }
                // TODO rgb(255,255,255) rgba(0,0,0,127)
            } else {
                $this->_error = $error;
                $this->_im = null;
                return false;
            }
            // TODO 独立函数，将颜色值转为数组rgba
            if (!isset($color['alpha'])) {
                $color['alpha'] = 0;
            }
            $color = imagecolorallocatealpha($this->_im, $color['red'], $color['green'], $color['blue'], $color['alpha']); 
        }
        $this->_im = imagerotate($this->_im, -$angle, $color, 1);
        if ($this->_im) {
            return true;
        } else {
            $this->_im = null;
            $this->_error = '旋转图片失败';
            return false;
        }
    }

    /**
     * 输出图像到浏览器
     * @see Lib_Image_Abstract::show()
     */
    public function show() {
        switch ($this->_sourceType) {
            case self::TYPE_GIF  : $imgType = 'image/gif'; $ext = 'gif'; break;
            case self::TYPE_JPEG :
            case self::TYPE_JPG  : $imgType = 'image/jpeg'; $ext = 'jpeg'; break;
            default : $imgType = 'image/png'; $ext = 'png'; break;
        }
        header('Content-Type:' . $imgType);
        $function = 'image' . $ext;
        $function($this->_im);
    }

    /**
     * 释放资源
     */
    public function __destruct() {
        if (is_resource($this->_im)) {
            imagedestroy($this->_im); // imagedestroy(resource $image) 销毁一图像
        }
    }

    /**
     * 获取裁剪后图片的实际宽度和高度
     * @param integer $sourceWidth 原图片的宽度
     * @param integer $sourceHeight 原图片的高度
     * @param integer $cropWidth 要裁剪的宽度
     * @param integer $cropHeight 要裁剪的高度
     * @param integer $x 裁剪的X坐标轴，可以为负数
     * @param integer $y 裁剪的Y坐标轴，可以为负数
     * @return array
     */
    private function getDefactoCropWidthAndHeight($sourceWidth, $sourceHeight, $cropWidth, $cropHeight, $x, $y) {
        $defactoWidthAndHeight = array('width'=>$cropWidth, 'height'=>$cropHeight);
        if (($cropWidth+$x) > $sourceWidth) {//$this->_sourceWidth - $x < $width
            $defactoWidthAndHeight['width'] = $sourceWidth-$x;
        } elseif (($cropWidth+$x) < $cropWidth) {
            $defactoWidthAndHeight['width'] = $cropWidth+$x;
        }
        if (($cropHeight+$y) > $sourceHeight) {
            $defactoWidthAndHeight['height'] = $sourceHeight-$y;
        } elseif (($cropHeight+$y) < $cropHeight) {
            $defactoWidthAndHeight['height'] = $cropHeight+$y;
        }
        return $defactoWidthAndHeight;
    }

    /**
     * 根据图片类型，保存相应的图片
     * @param string $targetPath 图片保存路径，包括完整的文件名
     * @param boolean $flag 是否覆盖已存在的图片；默认true：覆盖；false：不覆盖
     * @return boolean
     */
    private function writeImageByPath($targetPath, $flag) {
        if (is_file($targetPath)) {
            if ($flag) {
                if (!is_writeable($targetPath)) {
                    $this->_error = '图片文件 ' . $targetPath . ' 已存在且不可写！无法保存图片文件！';
                    return false;
                }
            } else {
                $this->_error = '目标文件已存在';
                return false;
            }
        }
        $dir = pathinfo($targetPath, PATHINFO_DIRNAME); // pathinfo 默认返回所有文件路径信息； 这里返回文件夹信息（返回目录部分）
        if (!is_writable($dir)) {
            $this->_error = '目录 ' . $dir . ' 不可写！无法保存图片！';
            return false;
        }
        $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION)); // 图片类型
        if (!in_array('.' . $ext, $this->_allowExt)) {
            $this->_error = '不支持的图片文件格式，无法保存图片；只支持 ' . join('、', $this->_allowExt) . ' 格式的图片';
            return false;
        }
        switch ($ext) {
            case self::TYPE_GIF   : return imagegif($this->_im, $targetPath); break;
            /* case 'png' : $quality = floor($quality/10);
             if ($quality < 0 || $quality > 9) {
            $quality = 8;
            }
            $quality = 10 - $quality;
            return imagepng($im, $targetPath, $quality); break; */
            // 特别注意了：这里到质量只能是0-9，如果填错了将可能无法生成图片;  (其实这里的质量对png影响不大，所以没有什么意义)
            // 特别注意了：imagepng($image[, $filename, $quality, $filters]) 倒数第二个参数是表示图片是压缩比例，取值必须是0~9（ compression level must be 0 through 9 ）；
            // 0  代表不压缩 文件和源文件一样大，1代表压缩最小， 9代表压缩最大，貌似没有多大的区别。默认6左右
            case self::TYPE_PNG   : imagesavealpha($this->_im, true); // 如果源gif或png有透明的部分需要保留
            // imagesavealpha($image, $saveflag); // 设置标记在保存png图像时保存完整的alpha通道；使用本函数，必须将 alphablending 清位（imagealphablending($im, false)）
            return imagepng($this->_im, $targetPath); break;
            case self::TYPE_JPG   :
            case self::TYPE_JPEG  : return imagejpeg($this->_im, $targetPath, $this->_quality); break; // jpeg 默认 $quality = 75
            // bool imagejpeg ( resource $image [, string $filename [, int $quality ]] )  以 JPEG 格式将图像输出到浏览器或文件
            default : $this->_error = '不支持的图片格式，无法保存图片'; return false; break;
        }
    }

}
