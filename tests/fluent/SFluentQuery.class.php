<?php
	class SFluentQuery extends SDBData implements Iterator // , ArrayAccess
	{
		public $className;
		protected $spec;

		function __construct($className, $where = NULL, array $args = NULL)
		{
			if(!class_exists($className))
				throw new SERIA_Exception($className.' does not exist! (Plural/singular mistake?)');
			if(!is_subclass_of($className, 'SFluentObject'))
				throw new SERIA_Exception($className.' must extend the SFluentObject class.');
			$this->className = $className;
			$this->spec = SFluent::_getSpec($className);
			parent::__construct($this->spec['table']);
			if(!empty($this->spec['selectWhere'])) $this->where($this->spec['selectWhere']);
			if($where !== NULL) $this->where($where, $args);
		}

		function grid() {
			return new SFluentGrid($this);
		}

		// ITERATOR
		function current()
		{
			if($row = parent::current())
				return SFluent::load($this->className, $row);
			return false;
		}


		// ArrayAccess
		function offsetExists($offset)
		{
			throw new SException('Cannot test for existence. This would be as slow as fetching directly.');
		}

		function offsetGet($offset)
		{
			$q = clone $this;
			$q->where($this->spec['primaryKey'].'=:pk', array('pk' => $offset));
			return $q->current();
		}

		function offsetSet($offset, $value)
		{
			throw new SERIA_Exception('Cannot insert objects this way.');
		}

		function offsetUnset($offset)
		{
			throw new SERIA_Exception('Cannot delete objects this way.');
		}
	}
