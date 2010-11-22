<?php
	class SDBData extends SData {
		protected $rs, $fields, $table;
		protected $offset;
		public function __construct($table, array $fields = NULL)
		{
			$this->table = $table;
			$this->fields = $fields;
		}
		protected function buildSQL()
		{
			$sql = 'SELECT '.($this->fields ? implode(',', $this->fields) : '*').' FROM '.$this->table;
			if($this->where !== NULL)
				$sql .= ' WHERE '.$this->where;
			if($this->orderBy !== NULL)
				$sql .= ' ORDER BY '.$this->orderBy;
			if($this->start === NULL && $this->length !== NULL)
				$sql .= ' LIMIT '.$this->length;
			else if($this->start !== NULL && $this->length !== NULL)
				$sql .= ' LIMIT '.$this->start.",".$this->length;
			return $sql;
		}
		private function refreshQuery()
		{
			$this->rs = S::db()->query($this->buildSQL(), $this->args)->fetchAll(PDO::FETCH_ASSOC);
			$this->offset = 0;
		}

		// ITERATOR
		// returns the row that is being pointed to at this moment
		function current()
		{
			if($this->rs===NULL)
				$this->refreshQuery();
			if(!isset($this->rs[$this->offset]))
				return false;
			return $this->rs[$this->offset];
		}

		// returns offset in recordset 0, 1, 2 etc
		function key()
		{
			$this->current();
			return $this->offset;
		}

		function next()
		{
			$this->offset++;
			return $this->current();
		}

		function rewind()
		{
			$this->offset = 0;
			return $this->current();
		}

		function valid()
		{
			return $this->current() ? true : false;
		}
	}
