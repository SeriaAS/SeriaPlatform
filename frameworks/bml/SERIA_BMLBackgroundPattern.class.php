<?php

class SERIA_BMLBackgroundPattern
{
	protected $inputImage = null;
	protected $width = 0;
	protected $height = 0;

	protected $leftGen = null;
	protected $rightGen = null;
	protected $topGen = null;
	protected $bottomGen = null;

	protected $topLeftGen = null;
	protected $topRightGen = null;
	protected $bottomLeftGen = null;
	protected $bottomRightGen = null;

	protected  $repeatReferencePoint = false;

	public function __construct($inputImage, $repeatReferencePoint=false)
	{
		$this->inputImage = $inputImage;
		$this->width = imagesx($inputImage);
		$this->height = imagesy($inputImage);
		$this->repeatReferencePoint = $repeatReferencePoint;
	}

	public function left($height=false, $norecurse=false)
	{
		if ($height === false)
			$height = $this->height;
		if (!$height || $height <= 0)
			throw new Exception('Searching for a pattern of this height does not make sense. ('.$height.')');
		$imageheight = $height;
		if ($imageheight < $this->height)
			$imageheight = $this->height;
		$patwidth = 1;
		for ($x = 1; $x < $this->width; $x++) {
			/* predict line copy */
			$predict = $x % $patwidth;
			/*
			 * Scan line (together with predicting code)...
			 */
			for ($y = 0; $y < $imageheight; $y++) {
				$predicted = imagecolorat($this->inputImage, $predict, $y);
				$actual = imagecolorat($this->inputImage, $x, $y);
				if ($predicted != $actual) {
					$patwidth = $x + 1;
					break;
				}
			}
		}
		$obj = new SERIA_BMLBackgroundPatternBlock;
		$obj->width = $patwidth;
		$obj->height = $height;
		$obj->repeat = 'horizontal';
		$obj->image = imagecreatetruecolor($obj->width, $imageheight);
		imagecopy($obj->image, $this->inputImage, 0, 0, 0, 0, $obj->width, $imageheight);
		if ($imageheight != $height) {
			if ($norecurse)
				throw new Exception('Recurse protection has been tripped.');
			$secondaryCheck = new SERIA_BMLBackgroundPattern($obj->image, $this->repeatReferencePoint);
			$secondaryPattern = $secondaryCheck->bottom($obj->width, true);
			$obj->image = $secondaryPattern->appendToCopy($obj->image, $obj->height - $imageheight);
		}
		switch ($this->repeatReferencePoint) {
			case 'right':
			case 'topright':
			case 'bottomright':
				$offset = $this->width % $obj->width;
				if ($offset != 0) {
					/*
					 * Rotate $offset pixels to left..
					 */
					$obj->horizontalRotate(0-$offset);
				}
				break;
		}
		return $obj;
	}
	public function right($height=false, $norecurse=false)
	{
		if ($height === false)
			$height = $this->height;
		if (!$height || $height <= 0)
			throw new Exception('Searching for a pattern of this height does not make sense. ('.$height.')');
		$imageheight = $height;
		if ($imageheight < $this->height)
			$imageheight = $this->height;
		$patwidth = 1;
		for ($x = 1; $x < $this->width; $x++) {
			/* predict line copy */
			$predict = $this->width - ($x % $patwidth) - 1;
			/* actual x */
			$xval = $this->width - $x - 1;
			/*
			 * Scan line (together with predicting code)...
			 */
			for ($y = 0; $y < $imageheight; $y++) {
				$predicted = imagecolorat($this->inputImage, $predict, $y);
				$actual = imagecolorat($this->inputImage, $xval, $y);
				if ($predicted != $actual) {
					$patwidth = $x + 1;
					break;
				}
			}
		}
		$obj = new SERIA_BMLBackgroundPatternBlock;
		$obj->width = $patwidth;
		$obj->height = $height;
		$obj->repeat = 'horizontal';
		$obj->image = imagecreatetruecolor($obj->width, $imageheight);
		imagecopy($obj->image, $this->inputImage, 0, 0, $this->width - $patwidth, 0, $obj->width, $imageheight);
		if ($imageheight != $height) {
			if ($norecurse)
				throw new Exception('Recurse protection has been tripped.');
			$secondaryCheck = new SERIA_BMLBackgroundPattern($obj->image, $this->repeatReferencePoint);
			$secondaryPattern = $secondaryCheck->bottom($obj->width, true);
			$obj->image = $secondaryPattern->appendToCopy($obj->image, $obj->height - $imageheight);
		}
		switch ($this->repeatReferencePoint) {
			case 'left':
			case 'topleft':
			case 'bottomleft':
				$offset = $this->width % $obj->width;
				if ($offset != 0) {
					/*
					 * Rotate $offset pixels to right..
					 */
					$obj->horizontalRotate($offset);
				}
				break;
		}
		return $obj;
	}

