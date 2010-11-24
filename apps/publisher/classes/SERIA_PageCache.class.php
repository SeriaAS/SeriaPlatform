<?php
	class SERIA_PageCache {

		protected $_cache, $_name, $_ttl, $_data, $_isCaching;

		public function __construct($name, $ttl = 300) {
			$this->_cache = new SERIA_Cache('pagecache');
			$this->_name = $name;
			$this->_ttl = $ttl;
		}
		
		public function start() {
			if($this->_data = $this->_cache->get($this->_name))
				return false;

			$this->_isCaching = true;
			ob_start();
			return true;
		}
		
		public function end() {
			if($this->_isCaching)
			{
				$data = ob_get_contents();
				$this->_cache->set($this->_name, $data);
				ob_end_clean();
				return $data;
			}
			if(!$this->_data) throw new SERIA_Exception('Called SERIA_PageCache->end() without first calling SERIA_PageCache->start()');
			return $this->_data;
		}
	}
?>
