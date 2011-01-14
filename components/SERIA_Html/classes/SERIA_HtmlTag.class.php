<?php
	/**
	*	This class is used to represent a single html tag. It must not be confused with
	*	the SERIA_HtmlNode class which represent a node and its children.
	*/
	class SERIA_HtmlTag
	{
		public $tagName;

		protected static $_bytesConsumed = NULL;	// number of bytes consumed from the passed html for the last call to consumeTag

		protected $_isClosingTag=false, $_isSelfClosed=false, $_properties=array();
		public function __construct($html, $skipWhitespace=false)
		{
			if($skipWhitespace)
			{
				$l = strlen($html);
				$html = ltrim($html);
				$consumed = $l - strlen($html);
			}
			else
				$consumed = 0;

			if(empty($html)) throw new SERIA_Exception('$html required for constructor');
			if($html[0] != '<') throw new SERIA_Exception('HTML tags start with "<", got: "'.htmlspecialchars(substr($html, 0, 50)).'".');

			$l = strlen($html);

			// STATES:
			// <example attribute='hello' attribute = "there" >

			$quoted = false;
			$newHtml = '<';
			for($i = 1; $i < $l; $i++)
			{
				$c = $html[$i];
				$newHtml .= $c;
				if($quoted && $c === $quoted)
				{
					$quoted = false;
				}
				else if($c === "'" || $c === '"')
				{
					$quoted = $c;
				}
				else if($c === '>')
				{ // found end of tag
					break;
				}
			}
			self::$_bytesConsumed = $consumed + strlen($newHtml);

			$html = $newHtml;


			if($html[strlen($html)-1] != '>') throw new SERIA_Exception('HTML tags end with ">".');



			// remove the < and >
			$html = mb_substr($html, 1, mb_strlen($html)-2);

			// determine if this is a closing tag
			$this->_isClosingTag = $html[0]=='/';
			if($this->_isClosingTag)
				$html = substr($html, 1);

			// get the tag name
			preg_match('|([a-zA-Z][a-zA-Z0-9_\:\-]*)|', $html, $matches);
			$this->tagName = $matches[1];
			$html = ltrim(substr($html, strlen($this->tagName)));

			while($html != "" && $html != "/")
			{
				// get the property name
				preg_match('|([a-zA-Z0-9\:]*)|', $html, $matches);
				$propertyName = strtolower($matches[1]);
				$html = ltrim(substr($html, strlen($propertyName)));
				if($html[0] == "=")
				{ // the property has a value
					$html = ltrim(substr($html, 1));
					if($html[0]=="'")
					{
						$propertyValue = substr($html, 1, strpos($html, "'", 1)-1);
						$html = ltrim(substr($html, strlen($propertyValue)+2));
					}
					else if($html[0]=='"')
					{
						$propertyValue = substr($html, 1, strpos($html, '"', 1)-1);
						$html = ltrim(substr($html, strlen($propertyValue)+2));
					}
					else
					{
						preg_match('|([a-zA-Z0-9#])|', $html, $matches);
						$propertyValue = $matches[1];
						$html = ltrim(substr($html, strlen($propertyValue)));
					}

					$this->_propertiesEscape[$propertyName] = true;
//					$this->_properties[$propertyName] = $propertyValue;

					/*
					 * Uses UTF-8 as output-charset assuming that the platform always uses UTF-8 internally,
					 * which I believe is the case right now (now is relative to the commit-date :).
Should perhaps move to the ->get method, and be optional (default $entityDecode=true)?
					 */
					$this->_properties[$propertyName] = html_entity_decode($propertyValue, ENT_QUOTES, 'UTF-8');
				}
				else if ($propertyName)
				{
					$this->_propertiesEscape[$propertyName] = true;
					$this->_properties[$propertyName] = $propertyName;
				}
				else
				{
					/* Invalid property name / character: trying to skip this */
					$i = 0;
					$len = strlen($html);
					while ($i < $len) {
						$charAt = substr($html, $i, 1);
						if (trim($charAt) == '' || $charAt == "\n" || $charAt == "\r" || $charAt == "\t" || $charAt == '/' || $charAt == '>')
							break;
						$i++;
					}
					if ($i == 0 && $len > 0) {
						if ($charAt == '>') {
							$html = substr($html, 1);
							/*
							 * There is a parsing error here, and it looks like the
							 * first parsing missed the tag-end-mark. Check if we
							 * should unconsume some bytes.
							 */
							if (strlen($html)) {
								/* Unconsume */
								self::$_bytesConsumed -= strlen($html);
								$html = '';
							}
								
						}
						else if ($charAt == '/')
						{
							/*
							 * There is a parsing error here, and it looks like the
							 * first parsing could have missed the tag-end-mark. Check if we
							 * should unconsume some bytes.
							 */
							$html = ltrim(substr($html, 1));
							if ($html) {
								$nextChar = substr($html, 0, 1);
								if ($nextChar == '>') {
									$html = substr($html, 1);
									/* Unconsume */
									if (strlen($html))
										self::$_bytesConsumed -= strlen($html);
									$html = '/'; /* Trigger the self-closed clause artificially */
								}
							} else
								$html = '/'; /* Let the self-closed clause take this */
						}
						else
							$html = ltrim(substr($html, 1)); /* Skip one bad character */
					}
					else
						$html = ltrim(substr($html, $i));
				}
			}
			$this->_isSelfClosed = $html=="/";
		}

		public static function getBytesConsumed()
		{
			return self::$_bytesConsumed;
		}

		public function __toString()
		{
			$result = "<".($this->_isClosingTag?'/':'').$this->tagName;
			foreach($this->_properties as $name => $value)
			{
				if($this->_propertiesEscape[$name])
					$result .= " ".$name."=\"".htmlspecialchars($value)."\"";
				else
					$result .= " ".$name."=\"".$value."\"";
			}
			$result .= (SERIA_XHTML && $this->_isSelfClosing?" />":">");
			return $result;
		}

		public function getAttributes() { return $this->getProperties(); }
		public function getProperties()
		{
			return $this->_properties;
		}

		public function get($propertyName)
		{
			if(isset($this->_properties[$propertyName]))
				return $this->_properties[$propertyName];
			return NULL;
		}

		/**
		*	Set a property on the HTML tag.
		*	@param string $propertyName	The name of the property
		*	@param string $value		The value of the property
		*	@param boolean $escape		Wether to html escape the property when rendering it
		*	@return SERIA_HtmlTag
		*/
		public function set($propertyName, $value, $escape=true)
		{
			$this->_propertiesEscape[$propertyName] = $escape;
			$this->_properties[$propertyName] = $value;
			return $this;
		}

		public function remove($propertyName)
		{
			unset($this->_propertiesEscape[$propertyName]);
			unset($this->_properties[$propertyName]);
			return $this;
		}

		public function isClosing()
		{
			return $this->_isClosingTag;
		}

		public function isSelfClosed() 
		{
			return $this->_isSelfClosed;
		}
	}
