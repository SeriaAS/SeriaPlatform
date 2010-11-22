<?php

class SERIA_BMLBackgroundPatternBlock
{
	public $image = null;
	public $width = 0;
	public $height = 0;
	public $repeat = 'both'; /* 'horizontal', 'vertical' or 'both' */

	public function appendToCopy($imageobj, $amount)
	{
		if ($amount <= 0)
			throw new Exception('Amount <=0 doesn\'t make sense.');
		$w = imagesx($imageobj);
		$h = imagesy($imageobj);
		switch ($this->repeat) {
			case 'both':
				throw new Exception('This function does not do repeat:both.');
			case 'vertical':
				$newimgobj = imagecreatetruecolor($w, $h+$amount);
				imagecopy($newimgobj, $imageobj, 0, 0, 0, 0, $w, $h);
				$block = $this->height;
				if ($block <= 0)
					throw new Exception('Entering infinite loop');
				for ($i = 0; $i < $amount; $i += $block) {
					$blk = min($block, $amount - $i);
					imagecopy($newimgobj, $this->image, 0, $h + $i, 0, 0, min($this->width, $w), $blk);
				}
				break;
			case 'horizontal':
				$newimgobj = imagecreatetruecolor(imagesx($imageobj)+$amount, imagesy($imageobj));
				imagecopy($newimgobj, $imageobj, 0, 0, 0, 0, $w, $h);
				$block = $this->width;
				if ($block <= 0)
					throw new Exception('Entering infinite loop');
				for ($i = 0; $i < $amount; $i += $block) {
					$blk = min($block, $amount - $i);
					imagecopy($newimgobj, $this->image, $w + $i, 0, 0, 0, $blk, min($this->height, $h));
				}
				break;
			default:
				throw new Exception('Invalid repeat value (\'horizontal\', \'vertical\' and \'both\' are valid).');
		}
		return $newimgobj;
	}
	public function prependToCopy($imageobj, $amount)
	{
		if ($amount <= 0)
			throw new Exception('Amount <=0 doesn\'t make sense.');
		$w = imagesx($imageobj);
		$h = imagesy($imageobj);
		switch ($this->repeat) {
			case 'both':
				throw new Exception('This function does not do repeat:both.');
			case 'vertical':
				$newimgobj = imagecreatetruecolor($w, $h+$amount);
				imagecopy($newimgobj, $imageobj, 0, $amount, 0, 0, $w, $h);
				$block = $this->height;
				for ($i = 0; $i < $amount; $amount += $block) {
					$blk = min($block, $amount - $i);
					imagecopy($newimgobj, $this->image, 0, $amount - $i - $blk, 0, $block - $blk, min($this->width, $w), $blk);
				}
				break;
			case 'horizontal':
				$newimgobj = imagecreatetruecolor(imagesx($imageobj)+$amount, imagesy($imageobj));
				imagecopy($newimgobj, $this->image, $amount, 0, 0, 0, $w, $h);
				$block = $this->width;
				for ($i = 0; $i < $amount; $amount += $block) {
					$blk = min($block, $amount - $i);
					imagecopy($newimgobj, $this->image, $amount - $i - $blk, 0, $block - $blk, 0, $blk, min($this->height, $h));
				}
				break;
			default:
				throw new Exception('Invalid repeat value (\'horizontal\', \'vertical\' and \'both\' are valid).');
		}
		return $newimgobj;
	}
	public function horizontalRotate($offset)
	{
		if ($offset < 0) {
			$offset = 0 - $offset;
			$offset = $this->width-($offset % $this->width);
		} else
			$offset %= $this->width;
		if (!$offset)
			return;
		$newimg = imagecreatetruecolor($this->width, $this->height);
		/*
		 * Just copy the two blocks..
		 */
		$block = $this->width - $offset;
		imagecopy($newimg, $this->image, $offset, 0, 0, 0, $block, $this->height);
		imagecopy($newimg, $this->image, 0, 0, $block, 0, $offset, $this->height);
		$this->image = $newimg;
	}
	public function verticalRotate($offset)
	{
		if ($offset < 0) {
			$offset = $this->height-((0-$offset) % $this->height);
		} else
			$offset %= $this->height;
		if (!$offset)
			return;
		$newimg = imagecreatetruecolor($this->width, $this->height);
		/*
		 * Just copy the two blocks..
		 */
		$block = $this->height - $offset;
		imagecopy($newimg, $this->image, 0, $offset, 0, 0, $this->width, $block);
		imagecopy($newimg, $this->image, 0, 0, 0, $block, $this->width, $offset);
		$this->image = $newimg;
	}
}

?>