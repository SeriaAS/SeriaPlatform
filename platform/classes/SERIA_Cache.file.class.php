<?php
	class SERIA_Cache implements SERIA_ICache // File based caching
	{
		private $namespace;
		private $root;

		public function __construct($namespace = '') {
			$this->namespace = strtolower($namespace);
			$this->root = SERIA_CACHE_ROOT.'/'.md5($this->namespace);



			if(!is_dir($this->root))
			{
				$mask = umask(0);
				mkdir($this->root, 0700, true);
				umask($mask);
			}
		}

		public function set($name, $value, $expiry = 1800) {
			return file_put_contents($this->root.'/'.md5($name), serialize(array(
				'name:'.$name => $value,  // theoretically two names can give the same md5 hash
				'expires' => time()+$expiry,
			)));
		}

		public function get($name) {
			$fn = $this->root.'/'.md5($name);
			if(file_exists($fn))
			{
				$token = unserialize(file_get_contents($fn));
				if($token['expires']<time())
				{ // token has expired
					unlink($fn);
					return null;
				}
				return $token['name:'.$name];
			}
			return null;
		}
	}
