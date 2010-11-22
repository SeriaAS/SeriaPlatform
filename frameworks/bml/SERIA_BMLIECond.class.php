<?php

class SERIA_BMLIECond extends SERIA_BMLElement
{
	protected $expr = 'IE';

	public function __construct($expr='IE')
	{
		parent::__construct(false);
		$this->noEndTag = false;
		$this->expr = $expr;
	}

	public function output()
	{
		$contents = "\n<!--[if ".$this->expr."]>\n";
		$contents .= parent::output();
		$contents .= "\n<![endif]-->\n";
		return $contents;
	}
}

?>
