<?php
	class SFluentQuery extends SData implements Iterator, ArrayAccess
	{
		public $className;
		private $rs = false, $current = false, $wheres = array(), $order = false, $limit = false, $spec;
		private $offset = 0;

		function __construct($className, $where = NULL, array $args = NULL)
		{
			if(!class_exists($className))
				throw new SERIA_Exception($className.' does not exist! (Plural/singular mistake?)');
			if(!is_subclass_of($className, 'SFluentObject'))
				throw new SERIA_Exception($className.' must extend the SFluentObject class.');
			$this->className = $className;
			$this->spec = SFluent::_getSpec($className);
			
			$this->rs = new SDBData($this->spec['table']);
			if(!empty($this->spec['selectWhere'])) $this->rs->where($this->spec['selectWhere']);
			if($where !== NULL) $this->rs->where($where, $args);
		}

		function grid() {
			return new SFluentGrid($this);
		}

/*
		function getFieldSpec()
		{
			return SFluent::getFieldSpec($this->className);
//			return eval('return '.$this->className.'::getFieldSpec();');
		}
*/

		private function buildSQL()
		{
			$sql = 'SELECT * FROM '.$this->spec['table'];
			if(sizeof($this->wheres)>0)
				$sql .= ' WHERE ('.implode(') AND (', $this->wheres).')';
			if($this->order !== false)
				$sql .= ' ORDER BY '.$this->order;
			if($this->limit !== false)
				$sql .= ' LIMIT '.$this->limit;

			return $sql;
		}
		private function refreshQuery()
		{
			$this->rs = SERIA_Base::db()->query($this->buildSQL(), $this->args)->fetchAll(PDO::FETCH_ASSOC);
			$this->offset = 0;
			$this->current = false;
		}

		// ITERATOR
		function current()
		{
			if($this->rs===false) $this->refreshQuery();
			if(!isset($this->rs[$this->offset]))
				return false;
			return SFluent::load($this->className, $this->rs[$this->offset]);
		}

		function key()
		{
			if(($current = $this->current())!==false)
			{
				$row = $current->FluentBackdoor('get_row');
				return $row[$this->spec['primaryKey']];
			}
			return false;
		}

		function next()
		{
			$this->offset++;
			return $this->current();
		}

		function rewind()
		{
			if($this->rs===false) $this->refreshQuery();
			$this->offset = 0;
			return $this->current();
		}

		function valid()
		{
			return $this->current() ? true : false;
		}

		// ArrayAccess
		function offsetExists($offset)
		{
			throw new SERIA_Exception('Cannot test for existence. This would be as slow as fetching directly.');
		}

		function offsetGet($offset)
		{
			if($this->rs!==false)
			{ // Optimization: we have prefetched a query, so we do not touch the database again if the object is in the current recordset
				foreach($this->rs as $row)
				{
					if($row[$this->spec['primaryKey']] == $offset)
					{
						return SFluent::load($this->className, $offset);
					}
				}
			}

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
