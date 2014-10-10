<?php
	/**
	*	Simple template class allowing you to attach variables and callbacks that are all accessed using $this->[variablename] from the
	*	template file. Use callbacks to provide variables that should not be instansiated unless used by the template.
	*
	*	Although this template parser allows PHP code to be injected directly there are a few shortcuts that you can use. The following
	*	table describes the equivalents:
	*
	*	VARIABLES
	*	---------
	*
	*	SHORTCUT IN TEXT				SHORTCUT IN ATTRIBUTE			PHP code
	*
	*	{{page.author.name|pad(50,{padlength})}}	{variable}				<?php echo $this->variable; ?>
	*	{{variable|trim}}				{variable|trim}				<?php echo trim($this->variable); ?>
	*	{{variable|lower|trim}}				{variable|lower|trim}			<?php echo trim(strtoupper($this->variable)); ?>
	*	{{page.title}}					{page.title}				<?php echo $this->page['title']; ?>
	*	{{user.department.name}}			{user.department.name}			<?php echo $this->user['department']['name']; ?>
	*
	*	CODE
	*	----
	*	Template commands are added by listening to the SERIA_METATEMPLATE_EXTEND and returning your callback, like this:
	*	SERIA_Hooks::listen(SERIA_METATEMPLATE_EXTEND, 'addMyTags');
	*	function addMyTags($template)
	*	{
	*		$template->addTag('s:block', array($this, 'compileSBlock'));
	*		$template->addTag('/s:block', array($this, 'compileEndSBlock'));
	*	}
	*
	*	SHORTCUT					PHP equivalent
	*
	*	<s:include src="/path" />			<?php include('/path'); ?>
	*/
	class SERIA_MetaTemplate
	{
		protected $_callbacks = array();
		protected $_callbackParams = array();
		protected $_vars = array();
		protected $_tagCompilers = array();
		protected $_includes = array();

		/**
		 *
		 * The parse method does chdir, this is a stack of original cwds.
		 * @var array
		 */
		protected $cwdRestore = array();

		/**
		*	Provide an array of paths to search for the template file, when calling ::parse()
		*	@param array $templatePaths	Array of paths
		*/
		public function __construct()
		{
			SERIA_Hooks::dispatch(SERIA_METATEMPLATE_EXTEND, $this);
		}

		/**
		*	Render a page by searching for templates at strategic locations.
		*	The page template can skip using the contents variable and directly render the
		*	contents, or it can use the contents variable and make use of a contents template.
		*
		*	The function will search for templates at these locations in order:
		*	1. /templates/$application/$page.php
		*	2. /templates/$application/index.php
		*	3. /templates/index.php
		*	4. /seria/apps/$application/templates/$page.php
		*	5. /seria/templates/$page.php
		*
		*	Also, if the template makes use of the $this->contents / {{contents}} variable,
		*	it will search for templates at these locations in order:
		*	1. /templates/$application/$page.contents.php
		*	2. /seria/apps/$application/templates/$page.contents.php
		*
		*	@param string $application 	The name of the application (the directory name under /seria/apps)
		*	@param string $page		The name identifying this page within the selected application
		*	@returns string
		*/
		public static function renderPage($application, $page)
		{
			$template = new SERIA_MetaTemplate();
			$template->addVariableCallback('contents', array($template, 'parse'), array(
				SERIA_ROOT.'/templates/'.$application.'/'.$page.'.contents.php',
				SERIA_ROOT.'/seria/apps/'.$application.'/'.$page.'.contents.php',
			));
			return $template->parse(array(
				SERIA_ROOT.'/templates/'.$application.'/'.$page.'.php',
				SERIA_ROOT.'/templates/'.$application.'/index.php',
				SERIA_ROOT.'/templates/index.php',
				SERIA_ROOT.'/seria/apps/'.$application.'/templates/'.$page.'.php',
				SERIA_ROOT.'/seria/templates/index.php',
			));
		}

		/**
		*	Add a tag compiler. The callback is a function that will receive an SERIA_HtmlTag object and should return either PHP or HTML.
		*	Remember to listen to the closing tag.
		*	@param string $tagName		The tag name you wish to listen to. For example "s:loop". Do not include < and >
		*	@param callback $callback	The function that will handle this tag when it is encountered.
		*/
		public function addTagCompiler($tagName, $callback)
		{
			$this->_tagCompilers[$tagName] = array('callback' => $callback);
		}

		/**
		 *
		 *  Filter to embed an attribute value into php code (inside a <?php block) as
		 *  a string. Returns the php-code for generating the string.
		 */
		public static function embedTagAttributeAsString($attributeValue)
		{
			$output = '';
			while ($attributeValue) {
				$pos = mb_strpos($attributeValue, '<?php');
				if ($output)
					$output .= '.';
				if ($pos === false) {
					$output .= var_export($attributeValue, true);
					break;
				} else if ($pos !== 0) {
					$output .= var_export(mb_substr($attributeValue, 0, $pos), true);
					$attributeValue = mb_substr($attributeValue, $pos);
				} else {
					$attributeValue = mb_substr($attributeValue, 5);
					$pos = mb_strpos($attributeValue, '?>');
					if ($pos !== false)
						$output .= 'eval('.var_export(str_replace('echo', 'return', mb_substr($attributeValue, 0, $pos)), true).')';
					else {
						$output .= 'eval('.var_export(str_replace('echo', 'return', $attributeValue), true).')';
						break;
					}
					$attributeValue = mb_substr($attributeValue, $pos+2);
				}
			}
			return $output;
		}

		/**
		*	Add a callback variable to the template. The callback is executed when the variable is
		*	accessed. Use this for lazy initialization of variables.
		*	@param string $name		The name of the variable to expose on $this->[name]
		*	@param callback $callback	A PHP callback. For PHP 5.3 you can use closures.
		*	@return SERIA_MetaTemplate
		*/
		public function addVariableCallback($name, $callback, array $params=NULL)
		{
			$this->_callbacks[$name] = $callback;
			if($params!==NULL)
				$this->_callbackParams[$name] = $params;
			return $this;
		}

		/**
		*	Add a variable to the template. The variable is returned when $this->[name] is accessed.
		*	@param string $name		The name of the variable to expose on $this->[name]
		*	@param mixed $value		The value for the variable
		*	@return SERIA_MetaTemplate
		*/
		public function addVariable($name, $value)
		{
			$this->_vars[$name] = $value;
			return $this;
		}

		/**
		*	Return the value for the template
		*	@param string $name		The variable name
		*/
		public function __get($name)
		{
			if(array_key_exists($name, $this->_vars))
			{
				return $this->_vars[$name];
			}
			else if(array_key_exists($name, $this->_callbacks))
			{
				if(array_key_exists($name, $this->_callbackParams))
					return call_user_func_array($this->_callbacks[$name], $this->_callbackParams[$name]);
				else
					return call_user_func($this->_callbacks[$name]);
			}
			return '[No template variable named "'.$name.'"]';
			throw new SERIA_Exception('Template variable "'.$name.'" not found.', SERIA_Exception::NOT_FOUND);
		}

		public function __isset($name) {
			return array_key_exists($name, $this->_vars) || array_key_exists($name, $this->_callbacks);
		}

		/**
		*	Add a PHP file to include prior to rendering the template. Can be used for global access control or
		*	similar.
		*/
		public function includeFile($path)
		{
			$this->_includes[] = $path;
		}

		/**
		*	Parse a template file containing PHP code.
		*	@param mixed $templateFileName		The path to your template file. Should be a complete absolute filesystem path.
		*	@return string				Returns the result of the template
		*/
		public function parse($templateFileName)
		{
			if(is_array($templateFileName))
			{
				foreach($templateFileName as $file)
					if(file_exists($file))
						return $this->parse($file);
				$filenames = array();
				foreach($templateFileName as $file)
					$filenames[] = str_replace(SERIA_ROOT, '', $file);
				throw new SERIA_Exception('Template file not found. Searched for "'.implode('", "', $filenames).'".', SERIA_Exception::NOT_FOUND);
			}

//			if(SERIA_DEBUG)
//			{
//				$result = trim(shell_exec('/usr/bin/php -l '.escapeshellarg($templateFileName).' 2>&1 1> /dev/null'));
//				if($result)
//					return "<strong>".$result."</strong>";
//			}

			/*
			 * This can not be left as a relative path because we are going to do chdir before
			 * eval.
			 */
			if (file_exists($templateFileName))
				$templateFileName = _sp_realpath($templateFileName);

			$code = file_get_contents($templateFileName);
			if(empty($code)) throw new SERIA_Exception('Template "'.$templateFileName.'" not found', SERIA_Exception::NOT_FOUND);
			$code = $this->compile($code, $templateFileName);
			if(sizeof($this->_includes)>0)
			{
				$includes = '<?php ';
				foreach($this->_includes as $path)
					$includes .= 'require_once("'.$path.'");';
				$includes .= '?>';
				$code = $includes.$code;
			}
//FRODE
//if($_GET['frode']) {echo "<pre>";echo (htmlspecialchars($code))."</pre>";die();}

			/* overrride the translation context because we do eval from another file */
			$_t_context = _t_setContext($templateFileName);

			ob_start();
			if(sizeof(self::$_stack)>0)
			{
				self::displayError('Missing end tag for '.self::$_stack[sizeof(self::$_stack)-1]);
			}
			else
			{
				array_push($this->cwdRestore, getcwd());
				chdir(dirname($templateFileName));
				eval('?'.'>'.$code);
				chdir(array_pop($this->cwdRestore));
			}
			$contents = ob_get_contents();
			ob_end_clean();
//echo "<pre>".htmlspecialchars($contents)."</pre>";die();

			return $contents;
		}

		/**
		*	Will compile any string into PHP-code that can be evaluated.
		*/
		public function compile($code, $templateFileName=null)
		{
			$result = '';

			$cursor = 0;
			/**
			*	Compile tags matching <s:*> or </s:*>
			*/
			while(($pos = $this->_findFirst($code, array('{{', '<s:','</s:'), $cursor))!==false)
			{
				// KEEP EVERYTHING BEFORE THIS TAG IN THE RESULT
				$result .= substr($code, $cursor, $pos-$cursor);
				$cursor = $pos;

				// WHAT DID WE FIND?
				$found = substr($code, $pos, 2);

				switch($found) {
					case "</" : case "<s" : // FOUND A TAG (remember $this->_findFirst looked only for {{, <s: and </s: )
						// FIND WHERE THE TAG ENDS, WITHOUT STOPPING WITHIN QUOTES
						$endPos = strpos($code, '>', $cursor+1);
						while($this->_isQuoted($code, $endPos, $pos))
							$endPos = strpos($code, '>', $endPos+1);
						$cursor = $endPos + 1;

						// HANDLE THE TAG PROPERLY
						$tag = substr($code, $pos, $endPos-$pos+1);
						$result .= self::_compileTag(new SERIA_HtmlTag($tag), $this->_tagCompilers, $templateFileName);

						break;
					case "{{" : // FOUND A VARIABLE THAT IS TO BE OUTPUT USING PHP's echo (which is why double {{ )
						$end = strpos($code, "}}", $cursor+2);
						$variableName = substr($code, $cursor+2, $end-$cursor-2);

						$cursor += 4 + strlen($variableName);

						$result .= '<'.'?php echo '.self::_compileVariable($variableName, $templateFileName).'; ?'.'>';

						break;
					default : die("ERROR: |$found|");
				}
			}
			$result .= substr($code, $cursor);
			return $result;
		}
		protected function _findFirst($code, array $find, $offset)
		{
			$first = false;
			foreach($find as $match)
			{
				$pos = strpos($code, $match, $offset);
				if($pos!==false && ($first===false || $first>$pos)) $first = $pos;
			}
			return $first;
		}

		public static function attributeToVariable($attribute, $templateFileName)
		{
			$rest = $attribute;
			$result = '';

			$parts = array();
			while(strlen($rest)>0)
			{
				if($rest[0]=='{')
				{ // consume a variable until we find }
					$pos = strpos($rest, '}');
					if($pos === false)
					{
						$parts[] = array('string', $rest);
						$rest = '';
					}
					else
					{
						$parts[] = array('variable', substr($rest, 1, $pos-1));
						$rest = substr($rest, $pos+1);
					}
				}
				else
				{
					$pos = strpos($rest, "{");
					if($pos===false)
					{ // no more variables
						$parts[] = array('string', $rest);
						$rest = '';
					}
					else
					{
						$parts[] = array('string', substr($rest, 0, $pos));
						$rest = substr($rest, $pos);
					}
				}
			}
			if(sizeof($parts)!=1 || $parts[0][0]=='string')
			{ // multiple values are concatenated, we got a constant
				throw new SERIA_Exception('Expected a variable, but got a constant value. Did you concatenate?');
			}
			return self::_compileVariable($parts[0][1], $templateFileName, true);
		}

		public static function attributeToConstant($attribute, $templateFileName)
		{
			$rest = $attribute;
			$result = '';

			$parts = array();
			while(strlen($rest)>0)
			{
				if($rest[0]=='{')
				{ // consume a variable until we find }
					$pos = strpos($rest, '}');
					if($pos === false)
					{
						$parts[] = array('string', $rest);
						$rest = '';
					}
					else
					{
						$parts[] = array('variable', substr($rest, 1, $pos-1));
						$rest = substr($rest, $pos+1);
					}
				}
				else
				{
					$pos = strpos($rest, "{");
					if($pos===false)
					{ // no more variables
						$parts[] = array('string', $rest);
						$rest = '';
					}
					else
					{
						$parts[] = array('string', substr($rest, 0, $pos));
						$rest = substr($rest, $pos);
					}
				}
			}

			$items = array();
			foreach($parts as $part)
			{
				switch($part[0])
				{
					case 'string' :
						$items[] = '\''.str_replace('\'','\\\'', $part[1]).'\'';
						break;
					case 'variable' :
						$items[] = self::_compileVariable($part[1], $templateFileName, false);
						break;
				}
			}
			return implode('.', $items);
		}

		protected static function _compileTag(SERIA_HtmlTag $tag, &$tagCompilers, $templateFileName)
		{
			/*
			 * Parse fields
			 *
			 * All attributes can be inserted directly into PHP-code like any other variable
			 *
			 * Translation checklist:
			 * attr='Hello' gives '"Hello"'
			 * attr='Hello {name}' gives '"Hello ".$this->name'
			 * attr='{name}' gives $this->name
			 * attr='{name} is your name' gives '$this->name." is your name"'
			 *
			$properties = $tag->getProperties();
			foreach ($properties as $name => $value) {

			 */
/*
				$input = $value;
				$proc = '';

				while ($input) {
					$pos = mb_strpos($input, '{');
					if ($pos === false) { // A constant string value, directly insertable into PHP-code
						$proc .= '\''.str_replace('\'','\\'',$input).'\'';
						break; // no more output
					} else if ($pos != 0) { // Complex string value, with concatenation - to be treated as a constant since we concatenate
						$proc .= mb_substr($input, 0, $pos);
						$input = '"'.str_replace('"','\"', mb_substr($input, $pos)).'".';
					} else {
						$input = mb_substr($input, 1);
						$end = mb_strpos($input, '}');
						if ($end !== false) {
							$var = mb_substr($input, 0, $end);
							$input = mb_substr($input, $end+1);
							$proc .= self::_compileVariable($var, $templateFileName);
						} else
							$proc .= '{';
					}
				}
				if ($proc != $value)
					$tag->set($name, $proc);
			}
*/
			$tagName = $tag->isClosing() ? '/' : '';
			$tagName .= $tag->tagName;
			if(isset($tagCompilers[$tagName]))
			{
//echo "PARSING($tagName)";
				$res = call_user_func($tagCompilers[$tagName]['callback'], $tag, $templateFileName);
//echo "GOT(".strlen($res).")<br>\n";
				return $res;
			} else {
				switch($tag->tagName)
				{
					case "s:loop":
						if($tag->isClosing())
							return '<'.'?php } ?'.'>';

						if($tag->get('source') && $tag->get('as'))
						{
							return '<'.'?php foreach('.self::_compileVariable($tag->get('source'), $templateFileName).' as '.($tag->get('key')?self::_compileVariable($tag->get('key'), $templateFileName, true).'=>':'').self::_compileVariable($tag->get('as'),$templateFileName,true).') { ?'.'>';
						}
						else throw new SERIA_Exception('Required parameters for the s:loop tag are "trough" and "as".');
						break;
					default :
						// perhaps return an error, instead of silent ignore?
						return $tag->__toString();
				}
				return $tag;
			}
		}

		protected static function _stringParser($input, &$str_output)
		{
			$got = $input;
			/*
			 * Sanitize..
			 */
			$quoting = substr($input, 0, 1);
			$output = $quoting;
			$input = substr($input, 1);
			while ($input) {
				$char = substr($input, 0, 1);
				$input = substr($input, 1);
				$output .= $char;
				switch ($char) {
					case $quoting:
						/*
						 * End of string reached.
						 */
						$str_output = $output;
						return $input;
						break;
					case '\\':
						if (!$input)
							throw new SERIA_Exception('Unterminated string.');
						$output .= substr($input, 0, 1);
						$input = substr($input, 1);
						break;
				}
			}
			throw new SERIA_Exception('Unterminated string: '.$got);
		}
		protected static function _tokenTreeReassemble($tree, $op=false)
		{
			if (!is_array($tree) || is_string($tree))
				return $tree;
			$assembled = '';
			foreach ($tree as $group) {
				if ($op && $assembled)
					$assembled .= $op;
				if (is_array($group) && !is_string($group))
					$assembled .= '('.self::_tokenTreeReassemble($group).')';
				else
					$assembled .= $group;
			}
			return $assembled;
		}
		protected static function _tokenizeVariable($input, $filters=false)
		{
			/*
			 * Some strings are stored for later, because we know that
			 * we will be called later with them. This is because we decompile
			 * subtrees of code to return a flat split.
			 */
			static $memory = array();

			$delims = array('\'', '"', '(', ')', '|', ',', ' ', "\t", "\n", "\r");
			$delimMark = array();
			$_delims = $delims;
			$mark = array_shift($_delims);
			foreach ($_delims as $delim)
				$delimMark[] = $mark;

//			$hash = hash('md4', $input);
			$hash = md5($input);
			/*
			 * The memory is not (md5) collision safe.
			 * Collisions are very unlikely, but may cause
			 * very strange results.
			 */
			if (!isset($memory[$hash])) {
				$tokens = array();
				while ($input) {
					$char = substr($input, 0, 1);
					switch ($char) {
						/*
						 * String constants:
						 */
						case '\'':
						case '"':
							$input = self::_stringParser($input, $strtoken);
							$tokens[] = $strtoken;
							break;
						/*
						 * Delimiters:
						 */
						case '(':
						case ')':
						case '|':
						case ',':
							$tokens[] = $char;
							$input = substr($input, 1);
							break;
						/*
						 * White space
						 */
						case ' ':
						case "\t":
						case "\n":
						case "\r":
							$input = substr($input, 1);
							break;
						/*
						 * Text tokens:
						 */
						default:
							$cp = str_replace($_delims, $delimMark, $input);
							$len = strpos($cp, $mark);
							if ($len === false)
								$len = strlen($input);
							if (!$len)
								throw new SERIA_Exception('Zero length token, missed a delimiter in switch-case.');
							$token = substr($input, 0, $len);
							$input = substr($input, $len);
							$tokens[] = $token;
					}
				}

				/*
				 * Create a token tree.
				 */

				/* PASS I: Create (list, .., ...) subtrees */
				$tree = array();
				$levelStack = array();
				$level =& $tree;
				foreach ($tokens as $token) {
					$tokenType = substr($token, 0, 1);
					switch ($tokenType) {
						case '(':
							$newlevel = array();
							$levelStack[] =& $level;
							unset($level);
							$level =& $newlevel;
							unset($newlevel);
							break;
						case ')':
							if (!$levelStack)
								throw new SERIA_Exception('Unexpected )');
							$newlevel =& $level;
							unset($level);
							$key = array_keys($levelStack);
							$key = array_pop($key);
							$level =& $levelStack[$key];
							unset($levelStack[$key]);
// Removed this, since it causes variable->getTag() to be translated to variable->getTag
//							if ($newlevel)
								$level[] =& $newlevel;
							unset($newlevel);
							break;
						case '\'':
						case '"':
						case '|':
						case ',':
						default:
							$level[] = $token;
					}
				}
				$tokens = $tree;
			} else {
				$tokens = $memory[$hash];
				if (is_string($tokens))
					$tokens = array($tokens);
			}
			if (!$filters)
				return $tokens;
			/* PASS II: Split by pipes (|) */
			$tree = array();
			$buffer = array();
			foreach ($tokens as $token) {
				if ($token == '|') {
					$num = count($buffer);
					if ($num > 2)
						throw new SERIA_Exception('Syntax error (more than two tokens (filter), '.$num.').');
					else if ($num == 2) {
						$filterName = array_shift($buffer);
						$args = array_shift($buffer);
						$filterType = substr($filterName, 0, 1);
						if ($filterType == '\'' || $filterType == '"') {
							if (is_array($args) && !is_string($args))
								throw new SERIA_Exception('Unexpected (');
						}
						if (!is_array($args) || is_string($args)) {
							$type = substr($args, 0, 1);
							if ($type == '\'' || $type == '"')
								throw new SERIA_Exception('Unexpected string.');
							else
								throw new SERIA_Exception('Unexpected variable or filter name.');
						}
						$tree[] = array($filterName, $args);
					} else if ($num == 1) {
						$tk = array_shift($buffer);
						$type = substr($tk, 0, 1);
						if ($type == '\'' || $type == '"')
							$tree[] = $tk;
						else
							$tree[] = array($tk);
					} else
						throw new SERIA_Exception('Unexpected |.');
				} else
					$buffer[] = $token;
			}
			if ($buffer) {
				$num = count($buffer);
				if ($num > 2)
					throw new SERIA_Exception('Syntax error (more than two tokens (filter), '.$num.').');
				else if ($num == 2) {
					$filterName = array_shift($buffer);
					$args = array_shift($buffer);
					$filterType = substr($filterName, 0, 1);
					if ($filterType == '\'' || $filterType == '"') {
						if (is_array($args) && !is_string($args))
							throw new SERIA_Exception('Unexpected (');
					}
					if (!is_array($args) || is_string($args)) {
						$type = substr($args, 0, 1);
						if ($type == '\'' || $type == '"')
							throw new SERIA_Exception('Unexpected string.');
						else
							throw new SERIA_Exception('Unexpected variable or filter name.');
					}
					$tree[] = array($filterName, $args);
				} else if ($num == 1) {
					$tk = array_shift($buffer);
					$type = substr($tk, 0, 1);
					if ($type == '\'' || $type == '"')
						$tree[] = $tk;
					else
						$tree[] = array($tk);
				} else
					throw new SERIA_Exception('OOps');
			} else if ($tree) {
				throw new SERIA_Exception('Expected filter name.');
			}
			$tokens = $tree;
			/* PASS III: Reassemble subgroups. */
			foreach ($tokens as $key => $group) {
				$gtree = self::_tokenTreeReassemble($group);
				$tokens[$key] = $gtree;
				$memory[md5($gtree)] = $group;
			}
			return $tokens;
		}

		public /*package*/ static function _compileVariable($name, $templateFileName, $mustBeAssignable=false)
		{
//if($name=='video->getPlayer()') $debug = true;
			$parts = self::_tokenizeVariable($name, true);
//if($debug) var_dump($parts);
			$var = $parts[0];
			if ($var[0] != '"' && $var[0] != '\'')
				$varParts = explode(".", $var);
			else
				$varParts = array($var);

			$finalVar = '';
			if(($l = sizeof($varParts))===1)
			{
				$finalVar .= trim($varParts[0]);
			}
			else
			{
				$finalVar .= trim($varParts[0]);
				for($i = 1; $i < $l; $i++)
					$finalVar .= '["'.str_replace('"', '\"', trim($varParts[$i])).'"]';
			}

			if($mustBeAssignable && $finalVar[0]!='$')
				throw new SERIA_MetaTemplateException('Can\'t assign values to "'.$name.'". Try "$'.$name.'" instead.');

			// ignore constants and quoted strings
			if(strpos('$"\'0123456789', $finalVar[0])===false)
			{
//				if($mustBeAssignable)
//					$finalVar = '$this->'.$finalVar;
//				else
//				$finalVar = 'var_dump($this->'.$finalVar.')';
				$finalVar = '$this->'.$finalVar;
			}

			if(($filterL = sizeof($parts))===1)
			{
				return $finalVar;
			}
			else
			{
// why not?				if($mustBeAssignable) throw new SERIA_Exception('Unable use filters on user defined variables ("'.$name.'").');

				for($filterI = 1; $filterI < $filterL; $filterI++)
				{
					$filterSpec = $parts[$filterI];
					$tree = self::_tokenizeVariable($filterSpec);
					$filterName = $tree[0];
					if (!is_string($filterName))
						throw new SERIA_Exception('Expected filter name.');
					if (isset($tree[1]))
						$fa = $tree[1];
					else
						$fa = null;
					$filterArguments = array();
					if (is_array($fa)) {
						$group = array();
						foreach ($fa as $key => $arg) {
							if ($arg == ',') {
								$filterArguments[] = $group;
								$group = array();
							} else
								$group[] = $arg;
						} 
						if ($group)
							$filterArguments[] = $group;
					}
					$map = array(
						'html'				=> 'SERIA_Html::sanitize(%%,SERIA_Html::PUBLIC_SANITIZE)',
						'publichtml'			=> 'SERIA_Html::sanitize(%%,SERIA_Html::PUBLIC_SANITIZE)',
						'guesthtml'			=> 'SERIA_Html::sanitize(%%,SERIA_Html::GUEST_SANITIZE)',
						'normalhtml'			=> 'SERIA_Html::sanitize(%%,SERIA_Html::NORMAL_SANITIZE)',
						'adminhtml'			=> 'SERIA_Html::sanitize(%%,SERIA_Html::ADMIN_SANITIZE)',
						'intval'			=> 'intval(%%)',
						'floatval'			=> 'floatval(%%)',
						
//TODO: Add all filters from Django: http://docs.djangoproject.com/en/dev/ref/templates/builtins/#ref-templates-builtins-filters
						'inc' 				=> array('(%%+1)','(%%+%1)'),			// increment
						'addslashes' 			=> 'addslashes(%%)',
						'addcslashes' 			=> 'addcslashes(%%)',
						'trim' 				=> 'trim(%%)',
						'ltrim' 			=> 'ltrim(%%)',
						'rtrim' 			=> 'rtrim(%%)',
						'crc32' 			=> 'crc32(%%)',
						'html_entity_decode' 		=> 'html_entity_decode(%%, ENT_QUOTES, "UTF-8")',
						'html_entity_encode' 		=> 'htmlentities(%%, ENT_QUOTES, "UTF-8")',		// added for consistency with htmlentities/html_entity_decode
						'htmlentities' 			=> 'htmlentities(%%, ENT_QUOTES, "UTF-8")',
						'htmlspecialchars' 		=> 'htmlspecialchars(%%, ENT_QUOTES, "UTF-8")',
						'htmlspecialchars_decode' 	=> 'htmlspecialchars_decode(%%, ENT_QUOTES)',
						'join'				=> 'implode(%1, %%)',
						'implode' 			=> 'implode(%1, %%)',
						'ucfirst' 			=> 'ucfirst(%%)',
						'lcfirst' 			=> 'lcfirst(%%)',
						'md5' 				=> 'md5(%%)',
						'metaphone' 			=> 'metaphone(%%)',
						'nl2br' 			=> 'nl2br(%%)',
						'number_format'			=> array('number_format(%%)','number_format(%%,%1)','number_format(%%,%1,%2,%3)'),
						'ord'				=> 'ord(%%)',
						'quoted_printable_decode'	=> 'quoted_printable_decode(%%)',
						'quoted_printable_encode'	=> 'quoted_printable_encode(%%)',
						'quotemeta'			=> 'quotemeta(%%)',
						'sha1'				=> 'sha1(%%)',
						'soundex' 			=> 'soundex(%%)',
						'ireplace'			=> 'str_ireplace(%1,%2,%%)',				// alias for str_ireplace
						'str_ireplace' 			=> 'str_ireplace(%1,%2,%%)',
						'pad'				=> array('str_pad(%%, %1)','str_pad(%%,%1,%2)'),	// alias for str_pad
						'str_pad'			=> array('str_pad(%%, %1)','str_pad(%%,%1,%2)'),
						'repeat'			=> 'str_repeat(%%, %1)',				// alias for str_repeat
						'str_repeat'			=> 'str_repeat(%%, %1)',
						'replace'			=> 'str_replace(%1,%2,%%)',				// alias for str_replace
						'str_replace'			=> 'str_replace(%1,%2,%%)',
						'strip_tags'			=> 'strip_tags(%%)',
						'stripcslashes'			=> 'stripcslashes(%%)',
						'stripslashes'			=> 'stripslashes(%%)',
						'strlen'			=> 'strlen(%%)',
						'reverse'			=> 'strrev(%%)',
						'strrev'			=> 'strrev(%%)',
						'lower'				=> 'mb_strtolower(%%)',
						'strtolower'			=> 'mb_strtolower(%%)',
						'upper'				=> 'mb_strtoupper(%%)',
						'strtoupper'			=> 'mb_strtoupper(%%)',
						'left'				=> 'mb_substr(%%,0,%1)',
						'right'				=> 'mb_substr(%%,-%1)',
						'dateformat'			=> 'SERIA_MetaTemplate::renderDateFormat(%%,%1)',
						'date'				=> 'SERIA_MetaTemplate::renderDate(%%)',
						'datetime'			=> 'SERIA_MetaTemplate::renderDateTime(%%)',
						'time'				=> 'SERIA_MetaTemplate::renderTime(%%)',
						'_t'				=> array(
							'call' => array('SERIA_MetaTemplate', '_translate'),
							'args' => array(var_export($templateFileName, true))
						),
						'toJson'			=> 'SERIA_Lib::toJSON(%%)'
					);
					if(!isset($map[$filterName])) throw new SERIA_Exception('Unknown filter "'.$filterName.'".');

					$arguments = array();
					foreach ($filterArguments as $group) {
						if (count($group) == 1)
							$arguments[] = self::_compileVariable(array_shift($group), $templateFileName);
						else {
							$assembly = self::_tokenTreeReassemble($group);
							$arguments[] = self::_compileVariable($assembly, $templateFileName);
						}
					}

					// Match the signature
					$argCount = count($arguments);

					$template = $map[$filterName];
					if(is_array($template))
					{
						if (!isset($template['call'])) {
							foreach($template as $t)
							{
								if(strpos($t, '%'.$argCount)!==false && strpos($t, '%'.($argCount+1))===false)
								{
									$template = $t;
									break;
								}
							}
						} else {
							/*
							 * Call a special filter callback..
							 */
							if (isset($template['args']))
								$args = $template['args'];
							else
								$args = array();
							$args[] = $finalVar;
							foreach ($arguments as $arg)
								$args[] = $arg;
							$finalVar = 'call_user_func('.var_export($template['call'], true).', '.implode(', ', $args).')';
							continue;
						}
					}
					if(is_array($template)) // if it is not an array, it means a matching template was not found in the previous step
						throw new SERIA_Exception('Incorrect number of arguments to the filter "'.$filterName.'".');

					$finalVar = str_replace('%%',$finalVar,$template);

					foreach($arguments as $key => $arg)
						$finalVar = str_replace('%'.($key+1), $arg, $finalVar);
				}
				return $finalVar;
			}
		}

		/**
		* For use when compiling tags to display error messages to users.
		*/
		public /*package*/ static function displayError($message)
		{
			SERIA_ProxyServer::noCache();
			echo "<span class='SERIA_MetaTemplate_error'><strong>MetaTemplate error: </strong> ".$message."</span>";
		}

		protected function _isQuoted($string, $pos, $offset = 0)
		{
			$string = mb_substr($string, $offset, $pos-$offset);
			$l = mb_strlen($string);
			$q = false;
			for($i = 0; $i < $l; $i++)
			{
				$c = mb_substr($string, $i, 1);
				if($c === '"' || $c === "'")
				{
					if($q === $c) $q = false;
					else if (!$q) $q = $c;
				}
			}
			return $q!==false;
		}

		/*
		 * Special filters
		 */

		/**
		 * Parse a date and render it to a user defined format
		 * Filter: |dateformat
		 */
		public static function renderDateFormat(SERIA_DateTimeMetaField $datetime, $format)
		{
			return $datetime->getDateTimeObject()->render($format);
		}

		/**
		 * Date renders a supplied date to the current users locale
		 * @filter |date
		 */
		public static function renderDate(SERIA_DateTimeMetaField $datetime) {
			return $datetime->getDateTimeObject()->renderUserDate();
		}

		/**
		 * DateTime renders a supplied date and time to the current users locale
		 * @todo Actually use the locale
		 * @filter |datetime
		 */
		public static function renderDateTime($datetime) {
			return $datetime->getDateTimeObject()->renderUserDateTime();
		}

		/**
		 * Time renders a supplied time to the current users locale
		 * @todo Actually use the locale
		 * @filter |time
		 */
		public static function renderTime($datetime) {
			return $datetime->getDateTimeObject()->renderUserTime();
		}


		/**
		 * Special filter. Do not call from normal code.
		 */
		public static function _translate()
		{
			$args = func_get_args();
			$filename = array_shift($args);
			$string = array_shift($args);
			$params = array();
			foreach ($args as $param)
				$params[] = $param;
			return _t_with_caller($string, $params, null, $filename);
		}


		/**
		*
		*/
		protected static $_stack = array();
		/**
		*	Special methods for helping with debugging meta template nesting. Push tags that require a closing tag and pop them when closing it.
		*/
		public static function push($tag, $data=NULL)
		{
			array_push(self::$_stack, array($tag, $data));
		}

		/**
		*	Check if a tag is in the stack.
		*/
		public static function inStack($tag)
		{
			foreach(self::$_stack as $data)
				if($data[0]==$tag)
					return true;
			return false;
		}

		/**
		*	Special method for helping with debugging meta template nesting. Pop whenever handling a closing tag, then catch any exception from this method.
		*	@param $tag		The tag expected to match in the hierarchy
		*	@return $data		Returns the $data provided by SERIA_MetaTemplate::push($tag, $data)
		*/
		public static function pop($tag)
		{
			$l = sizeof(self::$_stack);
			if($l===0) throw new SERIA_MetaTemplateException('No opening tag for "'.$tag.'".');
			if(self::$_stack[$l-1][0]!==$tag) throw new SERIA_MetaTemplateException('Expected closing tag for "'.self::$_stack[$l-1][0].'" but got "'.$tag.'".');
			$data = array_pop(self::$_stack);
			return $data[1] ? $data[1] : $data[0];
		}
	}
