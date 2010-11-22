<?php
	class SERIA_PageCache extends SERIA_Cache {
		protected $enabled = false;
		protected $name = '';
		protected $ttl = 300;
		protected $data;
		
		public function __construct($name, $ttl = 300) {
			$this->ttl = $ttl;
			$this->name = $name;
			parent::__construct('pagecache');
		}
		
		public function start() {
			$name = $this->name;
			if (!$name) {
				$this->enabled = false;
			} else {
				$this->name = $name;
				
				if (($fromCache = $this->get($name)) !== null) {
					$this->data = $fromCache;
					return false;
				}
				
				$this->enabled = true;
				ob_start();
				return true;
			}
		}
		
		public function end() {
			if ($this->enabled) {
				$this->enabled = false;
				$content = ob_get_clean();
				$this->set($this->name, $content, $this->ttl);
				return $content;
			} else {
				return $this->data . ($this->data = '');
			}
		}
	}
?>