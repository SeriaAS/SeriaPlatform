<?php
	class SERIA_DbData extends SERIA_Data {
		/*
		 * Limit the max number of rows that can be retrieved in one single query.
		 */
		const QUERY_ROW_LIMIT = 5000;

		protected $fields, $table, $primaryKey, $shardBy, $shardByValues=array();
		protected $rs = NULL;
		protected $rsOffset = NULL; /* The base index of the rs table */
		protected $offset = 0;

		public function __construct($table, $primaryKey, $shardBy=NULL, $select='*')
		{
			if(empty($primaryKey)) throw new Exception('Primary key is required param number two');
			$this->table = $table;
			$this->primaryKey = $primaryKey;
			$this->shardBy = $shardBy;
			$this->select = $select;
		}
		public static function table($table, $primaryKey, $shardBy=NULL, $select='*')
		{
			return new SERIA_DbData($table, $primaryKey, $shardBy, $select);
		}

		/**
		*	Get the cache object instance
		*/
		protected $_cache;
		protected function _cache() {
			if(!$this->_cache) {
				$this->_cache = new SERIA_Cache('SDbData_'.$this->table);
			}
			return $this->_cache;
		}

		/**
		*	Get a row from cache
		*/
		protected function _cacheGet($id) {
			$res = $this->_cache()->get($this->_cache->get('generation').'_'.$id);
			return $res;
		}

		/**
		*	Set a row to cache
		*/
		protected function _cacheSet($id, $row) {
//SERIA_Base::db()->dbLog("_cacheSet(".$this->table.", $id)");
			return $this->_cache()->set($this->_cache->get('generation').'_'.$id, $row, 10);
		}

		/**
		*	Increment cache generation so that all caches are void
		*/
		protected function _cacheClean() {
			SERIA_Base::db()->dbLog("_cacheClean(".$this->table.", $id)");
			$this->_cache()->set('generation', microtime(TRUE), 1800);
		}

		public function count() {
			$sql = $this->buildSQL('COUNT(`'.$this->primaryKey.'`)');
			return intval(SERIA_Base::db()->query($sql, $this->args)->fetch(PDO::FETCH_COLUMN, 0));
		}

		/**
		*	Insert a new row into the database. Choose the correct shard by inspecting $values if sharding is used.
		*	@param array $values 		An associative array of key => value pairs to insert.
		*	@return boolean
		*/
		public function insert(array $values)
		{
			if($this->shardBy && !isset($values[$this->shardBy]))
				throw new SERIA_Exception('Unable to insert this row, since you have not specified the "'.$this->shardBy.'" column - which is used for sharding.');

			$sql = 'INSERT INTO '.$this->table.' (`';
			$fieldNames = array_keys($values);
			$fieldKeys = array();
			$fieldValues = array();
			foreach($values as $key => $value)
			{
				$fieldKeys[] = ':'.$key;
				$fieldValues[] = $value;
			}
			$sql .= implode('`,`', $fieldNames).'`) VALUES (';
			$sql .= implode(',', $fieldKeys).')';

			$this->_cacheClean();
			return SERIA_Base::db()->exec($sql, $values);
		}

		/**
		*	Update the database, moving the row to the correct shard if neccesary.
		*	@param mixed $primaryKey		The value of the primary key
		*	@param array $values			Associative array of key=>value to update the database with
		*	@param string $previousShardByValue	If this is specified, then this usually means that your data may be moved to a different shard. Always set this if you are updating the value of the shard column.
		*/
		public function update($primaryKey, array $values, $previousShardByValue=false)
		{
			if($this->shardBy && !isset($values[$this->shardBy]))
				throw new SERIA_Exception('Unable to insert this row, since you have not specified the "'.$this->shardBy.'" column - which is used for sharding.');

			$sql = 'UPDATE '.$this->table.' SET ';
			$parts = array();
			foreach($values as $key=>$val)
				$parts[] = '`'.$key.'`=:'.$key;
			$sql .= implode(',', $parts);
			$sql .= ' WHERE `'.$this->primaryKey.'`=:sdbdatakey';
			$values[':sdbdatakey'] = $primaryKey;
			$res = SERIA_Base::db()->exec($sql, $values);
			$this->_cacheClean();
			return $res;
		}

		/**
		*	Delete a row from the database by it's primary key.
		*	@param mixed $primaryKey
		*/
		public function delete($primaryKey, $shardByValue=FALSE) {
			if($this->shardBy && $shardByValue===FALSE)
				throw new SERIA_Exception('Unable to delete this row, since you have not specified the "'.$this->shardBy.'" column as parameter two.');
			$sql = 'DELETE FROM '.$this->table.' WHERE `'.$this->primaryKey.'`=:sdbdatakey';
			$res = SERIA_Base::db()->exec($sql, array('sdbdatakey' => $primaryKey));
			$this->_cacheClean();
			return $res;
		}

		/**
		*	
		*/
		public function where($where, $args = NULL, $shardByValue = NULL)
		{
			$this->rs = NULL;
			$this->rsOffset = NULL;
			$this->offset = 0;
			parent::where($where, $args);
			if($shardByValue!==NULL)
				$this->shardByValues[] = $shardByValue;
			return $this;
		}

		protected function buildSQL($select='*')
		{
			$sql = 'SELECT '.$select.' FROM '.$this->table;
			if($this->where !== NULL)
				$sql .= ' WHERE '.$this->where;
			if($this->orderBy !== NULL)
				$sql .= ' ORDER BY '.$this->orderBy;
			if($this->start == 0 && $this->length !== NULL)
			{
				$sql .= ' LIMIT '.$this->length;
			}
			else if($this->start != 0 && $this->length !== NULL)
			{
				$sql .= ' LIMIT '.$this->start.",".$this->length;
			}
			return $sql;
		}
		private function loadData($loadOffset, $select='*')
		{
			if ($this->start == 0 && $this->length === NULL) {
				$this->start = $loadOffset;
				$this->length = self::QUERY_ROW_LIMIT;
				$setLimitToNull = true;
			} else {
				$setLimitToNull = false;
				$loadOffset = 0;
			}
			$sql = $this->buildSQL($select);
			$cacheKey = md5(serialize(array($sql, $this->args)));
			if(!($this->rs = $this->_cacheGet($cacheKey))) { // It is not in cache

				$this->rs = SERIA_Base::db()->query($sql, $this->args)->fetchAll(PDO::FETCH_ASSOC);
				if(!isset($this->rs[5])) { // There are less than 5 rows in this result, so let's cache it!
					$this->_cacheSet($cacheKey, $this->rs);
				}
			}

			$this->rsOffset = $loadOffset;
			if ($setLimitToNull) {
				$this->start = 0;
				$this->length = NULL;
			}
		}

		// ITERATOR
		// returns the row that is being pointed to at this moment
		function current()
		{
			if($this->rs===NULL)
				$this->loadData($this->offset, $this->select);
			else if ($this->start == 0 && $this->length == NULL) {
				if ($this->offset < $this->rsOffset || $this->offset >= ($this->rsOffset + self::QUERY_ROW_LIMIT))
					$this->loadData($this->offset, $this->select);
			}
			if(!isset($this->rs[$this->offset - $this->rsOffset]))
				return false;
			return $this->rs[$this->offset - $this->rsOffset];
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
