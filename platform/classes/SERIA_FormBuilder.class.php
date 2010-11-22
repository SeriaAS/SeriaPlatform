<?php

SERIA_Base::addFramework('bml');

class SERIA_FormBuilder extends SERIA_DivBuilder
{
	private $settings = false;
	private $head = '';
	private $tail = '';

	public function __construct($settings=array())
	{
		if (!isset($settings['layout'])) {
			if (!isset($settings['labelWidth']))
				$settings['labelWidth'] = 150;
			$divSettings = array(
				'columns' => array(
					array('width' => $settings['labelWidth']),
					array()
				)
			);
		} else {
			if ($settings['layout'] === 'SERIA_Article') {
				$divSettings = array(
					'columns' => array(
						array()
					)
				);
			} else
				throw new Exception('Undefined layout');
		}
		parent::__construct($divSettings);
		$this->settings = $settings;
	}
	public function addHidden($name, $value)
	{
		$this->head .= '<input type=\'hidden\' name=\''.htmlspecialchars($name).'\' value=\''.htmlspecialchars($value).'\'>';
	}
	public function addInput($labelText, $inputCode, $inputName=false)
	{
		$encap = array('', '');
		$laben = '<label';
		if (isset($this->settings['layout']) && $this->settings['layout'] == 'SERIA_Article') {
			$laben .= ' style=\'margin: 0px 0px; padding: 0px 0px;\'';
			$encap[0] .= '<h2>';
			$encap[1] = '</h2>'.$encap[1];
		}
		if ($inputName !== false)
			$this->addContent($laben.' for=\''.$inputName.'\'>'.$encap[0].$labelText.$encap[1].'</label>');
		else
			$this->addContent($laben.'>'.$encap[0].$labelText.$encap[1].'</label>');
		$this->addContent($inputCode);
	}
	public function setTail($content)
	{
		$this->tail = $content;
	}
	public function output()
	{
		if (isset($this->settings['method']))
			$method = $this->settings['method'];
		else
			$method = 'GET';
		$content = '';
		if ($method !== false) {
			$content .= '<form method=\''.$method.'\'>';
			$method = '</form>';
		} else
			$method = '';
		return $content . $this->head . parent::output() . $this->tail . $method;
	}
}

?>