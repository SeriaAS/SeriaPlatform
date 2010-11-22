<?php

class SERIA_CryptoBlowfish
{
	protected static $isInited = false;

	protected static $iv_from_data_func = null;
	protected static $encrypt_func = null;
	protected static $decrypt_func = null;

	protected static function init()
	{
		if (self::$isInited)
			return;
		self::$isInited = true;
		if (function_exists('mcrypt_encrypt')) {
			/*
			 * mcrypt
			 */
			self::$iv_from_data_func = array('SERIA_CryptoBlowfish', 'mcrypt_create_iv_from_data');
			self::$encrypt_func = array('SERIA_CryptoBlowfish', 'mcrypt_encrypt');
			self::$decrypt_func = array('SERIA_CryptoBlowfish', 'mcrypt_decrypt');
		} else {
			if (!class_exists('Crypt_Blowfish'))
				SERIA_Base::addFramework('pear_blowfish');
			if (!class_exists('Crypt_Blowfish'))
				throw new Exception('Crypt_Blowfish failed to load..');
			self::$iv_from_data_func = array('SERIA_CryptoBlowfish', 'pear_blowfish_create_iv_from_data');
			self::$encrypt_func = array('SERIA_CryptoBlowfish', 'pear_blowfish_encrypt');
			self::$decrypt_func = array('SERIA_CryptoBlowfish', 'pear_blowfish_decrypt');
		}
	}

	protected static function pear_blowfish_create_iv_from_data($mode, $data)
	{
		switch ($mode) {
			case 'ecb':
				return null;
			case 'cbc':
				$bytes = 8;
				break;
			default:
				throw new Exception('Unknown mode for IV gen: '.$mode);
		}
		$IV = str_repeat(' ', $bytes);
		for ($i = 0; $i < $bytes; $i++)
			$IV[$i] = $data[$i];
		return $IV;
	}
	protected static function pear_blowfish_encrypt($key, $data, $mode, $iv)
	{
		$crypt = Crypt_Blowfish::factory($mode, $key, $iv, CRYPT_BLOWFISH_PHP);
		return $crypt->encrypt($data);
	}
	protected static function pear_blowfish_decrypt($key, $data, $mode, $iv)
	{
		$crypt = Crypt_Blowfish::factory($mode, $key, $iv, CRYPT_BLOWFISH_PHP);
		return $crypt->decrypt($data);
	}

	protected static function mcrypt_get_mode($mode)
	{
		switch ($mode) {
			case 'ecb':
				return MCRYPT_MODE_ECB;
			case 'cbc':
				return MCRYPT_MODE_CBC;
		}
		throw new Exception('SERIA Blowfish compat layer does not yet support mode='.$mode);
	}
	protected static function mcrypt_create_iv_from_data($mode, $data)
	{
		$bytes = mcrypt_get_iv_size(MCRYPT_BLOWFISH, self::mcrypt_get_mode($mode));;
		$IV = str_repeat(' ', $bytes);
		for ($i = 0; $i < $bytes; $i++)
			$IV[$i] = $data[$i];
		return $IV;
	}
	protected static function mcrypt_encrypt($key, $data, $mode, $iv)
	{
		if ($iv === null) {
			$ivs = mcrypt_get_iv_size(MCRYPT_BLOWFISH, self::mcrypt_get_mode($mode));
			$iv = '';
			for ($i = 0; $i < $ivs; $i++)
				$iv .= "\0";
		}
		return mcrypt_encrypt(MCRYPT_BLOWFISH, $key, $data, self::mcrypt_get_mode($mode), $iv);
	}
	protected static function mcrypt_decrypt($key, $data, $mode, $iv)
	{
		if ($iv === null) {
			$ivs = mcrypt_get_iv_size(MCRYPT_BLOWFISH, self::mcrypt_get_mode($mode));
			$iv = '';
			for ($i = 0; $i < $ivs; $i++)
				$iv .= "\0";
		}
		return mcrypt_decrypt(MCRYPT_BLOWFISH, $key, $data, self::mcrypt_get_mode($mode), $iv);
	}

	public static function createIVFromData($mode, $data)
	{
		self::init();
		return call_user_func(self::$iv_from_data_func, $mode, $data);
	}
	public static function encrypt($key, $data, $mode, $iv=null)
	{
		self::init();
		return call_user_func(self::$encrypt_func, $key, $data, $mode, $iv);
	}
	public static function decrypt($key, $data, $mode, $iv=null)
	{
		self::init();
		return call_user_func(self::$decrypt_func, $key, $data, $mode, $iv);
	}
}