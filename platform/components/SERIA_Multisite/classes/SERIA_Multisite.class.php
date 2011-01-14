<?php
	class SERIA_Multisite
	{
		public static function isMaster() {
			if($_SERVER['HTTP_HOST']===NULL)
				return false;
			$host = $_SERVER['HTTP_HOST'];
			if(substr($host,0,4)==='www.')
				$host = substr($host,4);
			if($host===SERIA_MULTISITE_DOMAIN)
				return true;
			return false;
		}
	}
