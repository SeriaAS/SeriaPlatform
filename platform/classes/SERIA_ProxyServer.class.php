<?php
/**

        // session_cache_limiter('nocache');
        // Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
        // Expires: Thu, 19 Nov 1981 08:52:00 GMT
        // Pragma: no-cache

        session_cache_limiter('nostore');
        // No headers comes

        // session_cache_limiter('public');
        // Cache-Control:public, max-age=10800
        // Expires:Thu, 13 Mar 2014 14:01:04 GMT

        // session_cache_limiter('private');
        // Cache-Control: private, max-age=10800, pre-check=10800
        // Expires:Thu, 19 Nov 1981 08:52:00 GMT



//      session_start();
        echo "OK";

*	Class for handling proxy servers that work in front of Seria Platform.
*
*	Interface
*
*	init()
*		Sets cache control headers to the initial state, as defined by PHP.
*	publicCache($ttl=604800)
*		Sets time to live to $ttl, unless it's been set to a lower time to live earlier in the request
*		Sets cache limiter to public, unless it is set to a more restricted cache limiter earlier in the request.
*	privateCache($ttl=86400)
*		Sets time to live to $ttl, unless it's been set to a lower time to live earlier in the request
*		Sets cache limiter to public, unless it is set to a more restricted cache limiter earlier in the request.
*	noCache()
*		Sets time to live to 0.
*		Requests that the data is not cached.
*	noStore()
*		Sets time to live to 0.
*		Requests that the data is not stored. This can be a problem for content that should be loaded by browser
*		plugins, such as Flash. Often these plugins rely on the browser downloading the file, and then the plugin
*		loads the file from disk.
*/

if (SERIA_COMPATIBILITY<3) {
	require(__DIR__.'/SERIA_ProxyServer.2.class.php');
} else {
	class SERIA_ProxyServer {
                const CACHE_PUBLIC = 'SERIA_ProxyServer::CACHE_PUBLIC';
                const CACHE_PRIVATE = 'SERIA_ProxyServer::CACHE_PRIVATE';
                const CACHE_NOCACHE = 'SERIA_ProxyServer::CACHE_NOCACHE';
		const CACHE_NOSTORE = 'SERIA_ProxyServer::CACHE_NOSTORE';

		const INIT_PUBLIC_TTL = 604800;
		const INIT_PRIVATE_TTL = 86400;

		protected static $_expires = NULL;
		protected static $_limiter = self::CACHE_PUBLIC;
		// If override mode is set, you must call SERIA_ProxyServer::commit() at the end of your request. This will
		// prevent session_cache_limiter from tampering with your headers.
		protected static $_overrideMode = FALSE;
		protected static $_overrideAvailable = TRUE;

		/**
		*	Resets the caching parameters to their initial state
		*	If a state object is provided, it merges that state with the current state and returns the prior state
		*/
		public static function init($state = NULL) {
			self::_initProperties();
			$currentState = array(
				'limiter' => self::$_limiter,
				'expires' => self::$_expires,
			);
			if($state) {
				self::_setMode($state['limiter'], $state['expires']);
			} else {
				self::$_limiter = self::CACHE_PUBLIC;
				self::$_expires = time() + self::INIT_PUBLIC_TTL;
			}
			self::_resetState();
			return $currentState;
		}


		public static function override() {
			if(self::$_overrideAvailable)
				self::$_overrideMode = TRUE;
			else
				throw new SERIA_Exception('Too late to set SERIA_ProxyServer::override()');
		}

		public static function commit() {
			if(!self::$_overrideMode)
				throw new SERIA_Exception('Override mode not started');
			if(session_id())
				self::privateCache();
			self::_resetState();
		}

		public static function publicCache($ttl=self::INIT_PUBLIC_TTL) {
			if($ttl===NULL) throw new SERIA_Exception('Illegal argument NULL');
			self::_setMode(self::CACHE_PUBLIC, time() + $ttl);
		}

		public static function privateCache($ttl=self::INIT_PRIVATE_TTL) {
			if($ttl===NULL) throw new SERIA_Exception('Illegal argument NULL');
			self::_setMode(self::CACHE_PRIVATE, time() + $ttl);
		}

		public static function noCache() {
			self::_setMode(self::CACHE_NOCACHE);
		}

		public static function noStore() {
			self::_setMode(self::CACHE_NOSTORE);
		}

		public static function applyState(array $state) {
			self::_setMode($state['limiter'], $state['expires']);
		}

		public static function _setMode($limiter, $expires=NULL) {
			self::_initProperties();
			switch(self::$_limiter) {
				case self::CACHE_PUBLIC : // Everything goes
					break;
				case self::CACHE_PRIVATE : // Public does not go
					switch($limiter) {
						case self::CACHE_PUBLIC :
							$limiter = self::$_limiter;
							break;
					}
					break;
				case self::CACHE_NOCACHE : // Public and private does not go
					switch($limiter) {
						case self::CACHE_PUBLIC :
						case self::CACHE_PRIVATE :
							$limiter = self::$_limiter;
							break;
					}
					break;
				case self::CACHE_NOSTORE : // Public and private does not go
					switch($limiter) {
						case self::CACHE_PUBLIC :
						case self::CACHE_PRIVATE :
						case self::CACHE_NOCACHE :
							$limiter = self::$_limiter;
							break;
					}
					break;
			}
			if($expires !== NULL)
				$expires = min(self::$_expires, $expires);
			if($limiter == self::CACHE_NOSTORE || $limiter == self::CACHE_NOCACHE)
				$expires = 0;
			self::$_expires = $expires;
			self::$_limiter = $limiter;
			self::_resetState();
		}

		protected static function _initProperties() {
			if(self::$_expires===NULL) {
				switch(self::$_limiter) {
					case self::CACHE_PUBLIC :
						self::$_expires = time() + self::INIT_PUBLIC_TTL;
						break;
					default :
						self::$_expires = time() + self::INIT_PRIVATE_TTL;
						break;
				}
			}

		}

		protected static function _resetState() {
			self::_initProperties();

			self::$_overrideAvailable = FALSE;
			switch(self::$_limiter) {
				case self::CACHE_PUBLIC :
					if(!self::$_overrideMode) session_cache_limiter('private');
					header('Cache-Control: public, max-age='.(self::$_expires-time()));
					header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', intval(self::$_expires)));
					break;
				case self::CACHE_PRIVATE :
					if(!self::$_overrideMode) {
						session_cache_limiter('private');
						header('Cache-Control: private, expires='.self::$_expires.', max-age='.(self::$_expires - time()).', pre-check='.(self::$_expires - time()));
					} else {
						header('Cache-Control: private, expires='.self::$_expires.', max-age='.(self::$_expires - time()));
					}
					header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
					break;
				case self::CACHE_NOCACHE :
					if(!self::$_overrideMode)
						session_cache_limiter('nocache');
					header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
					header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
					break;
				case self::CACHE_NOSTORE :
					if(!self::$_overrideMode) session_cache_limiter('nocache');
					header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
					header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
					break;
			}
			// This prevents PHP from setting these headers whenever the session is started.
//			session_cache_limiter('do-not-set');
			if(!self::$_overrideMode)
				session_cache_expire(intval((self::$_expires-time()) / 60));
			else
				session_cache_limiter('do-not-set');
			header('Pragma: ');
		}

		public static function getState() {
			self::_initProperties();
			return array(
				'limiter' => self::$_limiter,
				'expires' => self::$_expires,
			);
		}
	}
}
