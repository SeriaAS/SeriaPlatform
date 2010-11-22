<?php
	define('HTMLPURIFIER_PREFIX', dirname(__FILE__) . '/../libs/htmlpurifier');
	require(HTMLPURIFIER_PREFIX.'/HTMLPurifier.standalone.php');

	class SERIA_Html
	{
		/**
		*	Sanitize html that comes from public unauthenticated users
		*/
		const PUBLIC_SANITIZE = 1;
		/**
		*	Sanitize html that comes from authenticated users
		*/
		const GUEST_SANITIZE = 2;
		/**
		*	Sanitize html that comes from normal users (users that are allowed access to the admin pages)
		*/
		const NORMAL_SANITIZE = 3;
		/**
		*	Sanitize html that comes from administrators (really no sanitation).
		*/
		const ADMIN_SANITIZE = 4;

		static $purifiers = array();
		protected static function getPurifier($strictness)
		{
			if(isset(self::$purifiers[$strictness])) return self::$purifiers[$strictness];
			$config = HTMLPurifier_Config::createDefault();
			$config->set('Core', 'Encoding', 'UTF-8');
			$config->set('HTML', 'Doctype', SERIA_DOCTYPE);
			$config->set('Cache', 'SerializerPath', SERIA_CACHE_ROOT.'/SERIA_Html/HTMLPurifier');

			switch($strictness)
			{
				case self::PUBLIC_SANITIZE : // most restrictive
					$config->set('Attr', 'EnableAttrID', true);
					
					return self::$purifiers[$strictness] = new HTMLPurifier($config);

				case self::GUEST_SANITIZE : // little less restrictive
					$config->set('Output', 'FlashCompat', true);
					$config->set('HTML', 'SafeEmbed', true);
					$config->set('HTML', 'SafeObject', true);
					return self::$purifiers[$strictness] = new HTMLPurifier($config);

				case self::NORMAL_SANITIZE :
					$config->set('CSS','AllowTricky',true);
					$config->set('Output', 'FlashCompat', true);
					$config->set('HTML', 'SafeEmbed', true);
					$config->set('HTML', 'SafeObject', true);
					$config->set('HTML', 'Trusted', true);

					return self::$purifiers[$strictness] = new HTMLPurifier($config);

				case self::ADMIN_SANITIZE :
					$config->set('CSS','AllowTricky',true);
					$config->set('Output', 'FlashCompat', true);
					$config->set('HTML', 'SafeEmbed', true);
					$config->set('HTML', 'SafeObject', true);
					$config->set('HTML', 'Trusted', true);

					return self::$purifiers[$strictness] = new HTMLPurifier($config);

					break;
				default : throw new SERIA_Exception('Unknown sanitazion level "'.$strictness.'".');
			}
		}

		public static function sanitize($html, $strictness=1)
		{
			return self::getPurifier($strictness)->purify($html);
		}

		public static function install() // SERIA_Base::INSTALL_HOOK
		{
			$row = array(
		                'action' => 'Create SERIA_Html cache',
		                'description' => 'Creating a cache folder for HTMLPurifier',
		        );

			if(!file_exists(SERIA_CACHE_ROOT.'/SERIA_Html/HTMLPurifier') &&
			   !mkdir(SERIA_CACHE_ROOT.'/SERIA_Html/HTMLPurifier', 0777, true))
				$row['error'] = 'Unable to create cache folder';

			return $row;
		}
	}
