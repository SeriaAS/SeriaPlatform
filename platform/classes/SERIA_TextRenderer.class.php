<?php
	class SERIA_TextRenderer {
		private $defaultSize = 10;
		private $fontFilename = '';
		private $cacheDir;
		private $cacheHttpPath;
		
		public function __construct($fontFilename = '') {
			$this->fontFilename = $fontFilename;
			$this->cacheDir = realpath('files/');
			$this->cacheHttpPath = SERIA_HTTP_ROOT . '/files/';
		}
		
		public function setFont($filename) {
			$this->fontFilename = $filename;
		}
		
		public function setSize($size) {
			$this->defaultSize = $size;
		}
		
		
		public function text($text, $color, $size = null) {
			if ($size === null) {
				$size = $this->defaultSize;
			}
			
			if (!$this->cacheDir) {
				return false;
			}
			if (!file_exists($this->cacheDir)) {
				return false;
			}
			
			$color = str_replace('#', '', $color);
			$colorRed = hexdec(substr($color, 0, 2));
			$colorGreen = hexdec(substr($color, 2, 2));
			$colorBlue = hexdec(substr($color, 4, 2));
			
			list($lowerLeftX, $lowerLeftY, $lowerRightX, $lowerRightY, $upperRightX, $upperRightY, $upperLeftX, $upperLeftY) =
				imagettfbbox($size, 0, $this->fontFilename, $text);

			$width = $lowerRightX - $lowerLeftX;
			$height = $lowerRightY - $upperRightY; 
			
			$cacheFilename = md5($text . $width . $height . $size . $color) . '.png';
			
			if (!file_exists($this->cacheDir . '/' . $cacheFilename)) {
			
				$image = imagecreatetruecolor($width, $height);
				
				$colorTransparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
				imagefill($image, 0, 0, $colorTransparent);
				$colorAlloc = imagecolorallocate($image, $colorRed, $colorGreen, $colorBlue);
				imagettftext($image, $size, 0, 0, $height, $colorAlloc, $this->fontFilename, $text);
				
				imagealphablending($image, false);
				imagesavealpha($image, true);
				imagepng($image, $this->cacheDir . '/' . $cacheFilename);
			}
			
			return $this->createHtml($cacheFilename, $text);
		}
		
		protected function createHtml($cacheFilename, $text) {
			$tag = '<img src="' . $this->cacheHttpPath . '/' . $cacheFilename . '" alt="' . str_replace('"', '&quot;', $text) . '">';
			return $tag;
		}
	} 
?>