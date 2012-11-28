<?php
	class SERIA_Cache implements SERIA_ICache // File based caching
	{
		private $namespace;
		private $root;

		public function __construct($namespace = '') {
			$this->namespace = strtolower($namespace);
			$this->root = SERIA_CACHE_ROOT.'/'.SERIA_Sanitize::filename($this->namespace);

			if(!is_dir($this->root))
			{
				$mask = umask(0);
				mkdir($this->root, 0700, true);
				umask($mask);
			}
		}

		public function set($name, $value, $expiry = 1800) {
			$res = file_put_contents($this->root.'/'.SERIA_Sanitize::filename($name), serialize(array(
				'name:'.$name => $value,  // theoretically two names can give the same md5 hash
				'expires' => time()+$expiry,
			)));
//			echo $this->namespace.":".$name.":";
//			var_dump($res);
			return $res;
		}

		public function get($name) {
			$fn = $this->root.'/'.SERIA_Sanitize::filename($name);
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

		public function delete($name)
		{
			$fn = $this->root.'/'.SERIA_Sanitize::filename($name);
			if (file_exists($fn))
				unlink($fn);
		}

		public function deleteAll()
		{
			/*
			 * Mistake security barriers should be:
			 *  1. Non-empty $this->root check here.
			 *  2. File permissions on the server should not allow unlink of static data, and data owned by other users (than usually www-data).
			 *      => Avoid 777-permissions.
			 *  3. File-length check: only deletes files with name-length of 32 characters (md5-length).
			 *  4. User check: Run as root is forbidden. (UNIX only)
			 */
			if (!$this->root || strlen($this->root) <= 1)
				throw new SERIA_Exception('Cache root directory must not be an empty filename in delete-all-cache! (Mistake protection)');
			if (function_exists('posix_getuid') && posix_getuid() == 0)
				throw new SERIA_Exception('Delete-all cache method should not run as root! (Mistake protection)');
			$files = glob($this->root.'/????????????????????????????????'); /* 32 characters, matches only md5-length filenames. */
			foreach ($files as $cachefile) {
				SERIA_Base::debug('Deleted cache file: '.$cachefile);
				unlink($cachefile);
			}
		}
	}
