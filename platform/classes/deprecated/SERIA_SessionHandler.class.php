<?php
	class SERIA_SessionHandler {
		protected static $cache;
		public static $ttl = 3600;
		public static $initialized = false;
		
		public static function init() {
			if (!self::$initialized) {
				session_set_save_handler(array('SERIA_SessionHandler', 'open'), array('SERIA_SessionHandler', 'close'),
				                         array('SERIA_SessionHandler', 'read'), array('SERIA_SessionHandler', 'write'),
				                         array('SERIA_SessionHandler', 'destroy'), array('SERIA_SessionHandler', 'gc'));
				self::$cache = new SERIA_Cache('sessionHandler', true);
				self::$ttl = SERIA_SESSION_TTL;
				
				session_cache_limiter("nocache");
				session_cache_expire(0);
				session_start();
				
				self::$initialized = true;
			}
		}
		
		public static function open($savePath, $sessionName) {
			return true;
		}
		
		public static function close() {
			return true;
		}
		
		public static function read($id) {
			return (string) self::$cache->get($id);
		}
		
		public static function write($id, $data) {
			if (self::$cache->set($id, $data, self::$ttl)) {
				return true;
			}
			
			return false;
		}
		
		public static function destroy($id) {
			return (bool) self::$cache->set($id, '', 1);
		}
		
		public static function gc($maxLifeTime) {
			return true;
		}
	}
?>