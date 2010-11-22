<?php
	class SERIA_FluentQuery implements Iterator, ArrayAccess
	{
		public $className;
		private $rs = false, $current = false, $classSpec, $table, $wheres = array(), $args = array(), $order = false, $limit = false, $primaryKey = false;
		private $offset = 0;

		function __construct($className, $where = false, array $args = NULL)
		{
			if(!class_exists($className))
				throw new SERIA_Exception($className.' does not exist! (Plural/singular mistake?)');
			$classes = array_flip(class_implements($className));
			if(!isset($classes['SERIA_IFluentObject']))
				throw new SERIA_Exception($className.' must implement the SERIA_IFluentObject interface.');
			$this->className = $className;
			eval('$fluentSpec = '.$className.'::fluentSpec();');

			$this->table = $fluentSpec['table'];
			$this->primaryKey = $fluentSpec['primaryKey'];

			if(!empty($fluentSpec['selectWhere']))
				$this->wheres[] = $fluentSpec['selectWhere'];

			if($where!==false)
				$this->wheres[] = $where;
			if($args)
				$this->args = $args;
		}

		function grid() {
			return new SERIA_FluentGrid($this);
		}

		function getFieldSpec()
		{
			return SERIA_Fluent::getFieldSpec($this->className);
//			return eval('return '.$this->className.'::getFieldSpec();');
		}

		function where($where, $args = NULL)
		{
			if($args instanceof SERIA_IFluentObject)
			{
				$args = $args->toDB();
			}

			if(is_array($args))
			{
				foreach($args as $key => $val)
					$this->args[$key] = $val;
			}

			$this->wheres[] = $where;
			return $this;
		}

		function order($order)
		{
			$this->order = $order;
			return $this;
		}

		function limit($limit)
		{
			$this->limit = $limit;
			return $this;
		}

		private function buildSQL()
		{
			$sql = 'SELECT * FROM '.$this->table;
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
			return SERIA_Fluent::createObject($this->className, $this->rs[$this->offset]);
		}

		function key()
		{
			if(($current = $this->current())!==false)
				return $current->getKey();
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
			{ // Optimization: we have prefetched the query, so we do not touch the database again if the object is in the current recordset
				foreach($this->rs as $row)
				{
					if($row[$this->classSpec['primaryKey']] == $offset)
					{
						return SERIA_Fluent::createObject($this->className, $offset);
					}
				}
			}

			$q = clone $this;
			$q->where($this->classSpec['primaryKey'].'=:pk', array('pk' => $offset));
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
