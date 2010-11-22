<?php
	class SERIA_Memcached {
		private static $servers = array();
		private $namespace = '';
		private static $cache = null;
		
		/**
		 * Construct a new object.
		 *
		 * @param string $namespace An optional namespace for keys.
		 */
		public function __construct($namespace = '') {
			$this->namespace = $namespace;
			
			if (!sizeof(self::$servers)) {
				/*
				 * The class_exists seems to work around a segmentation fault upon calling
				 * method_exists under certain unknown circumstances. This is not easily
				 * reproduced as it seems unrelated code can provoke this. Might
				 * well be caused by referencing uninitialized memory.
				 */
				class_exists('SERIA_MemcacheServers');
				if (method_exists('SERIA_MemcacheServers', 'find_all_by_enabled')) {
					try {
						self::$servers = SERIA_MemcacheServers::find_all_by_enabled(1)->toArray();
						
						$servers = array();
						foreach (self::$servers as $server) {
							if ($server->disableduntil <= time()) {
								$servers[] = $server->address . ':' . $server->port;
							}
						}
						
						if (sizeof($servers)) {
							self::$cache = new SERIA_Memcached_Client(
								array(
									'servers' => $servers,
									'compress_threshold' => 2147483648,
									'persistant' => false,
									'debug' => 0
								)
							);
						} else {
							self::$cache = null;
						}
					} catch (Exception $null) {
							$this->servers = array();
							self::$cache = null;
					}
				}
			}
		}
		
		/**
		 * Returns true if any memcache servers is available.
		 * 
		 * @return bool
		 *
		 */
		public function isAvailable() {
			return (self::$cache != null);
		}
		
		/**
		 * Get real key for relative key.
		 *
		 * @param string $var
		 * @return string
		 */
		private function getKey($var) {
			
			// Set memcached key to a application wide static random key if not defined or is set to old default value
			if (!defined('SERIA_MEMCACHED_KEY') || (SERIA_MEMCACHED_KEY == 'ax9a123dsf') || (!SERIA_MEMCACHED_KEY)) { 
				try {
					$key = substr(sha1(SERIA_DB_HOST . SERIA_DB_USER . SERIA_DB_PASSWORD . SERIA_DB_NAME . SERIA_PREFIX), 0, 10);
				} catch (Exception $null) {
					$key = md5(time());
				}
			} else {
				$key = SERIA_MEMCACHED_KEY;
			}
			return $key . '_' . $this->namespace . '_' . $var;
		}
		
		/**
		 * Adds a new value to the cache.
		 * 
		 * @param string var Key name
		 * @param mixed value Value
		 * @param int expire Expire time in seconds. 0/default => Never expire.
		 * @return bool
		 */
		public function set($var, $value, $expire = 0) {
			if (!self::$cache) {
				return false;
			}
			
			try {
				return self::$cache->set($this->getKey($var), $value, $expire);
			} catch (Exception $null) {
				self::$cache = null;
			}
		}
		
		/**
		 * Gets a object from the cache. Returns value, or null if not found.
		 * 
		 * @param string var Key name.
		 * @return int
		 */
		public function get($var) {
			if (!self::$cache) {
				return null;
			}
			
			try {
				$data = self::$cache->get($this->getKey($var));
				if ($data === false) {
					throw new SERIA_Exception('Memcached got an unexpected value.');
					self::$cache = false;
				} else {
					return $data;
				}
			} catch (Exception $null) {
				self::$cache = null;
			}
			
			throw new SERIA_Exception('Memcached not available');
		}
	}
?>