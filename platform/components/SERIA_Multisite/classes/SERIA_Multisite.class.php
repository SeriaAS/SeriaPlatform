<?php
	class SERIA_Multisite
	{
		public static function isMaster() {
			if(!defined('SERIA_MULTISITE_DOMAIN')) return false;
			if($_SERVER['HTTP_HOST']===NULL)
				return false;
			$host = $_SERVER['HTTP_HOST'];
			if(substr($host,0,4)==='www.')
				$host = substr($host,4);
			if($host===SERIA_MULTISITE_DOMAIN)
				return true;
			return false;
		}

		public static function getAllSites() {
			if(!defined('SERIA_MULTISITE_DOMAIN')) return false;
			$sites = SERIA_Base::db()->query("SELECT * FROM {sites}")->fetchAll(PDO::FETCH_ASSOC);
			return $sites;
		}

		public static function getAllSiteAliases() {
			if(!defined('SERIA_MULTISITE_DOMAIN')) return false;
			$aliases = SERIA_Base::db()->query("SELECT * FROM {sites_aliases}")->fetchAll(PDO::FETCH_ASSOC);
			return $aliases;
		}
	}
