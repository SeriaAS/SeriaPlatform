<?php

class Iso639Local
{
	protected $language;
	protected $country = null;

	/**
	 *
	 * Construct a new locale object for language and region (optional).
	 * @param mixed $name1 Language and region code (??-??, ??_??, ???-??, ???_?? (langcode-countrycode)), a language code (??, ???) or a language object (Iso639Language).
	 * @param mixed $name2 Country code if not specified by first parameter, or a country object (Iso3166Country).
	 */
	public function __construct($name1, $name2=null)
	{
		if ($name1 instanceof Iso639Language)
			$this->language = $name1;
		else if (is_string($name1)) {
			switch (strlen($name1)) {
				case 2:
				case 3:
					/* Language code */
					$this->language = new Iso639Language($name1);
					if ($name2 !== null) {
						if ($name2 instanceof Iso3166Country)
							$this->country = $name2;
						else
							$this->country = new Iso3166Country($name2);
					}
					break;
				case 5:
				case 6:
					/* Combined language and region */
					$pos = strpos($name1, '-');
					if ($pos === false)
						$pos = strpos($name1, '_');
					if ($pos === false)
						throw new SERIA_Exception('Invalid locale code: '.$name1);
					if ($pos != 2 && $pos != 3)
						throw new SERIA_Exception('Invalid locale code: '.$name1);
					if ($pos == 3 && strlen($name1) != 6)
						throw new SERIA_Exception('Invalid locale code: '.$name1);
					$language = substr($name1, 0, $pos);
					$country = substr($name1, $pos + 1);
					$this->language = new Iso639Language($language);
					$this->country = new Iso3166Country($country);
					break;
				default:
					/* Invalid */
					throw new SERIA_Exception('Invalid locale code: '.$name1);
			}
		}
	}

	public function __toString()
	{
		$code = $this->language->getAlpha2();
		if ($code === null)
			$code = $this->language->getAlpha3();
		if ($this->country !== null)
			$code .= '-'.$this->country->getAlpha2Code();
		return $code;
	}

	public function getEnglishName()
	{
		$name = $this->language->getEnglishName();
		if ($this->country !== null)
			$name .= ' ('.$this->country->getEnglishName().')';
		return $name;
	}

	/**
	 *
	 * Get the language.
	 * @return Iso639Language
	 */
	public function getLanguage()
	{
		return $this->language;
	}
	/**
	 *
	 * Get the country/region. Null if not set.
	 * @return Iso3166Country
	 */
	public function getCountry()
	{
		return $this->country;
	}
}
