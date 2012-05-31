<?php
	/**
	 *	Class for handling proxy servers that work in front of Seria Platform.
	 */
	class SERIA_ProxyServer {
		protected static $cacheLim = null;
		protected static $expireTime = null;

		/**
		 *
		 * Called to set the caching to the default of 60 seconds and reset the
		 * cache headers to default (public 60).
		 */
		public static function init()
		{
			$ttl = 60;
			header("Cache-Control: public, max-age=".intval($ttl).", s-maxage=".intval($ttl).", post-check=".intval($ttl).", pre-check=".(intval($ttl)*2));
			header("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time() + intval($ttl)));
			self::$cacheLim = 'public';
			self::$expireTime = null;
		}
		/**
		 *
		 * Called to set the caching to the default of 60 seconds and reset the
		 * cache headers to default (60 seconds). Limit initially to private.
		 */
		public static function private_init()
		{
			$ttl = 60;
			header("Cache-Control: private, max-age=".intval($ttl).", s-maxage=".intval($ttl).", post-check=".intval($ttl).", pre-check=".(intval($ttl)*2));
			header("Pragma: no-cache");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			self::$cacheLim = 'private';
			self::$expireTime = null;
			session_cache_limiter('private');
			session_cache_expire(intval($ttl / 60));
		}

		/**
		 * Sends headers that informs the proxy server to never cache this page.
		 */
		public static function noCache() {
	                header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
	                header("Pragma: no-cache");
	                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

			// if session is started, then PHP will override the above headers with it's own headers using the following configuration
			session_cache_limiter('nocache');
			session_cache_expire(0);

			self::$cacheLim = 'nocache';
		}

		/**
		 * Sends headers that informs the proxy server to never cache the page, while the web browser may cache it.
		 * @param $ttl 		Time to live in seconds for the cache. Since PHP only allow per minute granularity, this should be at least 60 seconds.
		 */
		public static function privateCache($ttl=60)
		{
			if (self::$cacheLim == 'nocache' ||
			    (self::$cacheLim == 'private' && self::$expireTime < $ttl)) {
				/*
				 * This has been overriden by a previous call to
				 * nocache or private, meaning that parts of this response is
				 * not cacheable, or privately cacheable with a lower ttl.
				 * Thus not allowing caching of this response with this ttl.
				 */
				return;
			}
			if (self::$expireTime && ($ttl === null || self::$expireTime < $ttl))
				$ttl = self::$expireTime;
			if ($ttl !== null)
				header("Cache-Control: private, max-age=".intval($ttl).", s-maxage=".intval($ttl).", post-check=".(intval($ttl)/2).", pre-check=".(intval($ttl)));
			else {
				$unspec_ttl = 86400;
				header('Cache-Control: private, max-age='.intval($unspec_ttl).", s-maxage=".$unspec_ttl.", post-check=".($unspec_ttl/2).", pre-check=".($unspec_ttl));
			}
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Pragma: private');

			// if session is started later, then PHP will override the above headers with it's own headers using the following configuration
			session_cache_limiter('private');
			if ($ttl !== null)
				session_cache_expire(intval($ttl / 60));
			else
				session_cache_expire($unspec_ttl / 60);

			self::$cacheLim = 'private';
			self::$expireTime = $ttl;
		}

		/**
		 * Sends headers that informs the proxy server to cache this page if possible. Warning! Although caching is important, you must be aware that
		 * others accessing the exact same url might see the exact same content. You must therefore never call this function if you are displaying private
		 * data.
		 * @param $ttl 		Time to live in seconds for the cache. Since PHP only allow per minute granularity, this should be at least 60 seconds.
		 */
		public static function publicCache($ttl=60)
		{
			if ($ttl !== null) {
				if (self::$expireTime !== null)
					$shorterTtl = ($ttl < self::$expireTime);
				else
					$shorterTtl = true;
			} else
				$shorterTtl = true; /* shorter or equal, no problem to say true */
			if ((self::$cacheLim && self::$cacheLim != 'public') ||
			    (self::$cacheLim == 'public' && !$shorterTtl)) {
				/*
				 * This has been overriden by a previous call to
				 * nocache or private, meaning that parts of this response is
				 * not cacheable, or privately cacheable with a lower ttl.
				 * Thus not allowing caching of this response with this ttl.
				 */
				if (self::$cacheLim == 'private' && $shorterTtl)
					self::privateCache($ttl); /* Reduce the ttl */
				return;
			}
			if ($ttl !== null) {
				header("Cache-Control: public, max-age=".intval($ttl).", s-maxage=".intval($ttl).", post-check=".intval($ttl).", pre-check=".(intval($ttl)*2));
				header("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time() + intval($ttl)));
			} else {
				/*
				 * Unlimited cache translates to max-age=24 hours.
				 */
				$unspec_ttl = 86400;
				header('Cache-Control: public, max-age='.intval($unspec_ttl).", s-maxage=".intval($unspec_ttl).", post-check=".intval($unspec_ttl).", pre-check=".(intval($unspec_ttl)*2));
				header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + intval($unspec_ttl)));
			}
			header("Pragma: public");

			// if session is started later, then PHP will override the above headers with it's own headers using the following configuration
			session_cache_limiter('private');
			if ($ttl !== null)
				session_cache_expire(intval($ttl / 60));
			else
				session_cache_expire($unspec_ttl / 60);

			self::$cacheLim = 'public';
			self::$expireTime = $ttl;
		}
	}
