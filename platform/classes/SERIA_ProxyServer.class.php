<?php
	/**
	 *	Class for handling proxy servers that work in front of Seria Platform.
	 */
	class SERIA_ProxyServer {
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
		}

		/**
		 * Sends headers that informs the proxy server to never cache the page, while the web browser may cache it.
		 * @param $ttl 		Time to live in seconds for the cache. Since PHP only allow per minute granularity, this should be at least 60 seconds.
		 */
		public static function privateCache($ttl=60)
		{
			header("Cache-Control: private, max-age=".intval($ttl).", s-maxage=".intval($ttl).", post-check=".(intval($ttl)/2).", pre-check=".(intval($ttl)));
			header('Pragma: private');

			// if session is started later, then PHP will override the above headers with it's own headers using the following configuration
			session_cache_limiter('private');
			session_cache_expire(intval($ttl / 60));
		}

		/**
		 * Sends headers that informs the proxy server to cache this page if possible. Warning! Although caching is important, you must be aware that
		 * others accessing the exact same url might see the exact same content. You must therefore never call this function if you are displaying private
		 * data.
		 * @param $ttl 		Time to live in seconds for the cache. Since PHP only allow per minute granularity, this should be at least 60 seconds.
		 */
		public static function publicCache($ttl=60)
		{
			header("Cache-Control: public, max-age=".intval($ttl).", s-maxage=".intval($ttl).", post-check=".intval($ttl).", pre-check=".(intval($ttl)*2));
			header("Expires: " . gmdate('D, d M Y H:i:s \G\M\T', time() + intval($ttl)));
			header("Pragma: public");

			// if session is started later, then PHP will override the above headers with it's own headers using the following configuration
			session_cache_limiter('private');
			session_cache_expire(intval($ttl / 60));
		}
	}
