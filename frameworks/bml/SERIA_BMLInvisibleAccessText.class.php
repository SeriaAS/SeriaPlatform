<?php

class SERIA_BMLInvisibleAccessText extends SERIA_BMLElement
{
	function __construct($text='')
	{
		parent::__construct('img', array('src' => SERIA_HTTP_ROOT.'/seria/icons/transparent/transparent.png', 'alt' => $text));
		$this->noEndTag = true; /* CAUTION!! */
		$this->setWidth(0);
		$this->setHeight(0);
		$this->setStyle('background', 'transparent');
	}
}

?>