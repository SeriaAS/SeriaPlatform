<?php
	class SERIA_MetaTemplateUrlsVariable implements ArrayAccess
	{
		protected $_manifest = NULL;
		protected $_path = NULL;

		public function __construct($manifest=NULL, $path=NULL)
		{
			$this->_manifest = $manifest;
			$this->_path = $path;
		}

		public function __toString() {
			return SERIA_Meta::manifestUrl($this->_manifest, $this->_path)->__toString();
		}

		public function offsetExists($offset) {
			if($this->_manifest === NULL)
				return SERIA_Manifests::getManifest($offset) ? true : false;
			return true;
			return false;
		}
		public function offsetGet($offset) {
			if(!$this->offsetExists($offset))
			{
				return NULL;
			}

			if($this->_manifest === NULL)
			{
				return new self($offset);
			}
			else
			{
				if($this->_path===NULL)
					return new self($this->_manifest, $offset);
				else
					return new self($this->_manifest, $this->_path.'/'.$offset);
			}
		}
		public function offsetSet($offset, $value) { throw new SERIA_Exception('Unable to assign values to a Manifest URL.', SERIA_Exception::USAGE_ERROR);}
		public function offsetUnset($offset) { throw new SERIA_Exception('Unable to unset values from a Manifest URL.', SERIA_Exception::USAGE_ERROR);}
	}
