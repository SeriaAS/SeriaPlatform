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
			preg_match('|([a-zA-Z0-9\:]*)|', $html, $matches);
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
					/*
					 * Uses UTF-8 as output-charset assuming that the platform always uses UTF-8 internally,
					 * which I believe is the case right now (now is relative to the commit-date :).
					 */
					$this->_properties[$propertyName] = html_entity_decode($propertyValue, ENT_QUOTES, 'UTF-8');
				}
				else
				{
					$this->_properties[$propertyName] = $propertyName;
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
				$result .= " ".$name."=\"".htmlspecialchars($value)."\"";
			$result .= (SERIA_XHTML && $this->_isSelfClosing?" />":">");
			return $result;
		}

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

		public function set($propertyName, $value)
		{
			$this->_properties[$propertyName] = $value;
		}

		public function remove($propertyName)
		{
			unset($this->_properties[$propertyName]);
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
