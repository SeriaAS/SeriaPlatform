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
class WebAppState implements ArrayAccess, IteratorAggregate {

	protected $_vals = array();

	public function __construct(array $values=NULL, $parent=NULL) {
		if($values) {
			$this->_parent = $parent;
			foreach($values as $k => $v)
				$this[$k] = $v;
		}
	}

	public function getIterator() {
		return new ArrayIterator($this->_vals);
	}

	/**
	*	Takes the properties of this object and merges them into another objects properties recursively. Properties in the other
	*	object will be overwritten if they have the same keys, except for arrays which will be merged recursively.
	*/
	public function mergeInto(WebAppState $parent) {
		foreach($this->_vals as $k => $v) {
			if(is_array($v) && !isset($parent[$k])) {
				$parent[$k] = array_replace_recursive($parent[$k], $v);
			} else {
				$parent[$k] = $v;
			}
		}
	}

	public function offsetExists($name) {
		return isset($this->_vals[$name]);
	}

	public function offsetGet($name) {
		return $this->_vals[$name];
	}

	public function __get($name) {
		return $this->_vals[$name];
	}

	public function __isset($name) {
		return isset($this->vals[$name]);
	}

	public function offsetSet($name, $value) {
		if(is_array($value)) {
			return $this->_vals[$name] = new WebAppState($value, $this);
		} else {
			self::_validateValue($value);
			return $this->_vals[$name] = $value;
		}
	}

	public function offsetUnset($name) {
		unset($this->_vals[$name]);
	}

	protected static function _validateValue($value) {
		if(is_scalar($value))
			return TRUE;
		if(is_array($value)) {
			foreach($value as $k => $v) {
				self::_validateValue($v);
			}
			return TRUE;
		}
		if(is_object($value) && get_class($value)==__CLASS__)
			return TRUE;
		throw new WebApp_Exception('Trying to assign non-scalar value to $state object.', WebAppRequest::HTTP_NOT_IMPLEMENTED);
	}

	public function toArray() {
		$res = array();
		foreach($this->_vals as $k => $v) {
			if(is_scalar($v))
				$res[$k] = $v;
			else
				$res[$k] = $v->toArray();
		}
		return $res;
	}

	public function __toString() {
		$res = new WebAppRequestFake("<pre>{\n".$this->renderStruct('  ')."\n}</pre>");
		return $res->__toString();
	}

	protected function renderStruct($indent='') {
		$res = array();
		foreach($this->_vals as $k => $v) {
			if(is_scalar($v)) {
				$res[] = $indent.json_encode($k)." : ".json_encode($v);
			} else {
				$res[] = $indent.json_encode($k)." : {\n".$v->renderStruct($indent.'    ')."\n$indent}";
			}
		}
		return implode($res, ",\n");
	}

}
