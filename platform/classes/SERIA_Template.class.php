<?php
/**
 * Templating class
 *
 * TODO:
 * - Defining regions (http://drupal.org/node/29139)
 * - Support block templates (http://drupal.org/node/11813)
 * - Support article type templates (similar to http://drupal.org/node/11815)
 *   use $teaser idea from http://drupal.org/node/11816
 * - Theme information stored inside theme (like http://wiki.e107.org/?title=Creating_a_theme_from_scratch#Step_2.29_Create_Theme.php)
 * - http://docs.joomla.org/Module_positions ?
 * 
 * How to:
 * - Execute a template file:
 *   SERIA_Template::parse('some template file.php', array(
 *     'form' => $form,
 *     'and' => $some,
 *     'other' => $variables,
 *     'that' => 'will',
 *     'be' => _t('available'),
 *     'as' => $local,
 *     'variables' => fopen('in_the_template.code'),
 *   ));
 *   Inside 'some template file.php' you can now for example echo $and;
 */

	class SERIA_Template
	{
		public static $head = array("");
		public static $headEnd = array("");
		public static $body = array();
		public static $disabled = false;
		public static $override = false;

		public static $vars = array();

		public static $language = null;
		
		/**
		 * Parses a PHP-file in the SERIA_Template context. This means that values such as $title, $sitename, $contents
		 * etcetera is available for use within the template.
		 *
		 * @param string $filename
		 */
		public static function parse($templateOrFile, $vars=false)
		{
			if($vars!==false)
				extract($vars);
			extract(self::$vars, EXTR_SKIP);
			if(strpos($templateOrFile, '<')!==false)
			{
				eval('?'.'>'.$templateOrFile);
			}
			else if(file_exists($templateOrFile))
			{
				include($templateOrFile);
			}
			else
			{
				eval('?'.'>'.$templateOrFile);
			}
		}

		static function debugMessage($message)
		{
			if(self::$disabled) return;
			if(!isset(SERIA_Template::$vars['debugMessages']))
				SERIA_Template::$vars['debugMessages'] = array();
			if (function_exists('memory_get_usage'))
				$memoryUsage = memory_get_usage(true);
			else
				$memoryUsage = 0;
			SERIA_Template::$vars['debugMessages'][md5($message)] = array('time' => (microtime(true)-$GLOBALS['seria']['microtime']), 'memory' => $memoryUsage, 'message' => $message);
		}
		
		public static function parseToString($filename, $vars=false)
		{
			ob_start();
			SERIA_Template::parse($filename, $vars);
			$result = ob_get_contents();
			ob_end_clean();
			return $result;
		}
		
		public static function override($contentType, $contents)
		{
			if (SERIA_Template::$override)
				throw new SERIA_Exception('SERIA_Template::override(..) called twice!'); /* Good!!: They will not see! */
			SERIA_Template::$override = array(
				"contentType" => $contentType,
				"contents" => $contents,
			);
			/*
			 * Flushing..
			 */
			ignore_user_abort(true);
			while (ob_end_flush());
			flush();
		}

		public static function disable()
		{
			SERIA_Template::$disabled = true;
		}

		public static function focusTo($elementId)
		{
			self::body("focusTo","<script type='text/javascript'>setTimeout(function(){document.getElementById(\"".htmlspecialchars($elementId)."\").focus();},0);</script>");
		}
		
		/**
		 * Set site name (will be used by templates)
		 * 
		 * @param string $name
		 */
		public static function sitename($sitename)
		{
			self::$vars["sitename"] = $sitename;
		}

		/**
		 * Set page title (will replace contents of <title></title>-tag.
		 *
		 * @param string $title
		 */
		public static function title($title)
		{
			self::$vars["title"] = $title;
		}

		public static function set($name, $value)
		{
			self::$vars[$name] = $value;
		}

		public static function get($name)
		{
			return self::$vars[$name];
		}
		
		/**
		 * Set page contents (available for templates with <?=$contents?>
		 *
		 * @param string $contents
		 */
		public static function contents($contents)
		{
			self::$vars["contents"] = $contents;
		}

		public static function baseURL($baseURL)
		{
			self::head("baseURL", "<base href=\"".htmlspecialchars($baseURL)."\" ".(SERIA_XHTML?"/":"").">");
		}
		
		public static function link($name, $path)
		{
			self::headEnd($path, "<link rel='".htmlspecialchars($name)."' href='".htmlspecialchars($path)."' %XHTML_CLOSE_TAG%>");
		}

		public static function cssInclude($path, $title="")
		{
			self::headEnd($path, "<link rel='stylesheet' type='text/css' href=\"".htmlspecialchars($path)."\" title=\"".htmlspecialchars($title)."\" %XHTML_CLOSE_TAG%>");
		}

		public static function jsInclude($path)
		{
			self::head($path, "<script type='text/javascript' src=\"".htmlspecialchars($path)."\"></script>");
		}

		public static function meta($name, $content)
		{
			self::head("meta-".strtolower($name), "<meta name=\"".htmlspecialchars($name)."\" content=\"".htmlspecialchars($content)."\" %XHTML_CLOSE_TAG%>");
		}

		public static function headPrepend($key, $data)
		{
			if (isset(self::$head[$key]))
				unset(self::$head[$key]);
			self::$head = array_merge(array($key => $data), self::$head);
		}
		public static function head($key, $data)
		{
			unset(self::$headEnd[$key]);
			self::$head[$key] = $data;
		}

		public static function headEnd($key, $data)
		{ 
			unset(self::$head[$key]);
			self::$headEnd[$key] = $data;
		}

		public static function body($key, $data)
		{
			self::$body[$key] = $data;
		}

		public static function setLanguage($lang)
		{
			static $lang_map = array(); /* Edit file seria/lang/lang_map.php */
			static $lang_map_loaded = false;

			if (!$lang_map_loaded) {
				$lang_map_loaded = true;
				if (file_exists(SERIA_ROOT.'/seria_lang/lang_map.php'))
					require(SERIA_ROOT.'/seria_lang/lang_map.php');
				else
					require(SERIA_ROOT.'/seria/lang/lang_map.php');
			}
			self::$language = $lang;
			/*
			 * Map to actual language code.
			 */
			if (isset($lang_map[self::$language]))
				self::$language = $lang_map[self::$language];
		}
		public static function getLanguage()
		{
			static $lang_map = array(); /* Edit file seria/lang/lang_map.php */
			static $lang_map_loaded = false;

			if (!$lang_map_loaded) {
				$lang_map_loaded = true;
				if (file_exists(SERIA_ROOT.'/seria_lang/lang_map.php'))
					require(SERIA_ROOT.'/seria_lang/lang_map.php');
				else
					require(SERIA_ROOT.'/seria/lang/lang_map.php');
			}
			if (self::$language === null) {
				$requri = $_SERVER['REQUEST_URI'];
				while (substr($requri, 0, 2) == '//')
					$requri = substr($requri, 1);
				$seria_admin_prefix = '/seria/'; 
				$seria_admin_prefix_len = strlen($seria_admin_prefix);

				if (substr($requri, 0, $seria_admin_prefix_len) == $seria_admin_prefix) {
					/* TODO: Would now get the language for the logged in user */
					/* TODO: We do not know if Publisher is installed on the root of the server. Use SERIA_HTTP_ROOT in your algorithm here. */
					/* fallback to content-accept-language */
				} else {
// I removed this because I think we should in fact use accept-language if we do not know the language through user account.
// Algorithm should be:
// If inside /seria/, use user account language, fallback to HTTP_ACCEPT_LANGUAGE
// else if outside /seria/, use language defined for this page, if no language defined, use HTTP_ACCEPT_LANGUAGE
//					if (SERIA_DEBUG)
//						SERIA_Base::debug('<strong>Unable to discover language.</strong>');
//					return;
				}
				$alang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
				$alang = explode(',', $alang);
				foreach ($alang as $val) {
					$val = trim($val);
					if ($val) {
						self::$language = $val;
						break;
					}
				}
				if (self::$language === null)
					self::$language = 'en'; /* Last resort fallback */
				/*
				 * Map to actual language code.
				 */
				if (isset($lang_map[self::$language]))
					self::$language = $lang_map[self::$language];
			}
			return self::$language;
		}

		public static function outputHandler($buffer)
		{
			try {
				$buffer = self::outputHandlerFilters($buffer);
				$buffer = SERIA_Hooks::dispatchOrdered(SERIA_TEMPLATE_OUTPUT_HOOK, $buffer);
// Sends incorrect length when using ob_gzhandler				header('Content-Length: '.strlen($buffer));
				return $buffer;
			} catch (Exception $e) {
				if (SERIA_DEBUG) {
					$messages = array();
					if (isset(SERIA_Template::$vars['debugMessages'])) {
						foreach (SERIA_Template::$vars['debugMessages'] as $msg)
							$messages[] = $msg['time'].': '.$msg['message'];
					}
					return $e->getMessage()."<br/>\n".nl2br($e->getTraceAsString())."<br/>\nDebug messages:<br/>\n".implode("<br/>\n", $messages);
				} else {
					return _t('Something went wrong in the output buffer');
				}
			}
		}
		public static function outputHandlerFilters($buffer)
		{
			if(self::$disabled) return $buffer;
			SERIA_Hooks::dispatch('SERIA_Template::outputHandler');

			if(self::$override)
			{
				if (is_array(self::$override)) {
					header("Content-Type: ".self::$override["contentType"]);

					$cont = self::$override["contents"];
					self::$override = true;
					return $cont;
				} else {
					return ''; /* Sorry, we have to discard this (Content-Length already sent) */
				}
			} else {
				header("Content-Type: text/html; charset=UTF-8");
			}

			$find = array();
			$replace = array();

			if (SERIA_SHOW_SERIA_INFORMATION) {
				$find[] = "<head";
				$replace[] = "<!-- 

	THIS WEBSITE IS DEVELOPED USING SERIA PLATFORM (c) ".date("Y")." SERIA AS, NORWAY

	Contact information:

		Website:   www.seria.no
		Telephone: +47 21 69 00 00
		Telefax:   +47 21 69 10 00
		E-mail:    post|seria no

	All source code, images and text residing in folders that contain the word \"seria\" 
	and below is the property of SERIA AS, Norway, unless owned by a third party.

	Redistribution without written permission is not allowed. If you create derivative 
	works based on our source code, please attribute us. Permission will usually be given 
	for open source projects.

--><head";
			}

			// replace the title
			if(self::$vars["title"])
			{ // replace the current title, if it does not exist - add it to the self::$head
				$titlePos = stripos($buffer, "<title");
				if($titlePos===false)
				{
					self::head("title", "<title>".htmlspecialchars(self::$vars["title"])."</title>");
				}
				else
				{
					$titleEndPos = strpos($buffer, ">", $titlePos)+1;
					$titleClosePos = stripos($buffer, "</title", $titleEndPos);
					$buffer = substr($buffer, 0, $titleEndPos).htmlspecialchars(self::$vars["title"]).substr($buffer, $titleClosePos);
				}
			}

			// add stuff to <head> section
			if(sizeof(self::$head)>0)
			{
				$headPos = stripos($buffer, "<head");
				if($headPos===false) return $buffer;
				$headEndPos = strpos($buffer, ">", $headPos)+1;
				$buffer = substr($buffer, 0, $headEndPos)."\n<!-- SERIA PLATFORM HEAD ATTACHMENT -->\n".implode("\n",self::$head)."\n<!-- /SERIA PLATFORM HEAD ATTACHMENT -->\n".substr($buffer, $headEndPos);
			}
			if(sizeof(self::$headEnd)>0)
			{
				$find[] = "</head";
				$replace[] = "\n<!-- SERIA PLATFORM HEAD ATTACHMENT -->\n".implode("\n",self::$headEnd)."\n<!-- /SERIA PLATFORM HEAD ATTACHMENT -->\n</head";
			}

			// add stuff to end of <body> section
			if(sizeof(self::$body)>0)
			{
				$find[] = "</body";
				$replace[] = "\n<!-- SERIA PLATFORM BODY ATTACHMENT -->\n".implode("\n",self::$body)."\n<!-- /SERIA PLATFORM BODY ATTACHMENT -->\n</body";
			}

			// if the code is xhtml, replace %XHTML_CLOSE_TAG% with /, else replace with nothing
			$find[] = "%XHTML_CLOSE_TAG%";
			$replace[] = (SERIA_XHTML != 0 ? "/" : "");

			return str_ireplace($find, $replace, $buffer);
		}
	}
