<?php

/**
 * 图片处理抽象类
 * (抽象类中的方法：如果是抽象方法的话，可以不用实现；如果子类继承了抽象类，那么一定要实现抽象方法)
 * 尽量使抽象方法中的参数及参数值和继承其抽象类的实现方法一致
 * @author bear
 * @copyright xiqiyanyan.com
 * @version 2.0.0
 * @created 2012-12-11 20:40
 */
abstract class Common_Image_Abstract
{
	/**
	 * 位置：左上（西北）
	 * @var string
	 */
	const PLACE_NORTHWEST = 'northwest';
	
	/**
	 * 位置：上边居中（北）
	 * @var string
	 */
	const PLACE_NORTH = 'north';
	
	/**
	 * 位置：右上（东北）
	 * @var string
	 */
	const PLACE_NORTHEAST = 'northeast';
	
	/**
	 * 位置：左边（西）
	 * @var string
	 */
	const PLACE_WEST = 'west';
	
	/**
	 * 位置：居中（最中间）
	 * @var string
	 */
	const PLACE_CENTER = 'center';
	
	/**
	 * 位置：右边（东）
	 * @var string
	 */
	const PLACE_EAST = 'east';
	
	/**
	 * 位置：左下（西南）
	 * @var string
	 */
	const PLACE_SOUTHWEST = 'southwest';
	
	/**
	 * 位置：下边居中（南）
	 * @var string
	 */
	const PLACE_SOUTH = 'south';
	
	/**
	 * 位置：右下（东南）
	 * @var string
	 */
	const PLACE_SOUTHEAST = 'southeast';
    // 其实Imagick也有打水印的位置，见imagick::GRAVITY_NORTHWEST

	const TYPE_GIF = 'gif';
	const TYPE_JPG = 'jpg';
	const TYPE_JPEG = 'jpeg';
	const TYPE_PNG = 'png';
	const TYPE_WBMP = 'wbmp';
	const TYPE_BMP = 'bmp';
	
    /**
     * 水平翻转
     * @var string
     */
    const ROTATE_H = 'H';
    
    /**
     * 垂直翻转
     * @var string
     */
    const ROTATE_V = 'V';
	
	/**
	 * 错误信息
	 * @var string
	 */
	protected $_error = null;
	
	/**
	 * 图像资源
	 * @var Imagick | source | object (Imagick)
	 */
	protected $_im = null;
	
	/**
	 * 源图片路径
	 * @var string
	 */
	protected $_sourcePath = null;
	
	/**
	 * 目标图片路径
	 * @var string
	 */
	protected $_targetPath = null;
	
	/**
	 * 源图片类型，值如： gif,jpg,jpeg,png (不包括点在内)
	 * @var string
	 */
	protected $_sourceType = null;
	
	/**
	 * 目标文件的文件名
	 * @var string
	 */
	protected $_targetFilename = null;
	
	/**
	 * 源图片的宽
	 * @var integer
	 */
	protected $_sourceWidth;
	
	/**
	 * 源图片的高
	 * @var integer
	 */
	protected $_sourceHeight;
	
    /**
     * jpg图片的质量
     * @var integer
     */
    protected $_quality = 88;

    /**
     * 生成随机文件名
     * @return string
     */
    static public function getFileName($long = 6) { // 抽象类中也允许有静态方法
        $abcString = '';
        $numString = '';
        $ABCString = '';
        for ($i=0; $i<$long; $i++) {
            $abcASCII = mt_rand(97, 122); // mt_rand() 生成随机数
            $abcString .= chr($abcASCII); // chr($ascii): ASCII码 转字符串，如果是字符串转ASCII码用：ord($char); ASCII码对应值： A-Z:65-90; a-z:97-122; 0-9:48-57
            $numASCII = mt_rand(48, 57);
            $numString .= chr($numASCII);
            $ABCASCII = mt_rand(65, 90);
            $ABCString .= chr($ABCASCII);
        } // print_r(microtime(true)); // 1320820176.375 // time() 秒数
        return $abcString . $numString . $ABCString . date('YmdHis');
    }
    
    /**
     * 删除文件
     * @param string $path
     */
    static public function deleteFile($path) {
        if (file_exists($path)) {
            @unlink($path);
        }
    }
    
	/**
	 * @param string 源图片路径（需要处理的图片的绝对路径） origin
	 */
	public function __construct($sourcePath = null) {
		if ($sourcePath !== null) {
			$this->read($sourcePath);
		}
	}
	
	/**
	 * 设置图像资源
	 * @param object $im
	 */
	public function setIm($im) {
		$this->_im = $im;
		// TODO 设置源图片的宽高等信息
	}
	
	/**
	 * 获取图像资源
	 * @return Imagick | resource
	 */
	public function getIm() {
		return $this->_im;
	}
	
