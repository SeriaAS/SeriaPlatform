<?php
	/**
	*	Special class that prevents loading instances, when they are not needed -
	*	for example if an instance
	*/
	class SERIA_FluentObjectProxy {
		private $_className, $_id, $_instance = NULL;
		function __construct($className, $id)
		{
			$this->_className = $className;
			$this->_id = $id;
		}

		public function FluentProxyInfo()
		{
			return array('className' => $this->_className, 'id' => $this->_id, 'instance' => $this->_instance);
		}

		protected function _load()
		{
			if($this->_instance === NULL)
				$this->_instance = SERIA_Fluent::load($this->_className, $this->_id);
		}

		function __get($name)
		{
			$this->_load();
			return $this->_instance->$name;
		}

		function __set($name, $value)
		{
			$this->_load();
			$this->_instance->$name = $value;
		}

		function __call($name, $args)
		{
			$this->_load();
			return call_user_func_array(array($this->_instance, $name), $args);
		}

		function __callStatic($name, $args)
		{
			$this->_load();
			return call_user_func_array(array($this->_className, $name), $args);
		}
	}