	public function bottom($width=false, $norecurse=false)
	{	
		if ($width === false)
			$width = $this->width;
		if (!$width || $width <= 0)
			throw new Exception('Searching for a pattern of this width does not make sense. ('.$width.')');
		$imagewidth = $width;
		if ($imagewidth < $this->width)
			$imagewidth = $this->width;
		$patheight = 1;
		for ($y = 1; $y < $this->height; $y++) {
			/* predict line copy */
			$predict = $y % $patheight;
			/*
			 * Scan line (together with predicting code)...
			 */
			for ($x = 0; $x < $imagewidth; $x++) {
				$predicted = imagecolorat($this->inputImage, $x, $predict);
				$actual = imagecolorat($this->inputImage, $x, $y);
				if ($predicted != $actual) {
					$patheight = $y + 1;
					break;
				}
			}
		}
		$obj = new SERIA_BMLBackgroundPatternBlock;
		$obj->height = $patheight;
		$obj->width = $width;
		$obj->repeat = 'vertical';
		$obj->image = imagecreatetruecolor($imagewidth, $obj->height);
		imagecopy($obj->image, $this->inputImage, 0, 0, 0, $this->height - $patheight, $imagewidth, $obj->height);
		if ($imagewidth != $width) {
			if ($norecurse)
				throw new Exception('Recurse protection has been tripped.');
			$secondaryCheck = new SERIA_BMLBackgroundPattern($obj->image, $this->repeatReferencePoint);
			$secondaryPattern = $secondaryCheck->right($obj->height, true);
			$obj->image = $secondaryPattern->appendToCopy($obj->image, $obj->width - $imagewidth);
		}
		switch ($this->repeatReferencePoint) {
			case 'top':
			case 'topright':
			case 'topleft':
				$offset = $this->height % $obj->height;
				if ($offset != 0) {
					/*
					 * Rotate $offset pixels to right..
					 */
					$obj->verticalRotate($offset);
				}
				break;
		}
		return $obj;
	}
	public function top($width=false, $norecurse=false)
	{
		if ($width === false)
			$width = $this->width;
		if (!$width || $width <= 0)
			throw new Exception('Searching for a pattern of this width does not make sense. ('.$width.')');
		$imagewidth = $width;
		if ($imagewidth < $this->width)
			$imagewidth = $this->width;
		$patheight = 1;
		for ($y = 1; $y < $this->height; $y++) {
			/* predict line copy */
			$predict = $y % $patheight;
			/*
			 * Scan line (together with predicting code)...
			 */
			for ($x = 0; $x < $imagewidth; $x++) {
				$predicted = imagecolorat($this->inputImage, $x, $predict);
				$actual = imagecolorat($this->inputImage, $x, $y);
				if ($predicted != $actual) {
					$patheight = $y + 1;
					break;
				}
			}
		}
		$obj = new SERIA_BMLBackgroundPatternBlock;
		$obj->height = $patheight;
		$obj->width = $width;
		$obj->repeat = 'vertical';
		$obj->image = imagecreatetruecolor($imagewidth, $obj->height);
		imagecopy($obj->image, $this->inputImage, 0, 0, 0, 0, $imagewidth, $obj->height);
		if ($imagewidth != $width) {
			if ($norecurse)
				throw new Exception('Recurse protection has been tripped.');
			$secondaryCheck = new SERIA_BMLBackgroundPattern($obj->image, $this->repeatReferencePoint);
			$secondaryPattern = $secondaryCheck->right($obj->height, true);
			$obj->image = $secondaryPattern->appendToCopy($obj->image, $obj->width - $imagewidth);
		}
		switch ($this->repeatReferencePoint) {
			case 'bottom':
			case 'bottomright':
			case 'bottomleft':
				$offset = $this->height % $obj->height;
				if ($offset != 0) {
					/*
					 * Rotate $offset pixels to right..
					 */
					$obj->verticalRotate(0-$offset);
				}
				break;
		}
		return $obj;
	}

	public function __get($name)
	{
		switch ($name) {
			case 'image':
				$img = imagecreatetruecolor($this->width, $this->height);
				imagecopy($img, $this->inputImage, 0, 0, 0, 0, $this->width, $this->height);
				return $img;
			case 'left':
				if ($this->leftGen === null)
					$this->leftGen = $this->left();
				return $this->leftGen;
			case 'right':
				if ($this->rightGen === null)
					$this->rightGen = $this->right();
				return $this->rightGen;
			case 'top':
				if ($this->topGen === null)
					$this->topGen = $this->top();
				return $this->topGen;
			case 'bottom':
				if ($this->bottomGen === null)
					$this->bottomGen = $this->bottom();
				return $this->bottomGen;
			case 'topLeft':
				if ($this->topLeftGen === null) {
					$topgenerator = new SERIA_BMLBackgroundPattern($this->top->image, $this->repeatReferencePoint);
					$this->topLeftGen = $topgenerator->left;
					$this->topRightGen = $topgenerator->right;
				}
				return $this->topLeftGen;
			case 'topRight':
				if ($this->topRightGen === null) {
					$topgenerator = new SERIA_BMLBackgroundPattern($this->top->image, $this->repeatReferencePoint);
					$this->topLeftGen = $topgenerator->left;
					$this->topRightGen = $topgenerator->right;
				}
				return $this->topRightGen;
			case 'bottomLeft':
				if ($this->bottomLeftGen === null) {
					$bottomgenerator = new SERIA_BMLBackgroundPattern($this->bottom->image, $this->repeatReferencePoint);
					$this->bottomLeftGen = $bottomgenerator->left;
					$this->bottomRightGen = $bottomgenerator->right;
				}
				return $this->bottomLeftGen;
			case 'bottomRight':
				if ($this->bottomRightGen === null) {
					$bottomgenerator = new SERIA_BMLBackgroundPattern($this->bottom->image, $this->repeatReferencePoint);
					$this->bottomLeftGen = $bottomgenerator->left;
					$this->bottomRightGen = $bottomgenerator->right;
				}
				return $this->bottomRightGen;
			default:
				throw new Exception('Property not found: '.$name);
		}
	}
}

?>