	/**
	 * 设置错误信息
	 * @param string $error
	 */
	public function setError($error) {
		$this->_error = $error;
	}
	
	/**
	 * 获取错误信息
	 * @return string
	 */
	public function getError() {
		return $this->_error;
	}
	
	/**
	 * 设置源图片路径
	 * @param string $sourcePath
	 */
	public function setSourcePath($sourcePath) {
		$this->read($sourcePath);
	}
	
	/**
	 * 设置目标图片路径(包括文件名)
	 * @param string $targetPath
	 */
	public function setTargetPath($targetPath) {
		$this->_targetPath = str_replace('\\', '/', $targetPath);
	}
	
	/**
	 * 获取源图片类型
	 * @return string
	 */
    public function getSourceType() {
		return $this->_sourceType;
	}
	
    /**
     * 设置图片质量（只对jpg和png有用）
     * @param integer $quality
     */
    public function setQuality($quality) {
        $this->_quality = $quality;
    }

	/**
	 * 获取缩小比例
	 * @param integer $srcWidth 源图的宽
	 * @param integer $srcHeight 源图的高
	 * @param integer $dstWidth 目标图片的宽
	 * @param integer $dstHeight 目标图片的高
	 * @param string $minification 按大的还是小的缩率；small：取得小的缩率[缩小得比较少]（宽或高有一边大于给定值），默认big：取得大的缩率(宽或高一定不会超过给定值)
	 * @return float | false
	 */
    public function getProportionality($srcWidth, $srcHeight, $dstWidth = null, $dstHeight = null, $minification = 'big') {
        $ratio = 1;
        $srcWidth = (int) $srcWidth;
        $srcHeight = (int) $srcHeight; 
        $dstWidth = (int) $dstWidth;
        $dstHeight = (int) $dstHeight;
        if ($srcWidth<1) {
        	$this->_error = '源图片的宽错误';
        	return false;
        }
        if ($srcHeight<1) {
        	$this->_error = '源图片的高错误';
        	return false;
        }
        if (!$dstWidth && !$dstHeight) {
        	return 1;
        }
        if ($srcWidth <= $dstWidth && $srcHeight <= $dstHeight) {
        	return 1;
        }
        if ($dstWidth!=null && $dstHeight==null) {
        	if ($dstWidth<1) {
        		$this->_error = '目标图片的宽必须是大于零的整数';
        		return false;
        	}
        	return $dstWidth/$srcWidth;
        }
        if ($dstHeight!=null && $dstWidth==null) {
        	if ($dstHeight<1) {
        		$this->_error = '目标图片的高必须是大于零的整数';
        		return false;
        	}
        	return $dstHeight/$srcHeight;
        }
        if ($dstWidth<1 || $dstHeight<1) {
        	$this->_error = '目标图片的宽和高必须是大于零的整数！';
        	return false;
        }
        if ($srcWidth*$dstHeight > $srcHeight*$dstWidth) {
            if ($minification == 'small') { // 求得小的缩小比例（ratio）
            	$ratio = $dstHeight/$srcHeight;	
            } else { // 求得大的缩小比例（ratio）
                $ratio = $dstWidth/$srcWidth;
            }
        } else {
        	if ($minification == 'small') { // 求得小的缩小比例（ratio）
        		$ratio = $dstWidth/$srcWidth;
        	} else { // 求得大的缩小比例（ratio）
        		$ratio = $dstHeight/$srcHeight;
        	}	
        }
        return $ratio;
    }

	/**
	 * 生成缩略图
	 * @param integer $width 目标宽
	 * @param integer $height 目标高
	 * @param boolean $bestfit 是否等比例压缩
	 */
	abstract public function thumbnail($width = null, $height = null, $bestfit = true);
	
    /**
     * 裁剪图片
     * @param integer $width 裁剪宽度
     * @param integer $height 裁剪高度
     * @param integer $x 裁剪坐标轴x
     * @param integer $y 裁剪坐标轴y
     * @return boolean
     */
    abstract public function crop($width = 0, $height = 0, $x = 0, $y = 0);
	
    /**
     * 添加文字水印
     * @param string $text 水印文字
     * @param string $place 水印位置
     * @param integer $x 水印位置X轴
     * @param integer $y 水印位置Y轴
     * @param integer $angle 文字旋转角度
     * @param array $style 文字样式
     * @return boolean
     */
    abstract public function watermarkText($text, $place = null, $x = 15, $y = 25, $angle = 0, array $style = array());

    /**
     * 添加图片水印
     * @param string $watermarkPath 水印图片
     * @param string $place 水印位置
     * @param integer $x 水印位置偏移量X
     * @param integer $y 水印位置偏移量Y
     * @param integer $alpha 水印图片的透明度
     * @param integer $angle 水印图片旋转角度
     */
    abstract public function watermarkImage($watermarkPath, $place = 'southeast', $x = 18, $y = 18, $alpha = 52, $angle = 0);

