<?php

/**
 * 
 * SERIA_CacheControl can parse or generate the Cache-Control header.
 * @author Jan-Espen Pettersen
 *
 */
class SERIA_CacheControl
{
	protected $cacheControl;

	/**
	 *
	 * Create a cache-control-header object for analysis.
	 * @param string $cacheControlHeader The Cache-Control header value.
	 */
	public function __construct($cacheControlHeader=null)
	{
		if ($cacheControlHeader)
			$this->cacheControl = self::parseHeader($cacheControlHeader);
		else
			$this->cacheControl = array();
	}
	protected static function shiftHeaderPart(&$cacheControl)
	{
		$cacheControl = ltrim($cacheControl);
		if ($cacheControl === '')
			return false;
		if ($cacheControl[0] != '"') {
			if ($cacheControl[0] == ',' || $cacheControl[0] == ';' || $cacheControl[0] == '=') {
				$token = $cacheControl[0];
				$cacheControl = ltrim(substr($cacheControl, 1));
				return $token;
			}
			$pos = array(',', ';', '=');
			$pos = array_flip($pos);
			foreach ($pos as $delim => &$p)
				$p = strpos($cacheControl, $delim);
			unset($p);
			$first = null;
			foreach ($pos as $p) {
				if ($p !== false && ($first > $p || $first === null))
					$first = $p;
			}
			if ($first === null) {
				$cc = $cacheControl;
				$cacheControl = '';
				return $cc;
			}
			$pos = $first;
			$cc = substr($cacheControl, 0, $pos);
			$cacheControl = ltrim(substr($cacheControl, $pos));
			return $cc;
		} else {
			/* quoted string */
			$cacheControl = substr($cacheControl, 1);
			$qStr = '';
			while ($cacheControl) {
				$c = $cacheControl[0];
				$cacheControl = substr($cacheControl, 1);
				if ($c == '"')
					break;
				else if ($c != "\\")
					$qStr .= $c;
				else if ($cacheControl) {
					/* Escaped by \ */
					$qStr = $cacheControl[0];
					$cacheControl = substr($cacheControl, 1);
				} else
					$qStr .= "\\"; /* Escape-seq followed by EOL */
			}
			return $qStr;
		}
	}
	protected static function parseHeader($cacheControl)
	{
		$tokens = array();
		while ($cacheControl !== '') {
			$token = self::shiftHeaderPart($cacheControl);
			if ($token === false)
				break;
			if ($token != '=' && $token != ',' && $token != ';') {
				$tokens[strtolower($token)] = false;
			} else if ($token != ',' && $token != ';') {
				$keys = array_keys($tokens);
				$lastToken = array_pop($keys);
				if ($tokens[$lastToken] === false)
					$tokens[$lastToken] = self::shiftHeaderPart($cacheControl);
				/* else we are out on the field! */
			}
		}
		return $tokens;
	}
	protected static function quotePrintable($value)
	{
		$value = str_replace('\\', '\\\\', $value);
		$value = str_replace('"', '\\"', $value);
		return '"'.$value.'"';
	}
	/**
	 *
	 * Get all Cache-Control tokens in an array.
	 * return array
	 */
	public function getTokens()
	{
		return $this->cacheControl;
	}
	/**
	 *
	 * Set a Cache-Control token.
	 * @param string $name
	 * @param string $value Optional value.
	 */
	public function setToken($name, $value=false)
	{
		$name = strtolower($name);
		$this->cacheControl[$name] = $value;
	}
	/**
	 *
	 * Get a Cache-Control token. Returns value or true (is set but without value) if set. Null if the token isn't set.
	 * @param mixed $name Value or true if set. Otherwise null.
	 */
	public function getToken($name)
	{
		if (isset($this->cacheControl[$name])) {
			if ($this->cacheControl[$name] === false)
				return true;
			else
				return $this->cacheControl[$name];
		} else
			return null;
	}
	/**
	 *
	 * Generate a Cache-Control header-value.
	 * @return string Cache-Control header value.
	 */
	public function __toString()
	{
		$tokens = array();
		foreach ($this->cacheControl as $name => $value) {
			if ($value === false)
				$tokens[] = $name;
			else {
				ob_start();
				if (intval($value) != $value)
					$value = self::quotePrintable($value);
				echo $name, '=', $value;
				$tokens[] = ob_get_clean();
			}
		}
		return implode(', ', $tokens);
	}

	/**
	 *
	 * Returns true if disallowed to cache.
	 * @return boolean True if disallowed to cache.
	 */
	public function noCache()
	{
		return isset($this->cacheControl['no-cache']);
	}
	/**
	 *
	 * Returns true if disallowed to store.
	 * @return boolean True if disallowed to store.
	 */
	public function noStore()
	{
		return isset($this->cacheControl['no-store']);
	}
	/**
	 *
	 * Returns true if public caching is allowed.
	 */
	public function isPublic()
	{
		if ($this->noStore() || $this->noCache() || $this->isPrivate())
			return false;
		return (isset($this->cacheControl['public']) || isset($this->cacheControl['s-maxage']) || isset($this->cacheControl['max-age']));
	}
	/**
	 *
	 * Returns true if caching must be kept private. (Browser-cache only)
	 */
	public function isPrivate()
	{
		if ($this->noStore() || $this->noCache())
			return false;
		return isset($this->cacheControl['private']);
	}
	/**
	 *
	 * Get the max lifetime of the public cache (0 means no caching).
	 */
	public function getPublicMaxAge()
	{
		if ($this->noCache() || $this->noStore())
			return 0;
		if (isset($this->cacheControl['public'])) {
			if (isset($this->cacheControl['s-maxage'])) {
				return intval($this->cacheControl['s-maxage'], 10);
			} else if (isset($this->cacheControl['max-age'])) {
				return intval($this->cacheControl['max-age'], 10);
			} else
				return null;
		} else if (!$this->isPrivate()) {
			if (isset($this->cacheControl['s-maxage'])) {
				return intval($this->cacheControl['s-maxage'], 10);
			} else if (isset($this->cacheControl['max-age'])) {
				return intval($this->cacheControl['max-age'], 10);
			} else
				return 0;
		}
		return 0;
	}
	/**
	 *
	 * Get the max lifetime of the private cache (0 means no caching).
	 */
	public function getPrivateMaxAge()
	{
		if ($this->noCache() || $this->noStore())
			return 0;
		if (isset($this->cacheControl['max-age']))
			return intval($this->cacheControl['max-age'], 10);
		else
			return 0;
	}
	
}
