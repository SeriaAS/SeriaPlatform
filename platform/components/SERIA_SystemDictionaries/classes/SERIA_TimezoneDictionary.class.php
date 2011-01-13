<?php
	class SERIA_TimezoneDictionary extends SERIA_Dictionary implements IteratorAggregate
	{
		protected $_data;

		public function __construct() {
		}

		function load() {
			if($this->_data) return $this->_data;
			$this->_data = array();
			$data = DateTimeZone::listIdentifiers();
			foreach($data as $tz)
			{
				$this->_data[$tz] = str_replace("_", " ", $tz);
			}
			asort($this->_data);
			return $this->_data;
		}

		function get($key) {
			$this->load();
			if(isset($this->_data[$key]))
				return $this->_data[$key];
			return NULL;
		}

		function getIterator() {
			$this->load();
			return new ArrayIterator($this->_data);
		}
	}
