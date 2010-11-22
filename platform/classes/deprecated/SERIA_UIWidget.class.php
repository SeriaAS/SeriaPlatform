<?php

class SERIA_UIWidget
{
	private static $widgets = array(
		'date_browser' => array(
			'class' => 'w_date_browser',
			'function' => 'w_apply_date_browser'
		),
		'file_browser' => array(
			'class' => 'w_file_browser',
			'function' => 'w_apply_file_browser'
		),
		'tinymce_editor' => array(
			'class' => 'w_tinymce',
			'function' => 'w_apply_tinymce'
		)
	);

	private $vars = array(
		'class' => false,
		'function' => false,
	);

	function __construct($name=false)
	{
		if ($name !== false) {
			foreach (self::$widgets as $wname => $wvars) {
				if ($wname == $name) {
					$vars = $wvars;
					return;
				}
			}
			throw new Exception(_t('Widget not found: %%WIDGET%%', array('WIDGET' => $name)));
		}
	}

	function set($name, $value)
	{
		if (isset($this->vars[$name]))
			$this->vars[$name] = $value;
		else
			throw new Exception(_t('Field not found: %%FIELD%%', array('FIELD' => $name)));
	}
	function get($name)
	{
		if (isset($this->vars[$name]))
			return $this->vars[$name];
		throw new Exception(_t('Field not found: %%FIELD%%', array('FIELD' => $name)));
	}

	function toArray()
	{
		$vars = array();
		foreach ($this->vars as $name => $value)
			$vars[$name] = $value;
		return $vars;
	}
	function fromArray($data)
	{
		foreach ($data as $name => $value)
			$this->vars[$name] = $value;
	}

	function toJSON()
	{
		$vars = $this->toArray();
		return SERIA_Lib::toJSON($vars);
	}
	function fromJSON($json)
	{
		$vars = SERIA_Lib::fromJSON($vars);
		$this->fromArray($vars);
	}

	static function getAllWidgets()
	{
		$widgets = array();
		foreach (self::$widgets as $name => $wvars)
			$widgets[] = new SERIA_UIWidget($name);
		return $widgets;
	}
	static function getAllWidgetsJSON()
	{
		$widgets = $this->getAllWidgets();
		foreach ($widgets as $index => $widget)
			$widgets[$index] = $widget->toArray();
		return SERIA_Lib::toJSON($widgets);
	}
}

?>