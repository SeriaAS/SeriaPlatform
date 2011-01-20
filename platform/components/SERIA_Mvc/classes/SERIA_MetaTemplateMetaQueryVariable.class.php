<?php
	/**
	*	MetaTemplate variable wrapper allowing access of any data set.
	*	{{Meta.SomeMetaClass}} all SomeMetaClass instances
	*	{{Meta.SomeMetaClass.disabledView}} performs a call on SomeMetaClass.disabledView() which must return only disabled user objects
	*	{{Meta.SomeMetaClass.someAction}} returns the SomeMetaClass::someAction action object (only for static methods)
	*/
	class SERIA_MetaTemplateMetaQueryVariable implements IteratorAggregate, ArrayAccess
	{
		protected $_class;
		public function __construct($metaObjectClass)
		{
			if(!is_subclass_of($metaObjectClass, 'SERIA_MetaObject'))
				throw new SERIA_Exception('Expects a SERIA_MetaObject classname as parameter, but got "'.$metaObjectClass.'".');
			$this->_class = $metaObjectClass;
		}

		/**
		* {{Meta.SomeMetaClass}}
		*/
		public function getIterator() {
			return SERIA_Meta::all($this->_class);
		}

		/**
		* Provides variables such as {{Meta.SomeMetaClass.someView}} and {{Meta.SomeMetaClass.someAction}}
		*/
		public function offsetExists($offset)
		{
			if(substr($offset, -4)==='View' || substr($offset, -6)==='Action')
			{
// must check if the method is declared static, because PHP is insanely stupidly built allowing non-static methods be called as static.
				if(method_exists($this->_class, $offset))
				{
					$ref = new ReflectionMethod($this->_class, $offset);
					if(!$ref->isStatic())
						throw new SERIA_Exception('Trying to access "'.$this->_class.'::'.$offset.'" but "'.$offset.'" must be called on an object.');
					return is_callable(array($this->_class, $offset));
				}
			}
			return false;
		}

		/**
		* Provides the variable {{Meta.SomeMetaClass.someView}}
		*/
		public function offsetGet($offset) {
			// allow only calls to functions allowed by offsetExists
			if(!$this->offsetExists($offset))
				return NULL;
			return call_user_func(array($this->_class, $offset));
		}

		public function offsetSet($offset, $value) { throw new SERIA_Exception('Unable to assign values to MetaQuery.', SERIA_Exception::INCORRECT_USAGE);}
		public function offsetUnset($offset) { throw new SERIA_Exception('Unable to unset values on MetaQuery.', SERIA_Exception::INCORRECT_USAGE);}
	}
