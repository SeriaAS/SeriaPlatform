<?php

class Iso639Language
{
	protected $alpha3;
	protected $alpha3Terminology;
	protected $alpha2;
	protected $english;
	protected $french;

	public function __construct($code)
	{
		$info = self::getInfoTable();
		if (strlen($code) == 3) {
			$code = strtolower($code);
			if (isset($info[$code])) {
				$this->initWithInfo($code, $info[$code]);
				return;
			}
			$map = self::getTerminologyNameMap();
			if (isset($map[$code])) {
				$this->initWithInfo($map[$code], $info[$map[$code]]);
				return;
			}
			throw new SERIA_Exception('Alpha-3 language code not found: '.$code);
		} else if (strlen($code) == 2) {
			$code = strtolower($code);
			$map = self::getAlpha2Map();
			if (isset($map[$code]))
				$this->initWithInfo($map[$code], $info[$map[$code]]);
			else
				throw new SERIA_Exception('Alpha-2 language code not found: '.$code);
		} else
			throw new SERIA_Exception('Invalid language code: '.$code);
	}

	protected function initWithInfo($code, $info)
	{
		$this->alpha3 = $code;
		$this->alpha3Terminology = $info['terminologyName'];
		$this->alpha2 = $info['alpha2'];
		$this->english = $info['english'];
		$this->french = $info['french'];
	}

	/**
	 *
	 * Get an array(biliographicName (three-letter name), terminologyName (three-letter synonym), alpha-2 name (two-letter name), English name, French name)
	 * @return array array(three-letter, three-letter synonym, two-letter, english, french)
	 */
	public static function getIso639_2_table()
	{
		static $iso639_2 = null;

		if ($iso639_2 !== null)
			return $iso639_2;

		$defFile = file_get_contents(dirname(dirname(__FILE__)).'/codetables/ISO-639-2_utf-8.txt');
		$defFile = str_replace("\n\r", "\n", $defFile);
		$defFile = str_replace("\r\n", "\n", $defFile);
		$defFile = str_replace("\r", "\n", $defFile);
		$defFile = explode("\n", $defFile);
		$iso639_2 = array();
		foreach ($defFile as $def) {
			$def = trim($def);
			if (!$def)
				continue;
			list($bibliographicName, $terminologyName, $alpha2Code, $englishName, $frenchName) = explode('|', $def);
			$iso639_2[] = array($bibliographicName, $terminologyName, $alpha2Code, $englishName, $frenchName);
		}
		return $iso639_2;
	}

	/**
	 *
	 * Get an associative array of info.
	 * Alpha-three-code => array(
	 *     'terminologyName' => three-letter-synonym-code,
	 *     'alpha2' => two-letter-code,
	 *     'english' => English name,
	 *     'french' => French name
	 * )
	 * @return array See description.
	 */
	public static function getInfoTable()
	{
		static $codes = null;

		if ($codes !== null)
			return $codes;

		$codes = array();
		$table = self::getIso639_2_table();
		foreach ($table as $data)
			$codes[$data[0]] = array(
				'terminologyName' => $data[1],
				'alpha2' => $data[2],
				'english' => $data[3],
				'french' => $data[4]
			);
		return $codes;
	}

	/**
	 *
	 * Get a map of termilogyName => alpha3Code (bibliographic).
	 * @return array map
	 */
	public static function getTerminologyNameMap()
	{
		static $map = null;

		if ($map !== null)
			return $map;

		$info = self::getInfoTable();
		$map = array();
		foreach ($info as $code => $data) {
			if (isset($data['terminologyName']) && $data['terminologyName'])
				$map[$data['terminologyName']] = $code;
		}
		return $map;
	}

	/**
	 *
	 * Get a map of alpa2-code => alpha3-code.
	 * @return array map
	 */
	public static function getAlpha2Map()
	{
		static $map = null;

		if ($map !== null)
			return $map;

		$info = self::getInfoTable();
		$map = array();
		foreach ($info as $code => $data) {
			if (isset($data['alpha2']) && $data['alpha2'])
				$map[$data['alpha2']] = $code;
		}
		return $map;
	}

	/**
	 *
	 * Get the alpha3 code (three-letter code) for this language.
	 * @return string Three-letter language code.
	 */
	public function getAlpha3()
	{
		return $this->alpha3;
	}
	/**
	 *
	 * Get the terminology alpha3 code synonym (three-letter code). Returns null if not available.
	 * @return string Three-letter synonym code or null.
	 */
	public function getTerminologyName()
	{
		if ($this->alpha3Terminology)
			return $this->alpha3Terminology;
		else
			return null;
	}
	/**
	 *
	 * Get the alpha2 code (two-letter code) if available. Returns null if not available.
	 * @return string Two-letter code or null.
	 */
	public function getAlpha2()
	{
		if ($this->alpha2)
			return $this->alpha2;
		else
			return null;
	}
	/**
	 *
	 * Get the English name of this language.
	 */
	public function getEnglishName()
	{
		return $this->english;
	}
	/**
	 *
	 * Get the French name of this language.
	 */
	public function getFrenchName()
	{
		return $this->french;
	}

	/**
	 *
	 * Returns the alpha2 or alpha3 code for this language.
	 * @return string
	 */
	public function __toString()
	{
		if ($this->alpha2)
			return $this->alpha2;
		return $this->alpha3;
	}
}
