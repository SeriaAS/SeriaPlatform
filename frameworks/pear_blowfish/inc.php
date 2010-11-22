<?php

include_once "PEAR.php";

if (!class_exists('PEAR')) {
	/*
	 * Too bad!
	 * Hack to get PEAR code run fine if it does not require any dependencies.
	 */
	class PEAR {
		/*
		 * Should really return an error object..
		 */
		public static function raiseError($err)
		{
			throw new SERIA_Exception($err);
		}
		public static function isError($obj)
		{
			return false;
		}
	}
}

require_once(dirname(__FILE__).'/Crypt_Blowfish-1.1.0RC2/Blowfish.php');
