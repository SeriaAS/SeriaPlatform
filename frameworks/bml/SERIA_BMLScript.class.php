<?php

class SERIA_BMLScript extends SERIA_BMLElement
{
	public function __construct($script, $type='text/javascript')
	{
		parent::__construct('script', array('type' => $type));
		$this->addChild('<!-- '.$script.' -->');
	}
}

?>