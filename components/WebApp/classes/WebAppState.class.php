<?php
/**
*	An object that can be populated with state information. This state information will be cached along with the result
*	of sub requests. For example:
*
*				URL					Template
*	1. Browser requests	www.yoursite.com/some/page		./views/default.php
*	2. [sub request]	[internal]/content/some/page		./views/content/some/page.php
*	3. [sub request]	[internal]/blocks/leftmenu		./views/blocks/leftmenu/index.php
*
*	In the above request, a $state object of class WebAppState is available for the default.php template. The $state object
*	is always empty when a sub request is processed. The default.php template sets the value $state->pageTitle = 'Page Title';.
*	Next, the default.php template performs a sub request to /content/some/page. Within /content/some/page.php, the $state object
*	will be empty. If /content/some/page.php then sets $state->pageTitle = 'Some Page';, the $state object for the sub request will
*	be merged into the parent template $state object.
*/
class WebAppState {

	protected $_properties = array();

	/**
	*	Takes the properties of this object and merges them into another objects properties recursively. Properties in the other
	*	object will be overwritten if they have the same keys, except for arrays which will be merged recursively.
	*/
	public function mergeInto(WebAppState $parent) {
		foreach($this->_properties as $k => $v) {
			if(is_array($v) && !isset($parent->$k)) {
				$parent->$k = array_replace_recursive($parent->$k, $v);
			} else {
				$parent->$k = $v;
			}
		}
	}

	public function __set($name, $value) {
		self::_validateValue($value);
		$this->_properties[$name] = $value;
	}

	public function __get($name) {
		return $this->_properties[$name];
	}

	public function __isset($name) {
		return isset($this->_properties[$name]);
	}

	public function __unset($name) {
		unset($this->_properties[$name]);
	}

	protected static function _validateValue($value) {
		if(is_scalar($value)) return TRUE;
		if(is_array($value)) {
			foreach($value as $k => $v) {
				self::_validateValue($v);
			}
			return TRUE;
		}
		throw new SERIA_Exception('WebAppState can only hold scalar values, due to caching.');
	}

}
