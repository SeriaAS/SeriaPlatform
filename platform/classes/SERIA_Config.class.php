<?php

	/**
	*	IMPORTANT!!!
	*
	*	- Never use this for settings that must be configured by a designer, use _config.php instead.
	*	- Use this primarily for settings that should be modifyable by the customer/site administrator.
	*	- A blank installation should always work.
	*/
	class SERIA_Config
	{
		private $domain;

		function __construct($domain)
		{
			$this->domain = $domain;
		}

		function get($name, $default=null)
		{
			$value = SERIA_Base::getParam('config:'.$this->domain.':'.$name);

			if($value === null)
				return $default;

			return $value;			
		}

		function set($name, $value)
		{
			return SERIA_Base::setParam('config:'.$this->domain.':'.$name, $value);
		}
	}
