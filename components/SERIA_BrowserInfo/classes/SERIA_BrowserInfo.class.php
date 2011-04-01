<?php
	/**
	*	Provides information about the browser that is currently accessing the page
	*/
	class SERIA_BrowserInfo
	{
		public $userAgent;

		public static function current()
		{
			return new SERIA_BrowserInfo($_SERVER['HTTP_USER_AGENT']);
		}

		public function __construct($userAgent)
		{
			$this->userAgent = $userAgent;
		}

		/**
		*	Does the user agent suggest that this is a mobile browser?
		*/
		public function isMobile()
		{
			$ua = strtolower($this->userAgent);
			$isMobile = 
			 strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false
			 || !empty($_SERVER['HTTP_X_OPERAMINI_PHONE'])
			 || strpos($ua, 'opera mobi') !== false
			 || strpos($ua, 'sony') !== false 
			 || strpos($ua, 'symbian') !== false 
			 || strpos($ua, 'nokia') !== false 
			 || strpos($ua, 'samsung') !== false 
			 || strpos($ua, 'mobile') !== false
			 || strpos($ua, 'windows ce') !== false
			 || strpos($ua, 'epoc') !== false
			 || strpos($ua, 'opera mini') !== false
			 || strpos($ua, 'nitro') !== false
			 || strpos($ua, 'j2me') !== false
			 || strpos($ua, 'midp-') !== false
			 || strpos($ua, 'cldc-') !== false
			 || strpos($ua, 'netfront') !== false
			 || strpos($ua, 'mot') !== false
			 || strpos($ua, 'up.browser') !== false
			 || strpos($ua, 'up.link') !== false
			 || strpos($ua, 'audiovox') !== false
			 || strpos($ua, 'blackberry') !== false
			 || strpos($ua, 'ericsson,') !== false
			 || strpos($ua, 'panasonic') !== false
			 || strpos($ua, 'philips') !== false
			 || strpos($ua, 'sanyo') !== false
			 || strpos($ua, 'sharp') !== false
			 || strpos($ua, 'sie-') !== false
			 || strpos($ua, 'portalmmm') !== false
			 || strpos($ua, 'blazer') !== false
			 || strpos($ua, 'avantgo') !== false
			 || strpos($ua, 'danger') !== false
			 || strpos($ua, 'palm') !== false
			 || strpos($ua, 'series60') !== false
			 || strpos($ua, 'palmsource') !== false
			 || strpos($ua, 'pocketpc') !== false
			 || strpos($ua, 'smartphone') !== false
			 || strpos($ua, 'rover') !== false
			 || strpos($ua, 'ipaq') !== false
			 || strpos($ua, 'au-mic,') !== false
			 || strpos($ua, 'alcatel') !== false
			 || strpos($ua, 'ericy') !== false
			 || strpos($ua, 'up.link') !== false
			 || strpos($ua, 'vodafone/') !== false
			 || strpos($ua, 'wap1.') !== false
			 || strpos($ua, 'wap2.') !== false
			 || strpos($ua, 'mobile safari') !== false;

			return $isMobile;
		}

		/**
		*	Does the user agent suggest that this is a mobile browser?
		*/
		public function isSmartphone()
		{
			$ua = strtolower($this->userAgent);
			$isMobile = 
			 !empty($_SERVER['HTTP_X_OPERAMINI_PHONE'])
			 || strpos($ua, 'opera mobi') !== false
			 || strpos($ua, 'symbian') !== false 
			 || strpos($ua, 'windows ce') !== false
			 || strpos($ua, 'opera mini') !== false
			 || strpos($ua, 'blackberry') !== false
			 || strpos($ua, 'series60') !== false
			 || strpos($ua, 'pocketpc') !== false
			 || strpos($ua, 'smartphone') !== false
			 || strpos($ua, 'ipod') !== false
			 || strpos($ua, 'ipad') !== false
			 || strpos($ua, 'iphone') !== false
			 || strpos($ua, 'mobile safari') !== false;


			return $isMobile;
		}

		/**
		*	Should return true only for android mobile devices
		*/
		public function supportsRtsp()
		{
			$ua = strtolower($this->userAgent);
			$supportsRtsp = !empty($_SERVER['HTTP_X_OPERAMINI_PHONE'])
                         || strpos($ua, 'android') !== false
                         || strpos($ua, 'maemo') !== false
                         || strpos($ua, 'series60') !== false;

			return $supportsRtsp;
		}
	}
