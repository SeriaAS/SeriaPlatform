<?php
/**
 * This file is always included when PHP-version is less than 5.3. It is NOT included if php version is exactly 5.3 or higher.
 */

	if(!function_exists("fnmatch"))
	{
		function fnmatch($pattern, $string) {
			if ($pattern == '*') {
				return true;
			}
			$pattern = "#^".strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.'))."\z#i";
			return preg_match($pattern, $string);
		}
	}

	/**
	*	This mehod is a great help for working around the problems with lacking late static binding in PHP versions earlier than 5.3
	*
	*	@author Frode Børli
	*/
	if(!function_exists('get_called_class')) {

		/**
		*	WARNING! This function reads the source code file, something that is very bad for performance. Luckily
		*	PHP 5.3 eliminates the need for this function. An optimization could be made utilizing apc cache or similar.
		*
		*	Returns the class that originally issued the call, perfectly (afaik) immitating the original function from
		*	PHP. The function might have errors in very rare use cases.
		*/
		function get_called_class() {
			static $cache = array();
			$bt = debug_backtrace();
			if(!isset($bt[1]))
				return NULL;

			if(isset($bt[1]['type']))
			{
				if($bt[1]['type']=='::')
				{ // called in the form CLASS::METHOD()
					if(isset($bt[2]['function']) && ($bt[2]['function']=='call_user_func' || $bt[2]['function']=='call_user_func_array') && is_array($bt[2]['args'][0]))
						return $bt[2]['args'][0][0]; // could check that args is an array, but should never happen anyway.
					return _get_called_class_file($bt, 1);
				}
				else if($bt[1]['type']=='->')
				{
// Never tested, might work, uncomment it if having problems with get_called_class when call_user_func(array($object, 'function')) is used
//					if(isset($bt[2]['function']) && ($bt[2]['function']=='call_user_func' || $bt[2]['function']=='call_user_func_array') && is_array($bt[2]['args'][0]) && is_object($bt[2]['args'][0][0]))
//						return get_class($bt[2]['args'][0][0]); // could check that args is an array, but should never happen anyway.
					return get_class($bt[1]['object']);
				}
			}
			return NULL;

		}

		function _get_called_class_file($bt, $offset)
		{
			$line = _get_called_class_getline($bt[$offset]['file'], $bt[$offset]['line']-1);
			preg_match_all('/([a-zA-Z0-9\_]+)::'.$bt[$offset]['function'].'/', $line, $matches);
			if($matches[1][0]=='self')
				return _get_called_class_file($bt, $offset+1);
			return $matches[1][0];
		}

		function _get_called_class_getline($file, $line)
		{
			static $cache = array();
			if(isset($cache[$file])) return $cache[$file][$line];
			$cache[$file] = file($file);
			return $cache[$file][$line];
		}
	}

