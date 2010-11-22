<?php

class SERIA_BMLImage extends SERIA_BMLElement
{
	private $previewId = false;
	private $src = false;

	public $noEndTag = true;

	public function __construct($src, $alt)
	{
		parent::__construct('img', array('src' => $src, 'alt' => $alt));
		$this->src = $src;
	}
	public function thumbnailWithPreview($width=false, $height=false, $previewWidth=false, $previewHeight=false)
	{
		if ($width === false && $height === false)
			$width = '100%';
		if ($width !== false)
			$this->setWidth($width);
		if ($height !== false)
			$this->setHeight($height);
		$this->previewId = 'preview'.mt_rand();
		if ($previewWidth !== false || $previewHeight !== false) {
			$previewAttr = '';
			if ($previewWidth !== false) {
				$len = strlen($previewWidth);
				if ($previewWidth[$len-1] != 'x')
					$previewWidth .= 'px';
				$previewAttr .= 'image.style.width = \''.$previewWidth.'\';';
			}
			if ($previewHeight !== false) {
				$len = strlen($previewHeight);
				if ($previewHeight[$len-1] != 'x')
					$previewHeight .= 'px';
				$previewAttr .= 'image.style.height = \''.$previewHeight.'\';';
			}
		} else
			$previewAttr = '';
		$inScript = "(function () { var e = event || window.event; if (document.getElementById('".$this->previewId."')) return; var div = document.createElement('div'); div.id = '".$this->previewId."'; div.innerHTML = '<h1 style=\\'padding: 0px; margin: 0px; color: black;\\'>"._t('Preview')."</h1>'; var image = new Image(); image.src = '".$this->src."'; ".$previewAttr." div.style.background = '#EEEEEE'; div.style.position = 'absolute'; div.style.left = event.pageX + 'px'; div.style.top = (event.pageY + 10) + 'px'; div.appendChild(image); document.body.appendChild(div); } )();";
		$outScript = " (function () { var elem = document.getElementById('".$this->previewId."'); if (elem) document.body.removeChild(elem); } )();";
		$this->setAttr('onmouseover', $inScript);
		$this->setAttr('onmouseout', $outScript);
	}
}

?>