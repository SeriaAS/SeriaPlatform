<?php
	class SERIA_MetaQuery implements Iterator, ArrayAccess
	{
		public $className;
		protected $spec;
		protected $_data;

		function __construct($className, $where = NULL, array $args = NULL)
		{
			if(!class_exists($className))
				throw new SERIA_Exception($className.' does not exist! (Plural/singular mistake?)');
			if(!is_subclass_of($className, 'SERIA_MetaObject'))
				throw new SERIA_Exception($className.' must extend the SERIA_MetaObject class.');
			$this->className = $className;
			$this->spec = SERIA_Meta::_getSpec($className);

			$this->_data = new SERIA_DbData($this->spec['table'], $this->spec['primaryKey']);
			if(!empty($this->spec['selectWhere'])) $this->_data->where($this->spec['selectWhere']);
			if($where !== NULL) $this->_data->where($where, $args);
			if(method_exists($className, 'MetaSelect'))
			{
				if($whereTmp = call_user_func(array($className, 'MetaSelect')))
				{
					$this->where($whereTmp);
				}
			}

		}

		/**
		*	Returns the number of rows matching this query
		*/
		public function count()
		{
			return $this->_data->count();
		}

		public function mergeWith(SERIA_MetaQuery $query)
		{
			return new SERIA_MergedMetaQuery($this, $query);
		}

		public function getSpec() { return $this->spec; }

		function grid() {
			return new SERIA_MetaGrid($this);
		}

		function treeGrid($parentIdColumn)
		{
			return new SERIA_MetaTreeGrid($this, $parentIdColumn);
		}

		/**
		*	Add the sql to the WHERE part of the query. This is ADDED to the SQL with AND;
		*	$recordset->where('id=1')->where('name="hello"') is equivalent to
		*	$recordset->where('id=1 AND name="hello"')
		*/
		final public function where($where, $args = NULL) {
			$this->_data->where($where, $args);
			return $this;
                }
                final public function limit($a,$b = NULL) {
			$this->_data->limit($a,$b);
			return $this;
                }
                final public function order($field) {
                        $this->_data->order($field);
                        return $this;
                }

		// ITERATOR
		function current()
		{
			if($row = $this->_data->current())
				return SERIA_Meta::load($this->className, $row);
			return false;
		}

		function key()
		{
			return $this->_data->FluentBackdoor('get_key');
		}

		function next()
		{
			$this->_data->next();
			return $this->current();
		}

		function rewind()
		{
			$this->_data->next();
			return $this->current();
		}

		function valid()
		{
			return $this->current();
		}

		// ArrayAccess
		function offsetExists($offset)
		{
			throw new SERIA_Exception('Cannot test for existence. This would be as slow as fetching directly.');
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
