<?php
	/**
	*	This class is used to represent a single html tag. It must not be confused with
	*	the SERIA_HtmlNode class which represent a node and its children.
	*/
	class SERIA_HtmlTag
	{
		public $tagName;
		protected $_isClosingTag=false, $_isSelfClosed=false, $_properties=array();
		public function __construct($html)
		{
			if(empty($html)) throw new SERIA_Exception('$html required for constructor');
			if($html[0] != '<') throw new SERIA_Exception('HTML tags start with "<", got: "'.htmlspecialchars(substr($html, 0, 50)).'".');

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
				$propertyName = $matches[1];
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
					$this->_properties[$propertyName] = $propertyValue;
				}
				else
				{
					$this->_properties[$propertyName] = $propertyName;
				}
			}
			$this->_isSelfClosed = $html=="/";
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
