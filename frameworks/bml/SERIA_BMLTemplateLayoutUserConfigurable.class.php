<?php

class SERIA_BMLTemplateLayoutUserConfigurable
{
	protected $defaultConfiguration = array(
		'backgroundImage' => '',
		'leftWidth' => 0,
		'rightWidth' => 0,
		'topHeight' => 0,
		'bottomHeight' => 0,
		'expandHeight' => true,
		'contentAlign' => 'left',
		'printLogo' => false
	);
	protected $config = array();

	public function __construct()
	{
		$this->loadConfiguration();
	}

	protected function loadConfiguration()
	{
		foreach ($this->defaultConfiguration as $name => $val) {
			$setname = 'SERIA_AutoTemplate_'.$name;
			$this->config[$name] = SERIA_Base::getParam($name);
			if ($this->config[$name] === false || $this->config[$name] === null)
				$this->config[$name] = $val;
		}
	}
	protected function saveConfiguration()
	{
		foreach ($this->defaultConfiguration as $name => $val) {
			$setname = 'SERIA_AutoTemplate_'.$name;
			SERIA_Base::setParam($setname, $val);
		}
	}

	public function output($contents=array())
	{
		$layout = SERIA_BMLTemplateLayout::createObject($this->config['backgroundImage']);
		print_r($this->config);
		$layout->setParams($this->config);
		return $layout->output($contents);
	}
}

?>