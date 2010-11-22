<?php
	/**
	 *	Dictionaries are data sources available for lookup. A dictionary can be a list of
	 *	language codes and its accompanying country name or a zip-code and its city.
	 */
	class SERIA_Dictionary implements ArrayAccess
	{
		private static $dictionaries = array();
		static function getDictionary($dictionaryName)
		{
			if(isset(self::$dictionaries[$dictionaryName])) return self::$dictionaries[$dictionaryName];

			$dictionary = SERIA_Hooks::dispatchToFirst('SERIA_DICTIONARY_GET_HOOK', $dictionaryName);
			if(!$dictionary) throw new SERIA_Exception('Unknown dictionary \''.$dictionaryName.'\'.');
			return self::$dictionaries[$dictionaryName] = $dictionary;
		}

		protected $config;

		function __construct($config) { $this->config = $config; }

		function get($term) { throw new Exception('SERIA_Dictionary::get()-method not overridden.'); }

		/**
		 * @see ArrayAccess::offsetGet()
		 */
		function offsetGet($key) { return $this->get($key); }
		/**
		 * @see ArrayAccess::offsetSet()
		 */
		function offsetSet($key, $val) { throw new Exception('Dictionaries are read only.'); }
		/**
		 * @see ArrayAccess::offsetExists()
		 */
		function offsetExists($key) { return $this->get($key) ? true : false; }
		/**
		 * @see ArrayAccess::offsetUnset()
		 */
		function offsetUnset($key) { throw new Exception('Dictionaries are read only.'); }
	}
