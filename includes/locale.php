<?php
	function seria_localeSettings() {
		/**
		 *	Set time zone
		 */
		if(isset($_SESSION[SERIA_PREFIX."_TIMEZONE"]))
			date_default_timezone_set($_SESSION[SERIA_PREFIX."_TIMEZONE"]);
		else
			date_default_timezone_set(SERIA_TIMEZONE);
		
		/**
		 *	Set the locale
		 */
		$locale = setlocale(LC_ALL ^ LC_NUMERIC, SERIA_LOCALE, 'no');
	}
	
	seria_localeSettings();

	/**
	 * Internal function called by _t when a string is not yet in the translation files.
	 *
	 * @param $filename
	 * @param $hash
	 * @param $string
	 * @return unknown_type
	 */
	function _t_addStringToDefaultLang($filename, $hash, $string)
	{
		static $notWorking = false;
		if($notWorking) {
			if(SERIA_DEBUG)
				SERIA_Base::debug('<strong>_t: Disabled file writing because of failure.  (you should open seria_lang/default and seria/lang/default for writing)</strong>');
			return;
		}

		/* Create the directory if it does not exist */
		$directory = dirname($filename);
		if (!file_exists($directory))
		{
			if(!@mkdir($directory, 0775, true))
			{
				if(SERIA_DEBUG)
					SERIA_Base::debug('<strong>_t: Failed to create directory: '.$directory.' (you should open seria_lang/default and seria/lang/default for writing)</strong>');
				$notWorking = true;
				return;
			}
		}

		/* Read and then write new content */
		$Giant_list_lang = array();
		try {
			$fh = @fopen($filename.'.tmp', 'w');

		} catch (Exception $e) {
			if(SERIA_DEBUG)
				SERIA_Base::debug('<strong>_t: Failed to open file for writing: '.$e->getMessage().' (you should open seria_lang/default and seria/lang/default for writing)</strong>');
			return;
		}
		if ($fh) {
			flock($fh, LOCK_EX);
			if (file_exists($filename))
				require($filename);
			$Giant_list_lang[$hash] = $string;
			$newCode = '<?php $Giant_list_lang = array_merge($Giant_list_lang, '.var_export($Giant_list_lang, true).'); ?>';
			/*
			 * Please don't fail after this..
			 */
			while ($newCode) {
				$written = fwrite($fh, $newCode);
				if ($written === false) {
					$notWorking = true;
					fclose($fh);
					unlink($filename.'.tmp'); /* This file is truncated, and some or all page views will fail if we don't delete it! */
					if(SERIA_DEBUG) SERIA_Base::debug('<strong>Failed to write a language file, and we have now lost the content! ('.$filename.')</strong>');
				}
				$newCode = substr($newCode, $written);
			}
			flock($fh, LOCK_UN);
			fclose($fh);
			if(file_exists($filename))
				unlink($filename);
			rename($filename.'.tmp', $filename);
		} else {
			$notWorking = true;
			if(SERIA_DEBUG) SERIA_Base::debug('<strong>_t: Failed to open file for writing: '.$filename.' (you should open seria_lang/default and seria/lang/default for writing)</strong>');
		}
	}
	/**
	 * A function that protects the namespace of the _t function from modification of Giant_list-strings.
	 * This is because previously the giant-list was flat and we loaded files directly by require.
	 *
	 * @param $filename
	 * @return unknown_type
	 */
	function _t_loadLangFile($filename)
	{
		$Giant_list_lang = array();
		require($filename);
		return $Giant_list_lang;
	}

	/**
	 * 
	 * See _t except filename of caller is specified as arg and not retrieved from call stack.
	 * @param string $string
	 * @param array $vars
	 * @param string $key
	 * @param string $filename Filename of caller.
	 * @throws Exception
	 */
	function _t_with_caller($string, $vars, $key, $filename)
	{
		static $Giant_list_lang = array();
		static $file_loaded = array();
		static $realpaths = array();

		if (!$filename)
			throw new Exception('Filename not specified.');
		$cwd = getcwd();
		if (!isset($realpaths[$cwd.':'.$filename]))
			$realpaths[$cwd.':'.$filename] = realpath($filename);
		$filename = $realpaths[$cwd.':'.$filename];

		/* PHASE I: Encoding */
		$string = mb_convert_encoding($string, "UTF-8", "UTF-8, ISO-8859-1");

		/* PHASE II: Translate */
		$hash = hash('md4', $string.($key === null ? '' : $key)); /* MD4 has been claimed to be the fastest hash function */
		if (!isset($Giant_list_lang[$filename]) || !isset($Giant_list_lang[$filename][$hash])) {
			/* PHASE IIb: Check if language def is loaded */
			$file_hash = hash('md4', $filename);
			if (!isset($file_loaded[$file_hash]))
				$file_loaded[$file_hash] = 0;
			$rootpath = realpath(SERIA_ROOT);
			$rootpath_len = strlen($rootpath);
			if (substr($filename, 0, $rootpath_len) != $rootpath) {
				/*
				 * Autodetect a translation directory.
				 * This means that if you have code that is outside the SERIA_ROOT
				 * you can still use _t in it provided that you create a seria_lang directory
				 * there.
				 */
				$rootpath = dirname($filename);
				while (!is_dir($rootpath.'/seria_lang')) {
					if ($rootpath == dirname($rootpath))
						throw new Exception('The included file ('.$filename.') is outside SERIA_ROOT. Translation is not implemented.');						
					$rootpath = dirname($rootpath);
				}
				$rootpath_len = strlen($rootpath);
				$lang_root = $rootpath.'/seria_lang';
			} else
				$lang_root = null;
			$relpath = substr($filename, $rootpath_len);
			if ($relpath[0] == DIRECTORY_SEPARATOR)
				$relpath = substr($relpath, 1);
			$seria_base_path = 'seria'.DIRECTORY_SEPARATOR;
			$seria_base_path_len = strlen($seria_base_path);
			if ($lang_root === null) {
				if (substr($relpath, 0, $seria_base_path_len) == $seria_base_path) {
					/* seria/ code: */
					$lang_root = SERIA_ROOT.'/seria/lang';
				} else {
					/* user/local code: */
					$lang_root = SERIA_LANG_PATH;
				}
			}
			if ($file_loaded[$file_hash] < 2) {
				/* PHASE IIc: Load locale language file */
				if ($file_loaded[$file_hash] == 0) {
					$file_loaded[$file_hash]++; /* 1 = locale loaded */
					$locale_filename = $lang_root.'/'.SERIA_Template::getLanguage().'/'.$relpath;
					if (file_exists($locale_filename))
						$Giant_list_lang[$filename] = _t_loadLangFile($locale_filename);
					else
						$Giant_list_lang[$filename] = array();
				}

				/* DEBUG ASSERTION: THE LOAD COUNTER MUST BE STRICTLY (0 = none loaded, 1 = locale loaded, 2 = default loaded) */
				if (SERIA_DEBUG && $file_loaded[$file_hash] !== 1 && $file_loaded[$file_hash] !== 2)
					throw new Exception('Unexpected value!');

				if (!isset($Giant_list_lang[$filename][$hash])) {
					if ($file_loaded[$file_hash] == 1) {
						/* PHASE IId: Load default language file */
						$file_loaded[$file_hash]++; /* 2 = default loaded */
						$default_filename = $lang_root.'/default/'.$relpath;
						if (file_exists($default_filename)) {
							$defaultFileContents = _t_loadLangFile($default_filename);
							foreach ($defaultFileContents as $whash => $value) {
								if (!isset($Giant_list_lang[$filename][$whash]))
									$Giant_list_lang[$filename][$whash] = $value;
							}
						}
					}
				}
			}
			/*
			 * The files are guaranteed to have been loaded here..
			 */
			if (!isset($Giant_list_lang[$filename][$hash])) {
				/* PHASE IIe: Add string to default language file */
				$Giant_list_lang[$filename][$hash] = $string;
				_t_addStringToDefaultLang($lang_root.'/default/'.$relpath, $hash, $string);
			}
		}
		if (SERIA_DEBUG && !isset($Giant_list_lang[$filename][$hash]))
			throw new Exception('_t() algo failed to add string to array');

		$string = $Giant_list_lang[$filename][$hash];

		/* PHASE III: Replacements */
		if(sizeof($vars))
		{
			$find = array();
			$replace = array();
			foreach($vars as $f => $r)
			{
				$find[] = "%$f%";
				$replace[] = $r;
			}
			$string = str_ireplace($find, $replace, $string);
		}
		return $string;
	}

	/**
	 * Makes a string translatable trough special translation files
	 *
	 * @param string $string Translatable string 
	 * @param array $vars Associative array of WORD => match, where %WORD% will be replaced in the translatable string.
	 * @param string $key An optional key that causes the translation hash to differ from other instances of this string (with no key or different key)
	 * @return string
	 */
	function _t($string, $vars=array(), $key=null)
	{
		$call_stack = debug_backtrace(false);
		while ($call_stack) {
			$call = array_shift($call_stack);
			$filename = $call['file'];
			if (file_exists($filename))
				break;
		}
		$call_stack = null;
		if (($override = SERIA_TranslationContext::getContext($filename)) !== false)
			$filename = $override;
		return _t_with_caller($string, $vars, $key, $filename);
	}

	class SERIA_TranslationContext
	{
		protected $contextIndex;

		public function __construct($path, $currentFilename)
		{
			static $counter = 0;

			if (!isset($GLOBALS['_tContext_stack'])) {
				$GLOBALS['_tContext_stack'] = array();
				$GLOBALS['_tContext_stack_scope'] = array();
			}
			$GLOBALS['_tContext_stack'][$counter] = $path;
			$GLOBALS['_tContext_stack_scope'][$counter] = $currentFilename;
			$this->contextIndex = $counter;
			$counter++;
		}
		public function __destruct()
		{
			if (isset($GLOBALS['_tContext_stack'][$this->contextIndex])) {
				unset($GLOBALS['_tContext_stack'][$this->contextIndex]);
				if (isset($GLOBALS['_tContext_stack_scope'][$this->contextIndex]))
					unset($GLOBALS['_tContext_stack_scope'][$this->contextIndex]);
			}
		}
		public static function getContext($currentFilename)
		{
			if (isset($GLOBALS['_tContext_stack']) && $GLOBALS['_tContext_stack']) {
				$keys = array_keys($GLOBALS['_tContext_stack']);
				$index = array_pop($keys);
				if (isset($GLOBALS['_tContext_stack_scope'][$index]) && $GLOBALS['_tContext_stack_scope'][$index] != $currentFilename)
					return false;
				return $GLOBALS['_tContext_stack'][$index];
			}
			return false;
		}
	}

	/**
	 *
	 * Start an overridden filepath context for _t-translations. This context is destroyed
	 * when the returned object is unreferenced/destroyed. Keep the reference as long as
	 * you want the filepath to be overridden.
	 *
	 * @param $filepath string
	 * @return Persistence__tContext
	 */
	function _t_setContext($filepath)
	{
		$call_stack = debug_backtrace(false);
		$filename = $call_stack[0]['file'];
		$call_stack = null;
		return new SERIA_TranslationContext($filepath, $filename);
	}

	/**
	 * Formats a timestamp as a date (without time) according to current locale
	 *
	 * @param int $timestamp
	 * @return string
	 */
	function _date($timestamp)
	{
		return date("Y-m-d", $timestamp);
	}
	
	/**
	 * Formats a timestamp as a date time according to current locale
	 *
	 * @param unknown_type $timestamp
	 * @return unknown
	 */
	function _datetime($timestamp)
	{
		return date("Y-m-d H:i:s", $timestamp);
	}
	
	function _time($timestamp) {
		return date('H:i:s', $timestamp);
	}

?>
