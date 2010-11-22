<?php
	abstract class SERIA_ActiveRecord extends SERIA_Model {
		const NULL = 'SERIA_ActiveRecord_NULLVALUE'; 
		
		public static $isActiveRecord = true;
		
		protected $belongsTo;
		protected $hasMany;
		protected $hasOne;
		
		protected $tableName = '';
		
		public $primaryKey = 'id';
		
		public $relationTable;
		
		private $objectCache = array();
		
		protected $useGuid = false;
		protected $guidKey = '';
		
		protected $usePrefix = true;
		protected $useActiveRecord = true;
		
		private $inDatabase = false;
		
		public static $lastSqlQuery;
		
		private static $columnNameCache;
		
		private $columnNameKeyList;
		
		private static $tableToClass = array();
		private static $relationDelimiter = '____';
		
		private $classNameCache = array();
		private $aliasNameCache = array();
		private $relationCache = array();
		
		public final function __construct($data = array()) {
			$this->lockField($this->primaryKey);
			
			static $relationTableCache = null;
			if (!$relationTableCache) {
				$relationTableCache = array();
			}
			
			if (isset($relationTableCache[$cacheKey = get_class($this)])) {
				$this->relationTable = $relationTableCache[$cacheKey = get_class($this)];
			} else {
				$this->relationTable = array(
					1 => $this->belongsTo,
					2 => $this->hasMany,
					3 => $this->hasOne
				);
	
				foreach ($this->relationTable as $id => $table) {
					if (!is_array($table)) {
						$this->relationTable[$id] = array();
					}
				}
			}
			parent::__construct($data);
			
			$this->updateColumnNameKeyList();
		}
		
		private function updateColumnNameKeyList() {
			$customColumns = array();
			if (is_array($this->customColumns)) {
				$customColumns = $this->customColumns;
			}
			$this->columnNameKeyList = array_merge(array_flip($this->columnNames), array_flip($customColumns));
		}
		
		public static function startTransaction() {
throw new SERIA_Exception("Nested transactions not supported. Global transactions are used.");
			try {
				self::commitTransaction();
			} catch (Exception $e) {}
			
//FRODE:			SERIA_Base::db()->beginTransaction();
		}
		public static function commitTransaction() {
throw new SERIA_Exception("Nested transactions not supported. Global transactions are used.");
//FRODE:			SERIA_Base::db()->commit();
		}
		public static function rollbackTransaction() {
throw new SERIA_Exception("Nested transactions not supported. Global transactions are used.");
//FRODE:			SERIA_Base::db()->rollback();
		}
		
		public function getTableName() {
			$tableName = '';
			if ($this->usePrefix) {
				$tableName .= SERIA_PREFIX;
			}
			$tableName .= $this->tableName;
			
			return $tableName;
		}
		
		public function getClassNameFromAlias($alias) {
			$cache =& $this->classNameCache;
			
			if (isset($cache[$alias])) {
				return $cache[$alias];
			}
			
			foreach ($this->relationTable as $table) {
				if (is_array($table)) {
					foreach ($table as $name => $table) {
						list($className, $aliasName) = explode(':', $name);
						if (strtolower($aliasName) == strtolower($alias)) {
							$cache[$alias] = $className;
							return $className;
						}
					}
				}
			}
		}
		
		public function getAliasFromClassName($class) {
			$cache =& $this->aliasNameCache;
			
			if (isset($cache[$class])) {
				return $cache[$class];
			}
			
			foreach ($this->relationTable as $table) {
				foreach ($table as $name => $table) {
					list($className, $aliasName) = explode(':', $name);
					if (strtolower($className) == strtolower($class)) {
						$cache[$class] = $aliasName;
						return $aliasName;
					}
				}
			}
		}
		
		public function __call($name, $arguments) {
			return $this->_callStatic($name, $arguments);
		}
		
		public function _callStatic($name, $arguments) {
			$nameParts = explode('_', $name);
			
			switch (array_shift($nameParts)) {
				case 'find':
					// find searches table on column values
					
					$criterias = array();
					$one = false;
					
					// True to return first elemenet, false if last element
					// Only used if $one is true
					$first = true;
					
					if (sizeof($nameParts) == 0) {
					$one = true;
						if (is_numeric($arguments[0])) {
							// Find one by Id: find(int id)
							$criterias = array($this->primaryKey => (int) array_shift($arguments));
						}
						// If not arguments is numeric, find first entry: find()
					} else {
						switch (array_shift($nameParts)) {
							case 'all':
								$criterias = array();
								$one = false;
								break;
							case 'first':
								$first = true;
								$one = true;
								break;
							case 'last':
								$first = false;
								$one = true;
								break;
							default:
								throw new SERIA_Exception('Unknown second find argument');
								break;
						}
						
						if (sizeof($nameParts) == 0) {
							$criterias = array();
						} else {
							switch (array_shift($nameParts)) {
								case 'by':
									// Following name part is search field
									if (sizeof($nameParts) == 0) {
										throw new SERIA_Exception('Missing search field for find method');
									}
									
									// Remaining arguments is search field
									$searchColumn = implode('_', $nameParts);
									$criterias[$searchColumn] = array_shift($arguments);
									break;
								default:
									throw new SERIA_Exception('Uknown third find argument');
									break;
							}
						}
					}
					
					$limit = 0;
					$offset = 0;
					$order = array();
					$orderSql = null;
					$include = array();
					$preparedStatementParams = array();
					
					if (property_exists('self', 'tableName') && strlen(self::$tableName)) {
						// Following code will only apply if PHP >= 5.3
						$tableName = self::getTableName();
					} else {
						$tableName = $this->getTableName();
					}
					
					if (is_array($arguments[0])) {
						$arguments2 = array_shift($arguments);
						$arguments = array();
						foreach ($arguments2 as $key => $value) {
							$arguments[] = $key;
							$arguments[] = $value;
						}
					}
					
					while (sizeof($arguments) > 0) {
						$arg = array_shift($arguments);
						switch ($arg) {
							case 'criterias':
								if (!is_array($inputCriterias = array_shift($arguments))) {
									throw new SERIA_Exception('Criterias is not an array');
								}
								
								foreach ($inputCriterias as $key => $value) {
									// If key does not contain a table reference (table.column),
									// add this models table as table reference
									if (sizeof(explode('.', $key)) == 1) {
										$key = $this->getTableName() . '.' . $key;
									}
									
									$criterias[$key] = $value;
								}
								break;
							case 'order':
								$orderValue = array_shift($arguments);
								if (is_string($orderValue)) {
									$orderSql = $orderValue;
								} elseif (is_array($orderValue)) {
									foreach ($orderValue as $orderKey => $orderMethod) {
										if (!$orderMethod) {
											$orderMethod = 'ASC';
										}
										
										$orderMethod = strtoupper($orderMethod);
										
										if (!in_array($orderMethod, array('DESC', 'ASC'))) {
											throw new SERIA_Exception('Unknown order direction');
										}
										
										if (strlen($orderSql)) {
											$orderSql .= ', ';
										}
										
										$orderSql .= $this->quoteColumnName($orderKey) . ' ' . $orderMethod;
									}
								}
								break;
							case 'limit':
								if (!is_numeric($limit = array_shift($arguments))) {
									throw new SERIA_Exception('Limit value is not numeric');
								}
								break;
							case 'offset':
								if (!is_numeric($offset = array_shift($arguments))) {
									throw new SERIA_Exception('Offset value is not numeric');
								}
								break;
							// Include is used when more than one table/model related to each other is being fetched from the database
							case 'include':
								if (!is_array($include = array_shift($arguments))) {
									throw new SERIA_Exception('Include is not an array');
								}
								
								break;
							case 'criteriasSql':
								if (!is_string($whereSql = array_shift($arguments))) {
									throw new SERIA_Exception('criteriasSql is not a string');
								}
								break;
							case 'params': /* prepared statement params */
								if (!is_array($preparedStatementParams = array_shift($arguments))) {
									throw new SERIA_Exception('Extra prepared statmement params is not an array');
								}
								break;
	 					}
					}
					
					if (!$tableName) {
						throw new SERIA_Exception('Cannot create object with unknown name');
					}
					
					$objectName = get_class($this);
					$object = new $objectName();
					
					if (!isset($whereSql)) {
						$whereSql = '';
					}
					
					$params = array('criterias' => $criterias, 'orderSql' => $orderSql, 'limit' => $limit, 'offset' => $offset, 'include' => $include, 'tableName' => $tableName, 'criteriasSql' => $whereSql, 'preparedStatementParams' => $preparedStatementParams);
					$rows = $object->findAll($params);
					
					if ($one) {
						if ($first) {
							foreach ($rows as $row) {
								return $row;
							}
						}
						foreach ($rows as $row) {
							$lastRow = $row;
						}
						if (isset($lastRow)) {
							return $lastRow;
						}
						return null;
					}
					return $rows;
					break;
			default:
					$this->event('call', $arguments);
					break;
			}
		}
		
		public function createLeftJoins($includes, $parentAlias = '') {
			$tableName = $this->getTableName();
			$joins = array();
			foreach ($includes as $includeAliasName) {
				if (sizeof(explode('.', $includeAliasName)) == 1) {
					foreach ($this->relationTable as $relationType => $relationTable) {
						if (($relationType == 1) || ($relationType == 3)) {
							foreach ($relationTable as $arg1 => $arg2) {
								$relationGlueTable = $tableName;
								if (is_numeric($arg1)) {
									list($relationClassName, $relationAliasName) = explode(':', $arg2);
								} else {
									list($relationClassName, $relationAliasName) = explode(':', $arg1);
									$relationTableColumn = $arg2;
									if (strpos($relationTableColumn, '.') > 0) {
										list($relationGlueTableAlias, $relationTableColumn) = explode('.', $relationTableColumn, 2);
										$glueClassName = $this->getClassNameFromAlias($relationGlueTableAlias);
										$glueObject = new $glueClassName();
										$relationGlueTable = $glueObject->getTableName();
									}
								}
								
								if ($relationClassName && ($relationClassName == $this->getClassNameFromAlias($includeAliasName))) {
			
									$relationObject = new $relationClassName();
									$relationTableName = $relationObject->getTableName();
			
									$tableToClass[$relationTableName] = $relationClassName;
			
									$relationObject = new $relationClassName();
									$relationTableName = $relationObject->getTableName();
			
									self::$tableToClass[$relationTableName] = $relationClassName;
									
									if (strlen($parentAlias)) {
										$parentAlias .= self::$relationDelimiter;
									}
									$alias = $parentAlias . $relationTableName;
									
									if ($relationType == 1) {
										$joins[] = array($relationClassName,
										                 $this->quoteColumnName($relationGlueTable) . '.' . $this->quoteColumnName($relationTableColumn),
										                 $this->quoteColumnName($alias) . '.' . $relationObject->primaryKey,
										                 $alias);
									} elseif ($relationType == 3) {
										$joins[] = array($relationClassName,
										                 $this->quoteColumnName($relationGlueTable) . '.' . $relationObject->primaryKey,
										                 $this->quoteColumnName($alias) . '.' . $this->quoteColumnName($relationTableColumn),
										                 $alias);
									}
									
									$objectIncludes = array();
									
									foreach ($includes as $include) {
										if (sizeof($parts = explode('.', $include, 2)) == 2) {
											list($aliasName, $childName) = $parts;
											if ($aliasName == $relationAliasName) {
												$objectIncludes[] = $childName;
											}
										}
									}
									$joins = array_merge($joins, $relationObject->createLeftJoins($objectIncludes, $alias));
								}
							}
						}
					}
				}
			}
			
			return $joins;
		}
		
		private function findAll($arguments = array()) {
			$resultSet = new SERIA_ActiveRecordSet();
			$resultSet->setObject($this);
			$resultSet->setParams($arguments);
			return $resultSet;
		}

		public function getColumns() {
			if (!isset($this->columnNames) || (sizeof($this->columnNames) == 0)) {
				if ($this->useActiveRecord) {
					if ($cache =& self::$columnNameCache[get_class($this)]) {
						$this->columnNames = $cache;
						return $this->columnNames;
					}
					
					$queryString = 'SHOW COLUMNS FROM ' . $this->quoteColumnName($tableName = $this->getTableName()) . '';
					$columnQuery = $this->sqlQuery($queryString);
					if (!$rows = $columnQuery->fetchAll(PDO::FETCH_ASSOC)) {
						throw new SERIA_Exception('No columns found in table ' . $tableName);
					}
					
					foreach ($rows as $column) {
						$this->columnNames[] = array_shift($column);
					}
					$cache = $this->columnNames;
				} else {
					$this->columnNames = $this->setColumns();
				}
			}
			
			$this->updateColumnNameKeyList();
			return $this->columnNames;
		}
	
		public function isRelatedTo($className) {
			$searchTables = $this->relationTable;
	
			$className = strtolower($className);
			$result = null;
			foreach ($searchTables as $id => $searchTable) {
				if (is_array($searchTable)) {
					foreach ($searchTable as $arg1 => $arg2) {
						$foundClassName = '';
						if (is_numeric($arg1)) {
							$searchClassName = $arg2;
						} else {
							$searchClassName = $arg1;
						}
	
						list($searchClassName, $alias) = explode(':', $searchClassName);
	
						if ($id == 2) {
							$alias = $this->getSingular($alias);
						}
	
						$alias = strtolower($alias);
						$searchClassName = strtolower($searchClassName);
	
						if (($searchClassName == $className) || ($alias == $className)) {
							$foundClassName = $className;
						}
	
						if ($foundClassName) {
							$result = $id;
							break 2;
						}
					}
				}
			}
	
			if ($result !== null) {
	 			if (is_array($this->hasMany)) {
					foreach ($this->hasMany as $arg1 => $arg2) {
						if (is_numeric($arg1)) {
							if ($arg2 == $tableName) {
								$result = 2;
								break;
							}
						} else {
							if ($arg1 == $tableName) {
								$result = 2;
								break;
							}
						}
					}
				}
			}
	
			if ($result !== null) {
				if (is_array($this->hasOne)) {
					foreach ($this->hasOne as $arg1 => $arg2) {
						if (is_numeric($arg1)) {
							if ($arg2 == $tableName) {
								$result = 3;
								break;
							}
						} else {
							if ($arg1 == $tableName) {
								$result = 3;
								break;
							}
						}
					}
				}
			}
			
			if (!$result) {
				$result = 0;
			}
			
			$cache[$cacheKey] = $result;
			return $result;
		}
		
		public function addColumnToObjectCache($name, $column, $value) {
			$delimiter = self::$relationDelimiter;
			
			if (strlen($name) == 0) {
				$this->columnNames[] = $column;
				$this->columnValues[$column] = $value;
			} elseif (($count = sizeof($nameParts = explode($delimiter, $name, 2))) >= 1) {
				$firstTableName = $nameParts[0];
				$secondTableName = $nameParts[1];
				
				if ($firstTableName != $this->getTableName()) {
					$className = self::$tableToClass[$firstTableName];
					if (!$className) {
						throw new Exception('Class for table (' . $firstTableName . ') not found');
					}
					
					if (!$this->objectCache[$className][0]) {
						$this->objectCache[$className][0] = new $className();
					}
					$object = $this->objectCache[$className][0];
				} else {
					$object = $this;
				}
				
				$object->addColumnToObjectCache($secondTableName, $column, $value);
			}
		}
	
		public function addToObjectCache($alias, $object, $addReverse=true) {
			if (!is_string($alias)) {
				throw new SERIA_Exception('Alias name must be type of string');
			}
			if (is_object($object) && $object->{$object->primaryKey}) {
				$objectFoundInCache = false;
				foreach ($this->objectCache as $objectsInCache) {
					foreach ($objectsInCache as $objectInCache) {
						if (get_class($objectInCache) == get_class($object)) {
							if ($objectInCache->{$objectInCache->primaryKey} == $object->{$object->primaryKey}) {
								$objectFoundInCache = true;
							}
						}
					}
				}
				if (!$objectFoundInCache) {
					$this->objectCache[$alias][] = $object;
				}
				
				if ($addReverse) {
					// Need a way to handle corrent alias. This is not possible.
//					$object->addToObjectCache($this, false);
				}
			}
		}
			
		public function __set($var, $value) {
			if ($var == $this->primaryKey) {
				if ($this->inDatabase) {
					throw new SERIA_Exception('Cannot change ID field for record in database');
				}
			}
			parent::__set($var, $value);
		}
			
		public function __get($var) {
			if (isset($this->columnNameKeyList[$var])) {
				return parent::__get($var);
			}
			$relationExists = false;
			
			$cache =& $this->relationCache;
			if (isset($cache[$var])) {
				list($relation, $singular) = $cache[$var];
			} else {
				$singular = false;
				if (!($relation = $this->isRelatedTo($var))) {
					if (!($relation = $this->isRelatedTo($this->getSingular($var)))) {
						throw new SERIA_Exception('Unknown column/relation: ' . $var);
					} else {
						$singular = true;
					}
				}
			}
			
			$cache[$var] = array($relation, $singular);
			
			return $this->getRelatedObjects($var, !$singular);
		}
		
		public function getSingular($plural) {
			return SERIA_ActiveRecordInterfaceHandler::getSingular($plural);
		}
		
		public function getPlural($singular) {
			return SERIA_ActiveRecordInterfaceHandler::getPlural($singular);
		}
	
		protected function getRelationColumnName($aliasToFind) {
			foreach ($this->relationTable as $relationType => $table) {
				if (!is_array($table)) {
					$table = array();
				}
				foreach ($table as $arg1 => $arg2) {
					$column = '';
					if (is_numeric($arg1)) {
						$data = $arg2;
					} else {
						$data = $arg1;
						$column = $arg2;
					}
	
					list($class, $alias) = explode(':', $data);
					
					if ($alias == $aliasToFind) {
						if (!$column) {
							$object = new $className();
							switch ($relationType) {
								case 1:
									// Belongs
									return $object->getTableName() . '_id';
									break;
								case 2:
									// Many
									return $this->getTableName() . '_id';
									break;
								case 3:
									// One
									return $this->getTableName() . '_id';
									break;
							}
							return false;
						}
						return $column;
					}
				}
			}
		}
		
		protected function getRelatedObjects($alias, $singular = true) {
			if (!class_exists($className = $this->getClassNameFromAlias($alias))) {
				$className = $this->getClassNameFromAlias($this->getSingular($alias));
			}
	
			if (!$className) {
				return false;
			}
	
			if (!($objects = $this->objectCache[$alias])) {
				$methodName = 'find_all';
				
				$relationColumnName = $this->getRelationColumnName($alias);
				if ($relationColumnName) {
					
					$criterias = array();
					$relationObject = new $className();
					if (!$relation = $this->isRelatedTo($alias)) {
						$relation = $this->isRelatedTo($this->getSingular($alias));
					}
					switch ($relation) {
						case 1:
							// Belongs to
							$criterias = array($relationObject->primaryKey => $this->$relationColumnName);
							break;
						case 2:
						case 3:
							// Has many
							// Has one
							$criterias = array($relationColumnName => $this->{$this->primaryKey});
							break;
						default:
							throw new SERIA_Exception('No relation found');
							break;
					}
					$callback = array($this->getPlural($className), $methodName);
					$objects = call_user_func($callback, 'criterias', $criterias);
					$objects = $objects->toArray();
					
					if ($objects) {
						foreach ($objects as $object) {
							$this->addToObjectCache($alias, $object);
						}
					}
				} else {
					throw new SERIA_Exception('Unknown relation alias: ' . $alias);
				}
			}
			
			if ($singular) {
				unset($object);
				foreach ($objects as $objectIterator) {
					$object = $objectIterator;
				}
				$objects = $object;
			}
	
			return $objects;
		}
			
		public function quoteColumnName($value) {
			$value = '`' . $value . '`';
			return $value;
		}
		
		public function quoteValue($value) {
			return SERIA_Base::db()->quote($value);
		}

		private static function setLastSqlQuery($query) {
			self::$lastSqlQuery = $query;
		}
		
		protected function sqlQuery($query, $params = array()) {
			if (!is_array($params)) {
				throw new SERIA_Exception('Params for Sql Query is not an array');
			}
			
			if (SERIA_DEBUG) {
				call_user_func(array(get_class($this), 'setLastSqlQuery'), $query);
			}
			
			if (sizeof($params)) {
				$result = SERIA_Base::db()->query($query, $params);
			} else {
				$result = SERIA_Base::db()->query($query);
			}
			return $result;
		}
		
		public function checkValidationBeforeSave() {
			if ($this->isValid()) {
				return true;
			}
		}
		
		private function update() {
			$columns = '';
			$sqlParams = array();
			
			foreach ($this->columnValues as $key => $value) {
				if (!$this->inDatabase || !in_array($key, $this->lockedFields)) {
					if (isset($this->columnNameKeyList[$key]) && in_array($key, $this->columnNames)) {
						if (strlen($columns) > 0) {
							$columns .= ', ';
						}
						
						if ($value === null) {
							$value = 'NULL';
						} else {
							do {
								$valueKey = 'key' . rand(0,1000);
							} while (isset($sqlParams[$valueKey]));
							
							$sqlParams[$valueKey] = $value;
							$value = $valueKey;
						}
						
						$columns .= $this->quoteColumnName($key) . ' = :' . $value . ' ';
					}
				}
			}
				
			$query = 'UPDATE ' . $this->quoteColumnName($this->getTableName()) . ' SET ' . $columns . ' WHERE ' . $this->quoteColumnName($this->primaryKey) . ' = ' . $this->{$this->primaryKey};
			if ($this->sqlQuery($query, $sqlParams)) {
				$this->inDatabase = true;
				$this->runEvent('afterUpdate');
				$this->runEvent('afterSave');
				return true;
			}
		}
		
		private function insert($replace = false) {
			$columns = self::getColumns();
			
			if (!$replace) {
				$id = 0;
				if ($this->useGuid) {
					$id = SERIA_Base::guid($this->guidKey);
				}
			} else {
				$id = $this->id;
			}
			
			$columnNames = array();
			foreach ($this->columnNames as $columnName) {
				$columnNames[] = $this->quoteColumnName($columnName);
			}
			
			$columnValues = array();
			$sqlParams = array();
			foreach ($this->columnValues as $columnName => $columnValue) {
				foreach ($columnNames as $colid => $columnName2) {
					if ($columnName2 == $this->quoteColumnName($columnName)) {
						$foundPrimary = false;
						
						if ($columnName == $this->primaryKey) {
							if ($id) {
								$columnValues[$colid] = $this->quoteValue($id);
								$foundPrimary = true;
							}
						}
						if (!$foundPrimary) {
							if ($columnValue === null) {
								$columnValue = 'NULL';
							} else {
								do {
									$valueKey = 'key' . rand(0,1000);
								} while (isset($sqlParams[$valueKey]));
								
								$sqlParams[$valueKey] = $columnValue;
								$columnValue = ':' . $valueKey;
							}
							$columnValues[$colid] = $columnValue;
						}
					}
				}
			}
			
			ksort($columnNames);
			ksort($columnValues);
			
			if ($replace) {
				// Warning: This is a MySQL only command.
				$query = 'REPLACE INTO ';	
			} else {
				$query = 'INSERT INTO ';
			}

			$query .= $this->quoteColumnName($this->getTableName()) . ' (' . implode(', ', $columnNames) . ') VALUES(' . implode(', ', $columnValues) . ')';
			$result = $this->sqlQuery($query, $sqlParams);
			if (!$this->useGuid) {
				$id = SERIA_Base::db()->lastInsertId();
			}
			$this->{$this->primaryKey} = $id;
			
			if ($id) {
				$this->inDatabase = true;
				$this->runEvent('afterCreate');
				$this->runEvent('afterSave');
				return true;
			}
		}

		public function save() {
			if (!$this) {
				throw new SERIA_Exception('save() cannot be called static');
			}
			
			$this->runEvent('beforeSave');
			
			if (!$this->checkValidationBeforeSave()) {
				return false;
			}
			
			if (!$this->{$this->primaryKey}) {
				return $this->insert();
			}
			
			if (is_numeric($this->{$this->primaryKey})) {
				return $this->update();
			}
			
			return false;
		}
		
		public function replace() {
			if (!$this) {
				throw new SERIA_Exception('save() cannot be called static');
			}
			
			$this->runEvent('beforeSave');

			if (!$this->checkValidationBeforeSave()) {
				return false;
			}
			
			return $this->insert(true);
		}
			
		public function delete() {
			if (is_numeric($this->{$this->primaryKey})) {
				$this->runEvent('beforeDelete');
				$query = 'DELETE FROM ' . $this->quoteColumnName($this->getTableName()) . ' WHERE ' . $this->quoteColumnName($this->primaryKey) . '=' . ':primarykey';
				if ($this->sqlQuery($query, array('primarykey' => $this->{$this->primaryKey}))) {
					$this->inDatabase = false;
					$this->{$this->primaryKey} = 0;
					$this->runEvent('afterDelete');
					return true;
				}
			}
				
			return false;
		}
		
 	}
?>