    /**
     * 水平或垂直翻转图片
     * @param string $hv
     * @return boolean
     */
    abstract public function rotateHV($hv = 'H');

    /**
     * 任意角度旋转图片
     * @param integer $angle 旋转角度
     * @param string $color 背景色
     */
    abstract public function rotate($angle, $color = 'transparent');
    
    /**
     * 调整图像大小,等比例缩放图片后进行裁剪，即生成指定大小的图片，多余部分进行裁剪
     * @param integer $width
     * @param integer $height
     * @param string $tooWideCutPosition 太宽了进行裁剪的位置
     * @param string $heightPlace 太高了进行裁剪的位置
     * @return boolean
     */
    abstract public function resize($width, $height, $tooWideCutPosition = 'center', $tooHighCutPosition = 'center');
    
    /**
     * 读取一张图片
     * @param string $targetPath
     */
    abstract public function read($targetPath);
    
    /**
     * 输出到浏览器
     */
    abstract public function show();
	
	/**
	 * 保存（写入）图片
	 * @param string $targetPath 目标图片路径
	 * @param boolean $flag (isCover) 是否覆盖已存在的图
	 * @return boolean
	*/
	abstract public function write($targetPath = null, $flag = true);

	/**
	 * 判断是否存在图像资源
	 * @return boolean
	 */
	protected function _isExistIm() {
	    if (is_resource($this->_im) || $this->_im instanceof Imagick) {
	        return true;
	    } else {
	        if (!$this->_error) {
	            $this->_error = '无法获取图像资源';
	        }
	        return false;
	    }
	}
	
	/**
	 * 创建目录（文件夹）路径下的所有目录 , created all folder
	 * 这里的方法和 Common_Tool::createdDirectory($directoryPath) 是一样的
	 * @param string $directoryPath 要创建的文件目录（文件夹）路径，只能是绝对路径，或相对路径，
	 * 不能是zend的重写路径（如 ‘/upload/img/’）或http://这样的路径
	 * @return boolean
	*/
    protected function createdDirectory($directoryPath) { // created all folder
    	$directoryPath = trim($directoryPath);
		if (!$directoryPath) {
			$this->_error = '创建目录路径为空！';
			return false;
		}
		$directoryPath = str_replace('\\', '/', $directoryPath);
		if ($directoryPath[strlen($directoryPath)-1] == '/') {
			$directoryPath = substr($directoryPath, 0, -1);
		}
		
		/* $pattern = '/([a-zA-Z]+:\/)?/';
		preg_match($pattern, $directoryPath, $prefixFolder);
		$createdFolder = $prefixFolder[0];
		$directoryPath = str_replace($createdFolder, '', $directoryPath); */ // 貌似这里为了兼容windows才写的，后面改版没有考虑进去 
		
        // 只因为有时file_exists明明有的目录，但是还是返回false，后改为从后面截取判断(都是安全模式惹的祸)
        // 后来经证实，在php安全模式下，有很多函数受到限制，所以需要设置php环境成非安全模式，设置 safe_mode (php可以用ini_get('safe_mode')查看)
        if (is_dir($directoryPath)) {
        	return true;
        }
        $arrayFolder = explode('/', $directoryPath);
        $sum = count($arrayFolder);
        $createdPath = array();
        for ($i=($sum-1); $i>0; $i--) {
        	array_unshift($createdPath, $arrayFolder[$i]);
        	$directoryPath = str_replace('/' . $arrayFolder[$i], '', $directoryPath);
            if (is_dir($directoryPath)) {
                break;
        	}
        }
        foreach ($createdPath as $path) {
        	if (is_writeable($directoryPath)) {
        		$directoryPath .= '/' . $path;
        		mkdir($directoryPath);
//         		mkdir($directoryPath, 0777, true); // 安全模式下有问题
//         		chmod($directoryPath, 0777);
        	} else {
        		$this->_error = "无法新建目标文件夹（目录）；请确认目标文件夹路径是否正确或目标文件夹 $directoryPath 是否有写的权限！";
        		return false;
        	}
        }
        return true; // 不知道以后这个地方还会有问题不
		
		/* foreach ($arrayFolder as $folder) {
			$folder = trim($folder);
			$parentFolder = $createdFolder;
			$createdFolder .= $folder . '/';
			if ($createdFolder == '/') {
				continue;
			}
			$createdFolder = '/home/';
			
			if (!file_exists($createdFolder)) {
				if (!is_writeable($parentFolder)) {
					$this->setError("无法新建目标文件夹（目录）；请确认目标文件夹路径是否正确或目标文件夹 $parentFolder 是否有写的权限！");
					return false;
				}
				mkdir($createdFolder);
			}
		} */

	}
    
}
