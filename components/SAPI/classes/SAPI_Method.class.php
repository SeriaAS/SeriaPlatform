<?php

class SAPI_Method
{
	protected $class;
	protected $method;
	protected $avail;
	protected $callMethod;
	protected $params;

	public function __construct($class, $method)
	{
		$this->class = $class;
		$this->method = $method;
		if (!class_exists($this->class))
			throw new SERIA_Exception('The class does not exist: '.$this->class);
		$parentClass = get_parent_class($this->class);
		if ($parentClass != 'SAPI')
			throw new SERIA_Exception('This is not a SAPI class: '.$this->class.($parentClass ? ' extends '.$parentClass : ''));
		if (method_exists($this->class, $this->method))
			$avail = array('get', 'post', 'put', 'delete');
		else {
			$wants = array('get', 'post', 'put', 'delete');
			$avail = array();
			foreach ($wants as $want) {
				if (method_exists($this->class, $want.'_'.$this->method))
					$avail[] = $want;
			}
		}
		$this->avail = $avail;
	}

	public function callMethod()
	{
		$methodReflection = new ReflectionMethod($this->class, $this->callMethod);
		$reflectionParameters = $methodReflection->getParameters();
		$params = array();
		foreach ($reflectionParameters as $reflectionParameter) {
			if (isset($this->params[$reflectionParameter->name]))
				$params[] = $this->params[$reflectionParameter->name];
			else
				throw new SERIA_Exception('Expected parameter: '.$reflectionParameter->name);
		}
		return $methodReflection->invokeArgs(null, $params);
	}

	public function post($post, $get)
	{
		if (!in_array('post', $this->avail))
			throw new SERIA_Exception('POST is not available for this SAPI-method ('.$this->class.'/'.$this->method.').');
		$params = $get;
		foreach ($post as $name => $value)
			$params[$name] = $value;
		$this->params = $params;
		if (method_exists($this->class, 'post_'.$this->method))
			$this->callMethod = 'post_'.$this->method;
		else
			$this->callMethod = $this->method;
		return $this->callMethod();
	}
	public function get($params)
	{
		if (!in_array('get', $this->avail))
			throw new SERIA_Exception('GET is not available for this SAPI-method ('.$this->class.'/'.$this->method.').');
		$this->params = $params;
		if (method_exists($this->class, 'post_'.$this->method))
			$this->callMethod = 'post_'.$this->method;
		else
			$this->callMethod = $this->method;
		return $this->callMethod();
	}
	public function put($params)
	{
		if (!in_array('put', $this->avail))
			throw new SERIA_Exception('PUT is not available for this SAPI-method ('.$this->class.'/'.$this->method.').');
		$this->params = $params;
		if (method_exists($this->class, 'post_'.$this->method))
			$this->callMethod = 'post_'.$this->method;
		else
			$this->callMethod = $this->method;
		return $this->callMethod();
	}
	public function delete($params)
	{
		if (!in_array('delete', $this->avail))
			throw new SERIA_Exception('DELETE is not available for this SAPI-method ('.$this->class.'/'.$this->method.').');
		$this->params = $params;
		if (method_exists($this->class, 'post_'.$this->method))
			$this->callMethod = 'post_'.$this->method;
		else
			$this->callMethod = $this->method;
		return $this->callMethod();
	}
}
