<?php
	class SERIA_ActiveRecordSet implements ArrayAccess,Iterator {
		private $object;
		private $set = null;
		private $params = array();
		private $iteratorPosition = 0;
		private static $tableToClass = array();
		
		public function __get($var) {
			switch ($var) {
				case 'count':
					if ($this->set === null) {
						$this->runQuery();
					}
					return sizeof($this->set);
					break;
				default:
					throw new SERIA_Exception('Unknown property on Active Record Set: ' . $var);
					break;
			}
		}
		
		public function offsetSet($offset, $value) {
			throw new SERIA_Exception('Set not supported on Active Record Result Set');
		}
		public function offsetGet($offset) {
			if ($this->set === null) {
				$this->runQuery();
			}
			
			return $this->set[$offset];
		}
		public function offsetExists($offset) {
			return isset($this->set[$offset]);
		}
		public function offsetUnset($offset) {
			throw new SERIA_Exception('unset() not supported on Active Record Result Set');
		}
		
		public function current() {
			return $this->set[$this->position];
		}
		
		public function key () {
			return $this->position;
		}
		
		public function next () {
			++$this->position;
		}
		
		public function rewind () {
			if ($this->set === null) {
				$this->runQuery();
			}
			$this->position = 0;
		}
		
		public function valid () {
			return isset($this->set[$this->position]);
		}
		
		public function toArray() {
			if (!is_array($this->set)) {
				$this->runQuery();
			}
			return $this->set;
		}
		
		/**
		 * Returns an array containing primaryKey => object
		 *
		 */
		public function toKeyArray() {
			$set = array();
			foreach ($this->toArray() as $object) {
				$set[$object->{$object->primaryKey}] = $object; 
			}
			
			return $set;
		}
		
		public function __construct() {
		}
		public function setObject($object) {
			if (!is_object($object)) {
				throw new SERIA_Exception('Object is not an object');
			}
			
			$this->object = $object;
			
			return $this;
		}
		public function setParams($params) {
			$this->params = $params;
		}
		
		public function runQuery() {
			$object = $this->object;
			$tableName = $object->getTableName();
			
			$leftJoin = array();
			
			$tableToClass = array();
			
			$lateObjectsQuery = array();
			
			$arguments = $this->params;
			
			// Construct a list of root level includes, and ignore child includes as they are
			// passed to lateObject queries
			$includes = array();
			foreach ($arguments['include'] as $include) {
				if (sizeof(explode('.', $include)) == 1) {
					$includes[] = $include;
				}
			}
			
			$leftJoin = $object->createLeftJoins($arguments['include']);
			
			foreach ($includes as $includeAliasName) {
				$includeClassName = $object->getClassNameFromAlias($includeAliasName);
				$includeObject = new $includeClassName();
				$includeTableName = $includeObject->getTableName();
				
				foreach ($object->relationTable as $relationType => $relationTable) {
					if (is_array($relationTable)) {
						foreach ($relationTable as $arg1 => $arg2) {
							$relationGlueTable = $tableName;
							if (is_numeric($arg1)) {
								list($relationClassName, $relationAliasName) = explode(':', $arg2);
							} else {
								list($relationClassName, $relationAliasName) = explode(':', $arg1);
								$relationTableColumn = $arg2;
								if (strpos($relationTableColumn, '.') > 0) {
									list($relationGlueTableAlias, $relationTableColumn) = explode('.', $relationTableColumn, 2);
									$glueClassName = $object->getClassNameFromAlias($relationGlueTableAlias);
									$glueObject = new $glueClassName();
									$relationGlueTable = $glueObject->getTableName();
								}
							}
	
							$relationObject = new $relationClassName();
							$relationTableName = $relationObject->getTableName();
	
							$tableToClass[$relationTableName] = $relationClassName;
	
							$relationObject = new $relationClassName();
							$relationTableName = $relationObject->getTableName();
	
							$tableToClass[$relationTableName] = $relationClassName;
	
							// !!???
							if (strtolower($relationTableName) == strtolower($includeTableName)) {
								switch ($relationType) {
									case 1:
										// Belongs to
										
										break 2;
									case 2:
										// Has many
										if ($relationGlueTable != $tableName) {
											throw new SERIA_Exception('Relation not supported');
										}
										$lateObjectsQuery[] = array($relationAliasName, $relationClassName, $relationTableColumn);
										break 2;
									case 3:
										// Has one
										if ($relationGlueTable != $tableName) {
											throw new SERIA_Exception('Relation not supported');
										}
										
 										break 2;
										$leftJoin[] = array($relationClassName,
										                    $object->quoteColumnName($relationGlueTable) . '.' . $object->primaryKey,
										                    $object->quoteColumnName($relationTableName) . '.' . $object->quoteColumnName($relationTableColumn),
										                    $relationTableName);
 										break 2;
								}
							}
						}
					}
				}
			}
			
			$mainTableColumns = $object->getColumns();
		
			$columns = array();
			foreach ($mainTableColumns as $column) {
				$columns[] = $object->quoteColumnName($tableName) . '.' . $object->quoteColumnName($column) . ' as ' . $object->quoteValue($tableName . '.' . $column);
			}
			
			foreach ($leftJoin as $join) {
				list($joinTableName, $joinLocalColumnName, $joinForeignColumnName, $alias) = $join;
	
				$object->columnValues[$joinTableName] = $joinObject = new $joinTableName();
				$joinColumns = $joinObject->getColumns();
	
				foreach ($joinColumns as $joinColumn) {
					$columns[] = ($joinTableName = $alias) . '.' . $object->quoteColumnName($joinColumn)  . ' as ' . $object->quoteValue($joinTableName . '.' . $joinColumn);
				}
			}
			
			
			$joinStrings = array();
			// Add left joins
			foreach ($leftJoin as $join) {
				list($joinClassName, $joinLocalColumnName, $joinForeignColumnName, $joinTableAlias) = $join;
				
				$joinObject = new $joinClassName();
				$joinTableName = $joinObject->getTableName();
	
				$joinString = ' LEFT JOIN ' . $joinTableName . ' AS ' . $joinTableAlias . ' ON ' . $joinLocalColumnName . ' = ' . $joinForeignColumnName;
				$joinStrings[] = $joinString;
			}
				
			// Construct Sql Query
			$query = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $object->quoteColumnName($tableName) . implode(' ', $joinStrings) . ' ';
	
			$where = '';
			$sqlParams = array();
			if (sizeof($criterias = $arguments['criterias'])) {
				$where = '(1 ';
				foreach ($criterias as $key => $value) {
					$where .= ' AND ';
					
					// Support all of tableName.column, tableAlias.column and just column
					$tableAlias = '';
					$columnName = '';
					$nameData = explode('.', $key, 2);
					if (sizeof($nameData)) {
						$tableAlias = $nameData[0];
						if (sizeof($nameData) > 1) {
							$columnName = $nameData[1];
						}
					} 
					if ($columnName == '') {
						// If no column name supplied, use this objects table name as table name
						$tableName = $object->getTableName();
						$columnName = $key;
					} else {
						$tmpObjectName = $object->getClassNameFromAlias($tableAlias);
						if ($tmpObjectName) {
							// If there is a class name, use it to get table name
							$tmpObject = new $tmpObjectName();
							$tableName = $tmpObject->getTableName();
						} else {
							// If not, asume table name from foreign string
							$tableName = $tableAlias;
						}
					}
					
					$quotedColumnName = $object->quoteColumnName($tableName) . '.' . $object->quoteColumnName($columnName);
					
					if (is_array($value)) {
						$set = array();
						foreach ($value as $key => $valueIterator) {
							$set[] = $object->quoteValue($valueIterator);
						}
						if (sizeof($set) > 0) {
							$where .= $quotedColumnName . ' IN (' . implode(', ', $set) . ')';
						} else {
							$where .= '0';
						}
					} elseif ($value === SERIA_ActiveRecord::NULL) {
						$where .= $quotedColumnName . ' IS NULL ';
					} else {
						
						do {
							$valueKey = 'key' . rand(0,1000);
						} while (isset($sqlParams[$valueKey]));
						
						$sqlParams[$valueKey] = $value;
						$where .= $quotedColumnName . ' = ' . ':' . $valueKey;
					}
				}
				$where .= ')';
	
				$query .= 'WHERE ' . $where;
			}
	
			if ($whereSql = $arguments['criteriasSql']) {
				if (!strlen($where)) {
					$query .= ' WHERE 1 ';
				}
				$query .= ' AND ' . $whereSql;
			}
			
			if ($orderSql = $arguments['orderSql']) {
				$query .= ' ORDER BY ' . $orderSql;
			}
			
			if ($limit = (int) $arguments['limit']) {
				$query .= ' LIMIT '.intval($limit);
			}
			if ($offset = (int) $arguments['offset']) {
				$sqlParams['offset'] = $offset;
				$query .= ' OFFSET :offset';
			}

			if ($arguments['preparedStatementParams'])
				$sqlParams = array_merge($sqlParams, $arguments['preparedStatementParams']);
			
			self::$tableToClass = array_merge(self::$tableToClass, $tableToClass);
			$db = SERIA_Base::db();
			$queryResult = $db->query($query, $sqlParams);
			
			$queryRows = $queryResult->fetchAll();
			$objects = array();
			foreach ($queryRows as $row) {
				$className = get_class($object);
				$newobject = new $className();
				foreach ($row as $column => $value) {
					if (!is_numeric($column)) {
						list($table, $column) = explode('.', $column, 2);
						if ($table == $object->getTableName()) {
							$newobject->columnValues[$column] = $value;
						} else {
							$newobject->addColumnToObjectCache($table, $column, $value);
						}
					}
					$newobject->inDatabase = true;
				}
				$objects[] = $newobject;
			}
			
			// lateObjectsQuery contains list of additional queries
			foreach ($lateObjectsQuery as $query) {
				list($alias, $className, $foreignColumn) = $query;
				
				$foreignObject = new $className();
				
				$ids = array();
				foreach ($objects as $iobject) {
					if ($iobject->{$iobject->primaryKey} && is_numeric($iobject->{$iobject->primaryKey})) {
						$ids[] = $iobject->{$iobject->primaryKey};
					}
				}
				
				$ids = implode(', ', $ids);
				if (strlen($ids)) {
					$include = array();
					
					// Find all includes for this query, and pass them as include argument to query
					$includes = array();
					foreach ($arguments['include'] as $include) {
						if (sizeof(explode('.', $include)) > 1) {
							list($rootAlias, $childAlias) = explode('.', $include, 2);
							if ($rootAlias == $alias) {
								$includes[] = $childAlias;
							}
						}
					}
					$criteriasSql = $object->quoteColumnName($foreignObject->getTableName()) . '.' . $foreignColumn . ' IN (' . $ids . ')';
					$lateObjects = call_user_func(array($object->getPlural($className), 'find_all'), 'criteriasSql', $criteriasSql, 'include', $includes);
					
					foreach ($lateObjects as $lateObject) {
						foreach ($objects as $object_id => $iobject) {
							if ($iobject->{$object->primaryKey} == $lateObject->$foreignColumn) {
								$objects[$object_id]->addToObjectCache($alias, $lateObject);
							}
						}
					}
				}
			}
			foreach ($objects as &$iobject) {
				$iobject->runEvent('afterGet');
			}
			
			$this->set = $objects;
		}
	}
?>