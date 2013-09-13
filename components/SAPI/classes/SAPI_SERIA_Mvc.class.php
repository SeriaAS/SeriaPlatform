<?php

class SAPI_SERIA_Mvc
{
	protected $class;

	public function __construct($class)
	{
		$this->class = $class;
		if (!class_exists($this->class))
			throw new SERIA_Exception('The class does not exist: '.$this->class);
		if (!in_array('SERIA_IApiAccess', class_implements($class)))
			throw new SERIA_Exception($class.' is not accessible trough this API.', SERIA_Exception::NOT_FOUND);
	}

	public function get($get)
	{
		$params = $get;

		if (isset($params['apiPath']))
			unset($params['apiPath']);
		if (isset($params['apiTime']))
			unset($params['apiTime']);
		if (isset($params['apiHash']))
			unset($params['apiHash']);
		if (isset($params['apiSalt']))
			unset($params['apiSalt']);
		if (isset($params['apiAuth']))
			unset($params['apiAuth']);

		if(!isset($params['start']))
			$params['start'] = 0;
		if(!isset($params['length']))
			$params['length'] = 1000;
		return call_user_func(array($this->class, 'apiQuery'), $params);
	}
	public function post($post, $get)
	{
		$params = $post;

		if (isset($params['apiTime']))
			unset($params['apiTime']);
		if (isset($params['apiHash']))
			unset($params['apiHash']);
		if (isset($params['apiSalt']))
			unset($params['apiSalt']);
		if (isset($params['apiAuth']))
			unset($params['apiAuth']);

		if(!isset($params['start']))
			$params['start'] = 0;
		if(!isset($params['length']))
			$params['length'] = 1000;
		return call_user_func(array($this->class, 'apiQuery'), $params);
	}
}