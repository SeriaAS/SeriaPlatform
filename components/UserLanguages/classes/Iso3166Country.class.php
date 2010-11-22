<?php

class Iso3166Country
{
	protected $alpha2Code;
	protected $enName;

	public function __construct($code)
	{
		$code = strtoupper($code);
		$codes = self::getAlpha2Table();
		if (!isset($codes[$code]))
			throw new SERIA_Exception('Country code is unknown: '.$code);
		$this->alpha2Code = $code;
		$this->enName = $codes[$code];
	}

	/**
	 *
	 * Get an array(two letter country code => english country name).
	 * @return array array(alpha2-code => country)
	 */
	public static function getAlpha2Table()
	{
		static $codes = null;

		if ($codes !== null)
			return $codes;

		$defFile = file_get_contents(dirname(dirname(__FILE__)).'/codetables/list-en1-semic-3.txt');
		$defFile = str_replace("\n\r", "\n", $defFile);
		$defFile = str_replace("\r\n", "\n", $defFile);
		$defFile = str_replace("\r", "\n", $defFile);
		$defFile = explode("\n", $defFile);
		$intro = array_shift($defFile);
		$blank = array_shift($defFile);
		if ($blank || !$intro)
			throw new SERIA_Exception('Bad country table file.');
		$codes = array();
		foreach ($defFile as $def) {
			$def = trim($def);
			if (!$def)
				continue;
			list($country, $code) = explode(';', $def);
			$codes[$code] = $country;
		}
		return $codes;
	}
	/**
	 *
	 * Get a list of two letter country codes (alpha 2) 
	 * @return array Array of two letter country codes.
	 */
	public static function getAlpha2Codes()
	{
		return array_keys(self::getAlpha2Table());
	}

	/**
	 *
	 * Return the two-letter code for this country.
	 */
	public function getAlpha2Code()
	{
		return $this->alpha2Code;
	}
	/**
	 *
	 * Return the english name of this country.
	 */
	public function getEnglishName()
	{
		return $this->enName;
	}

	/**
	 *
	 * Returns the alpha2 code for this country.
	 * @return string
	 */
	public function __toString()
	{
		return $this->alpha2Code;
	}
}
