<?php
	/**
	*	Todo:
	*	- LOAD		OK
	*	- SAVE		OK
	*	- VALIDATE	OK
	*	- DELETE	OK
	*	- ALL		OK
	*	- TABLE CREATE
	*/
	class SFluent
	{
		private static $_specCache = array();
		public static function all($className)
		{
			return new SFluentQuery($className);
			// return a fluentquery for the class
		}

		public static function load($className, $key)
		{
			if(is_array($key))
				return new $className($key);

			$spec = self::_getSpec($className);
			$row = SERIA_Base::db()->query('SELECT * FROM '.$spec['table'].' WHERE '.$spec['primaryKey'].'=:key', array('key' => $key))->fetch(PDO::FETCH_ASSOC);
			// REWRITE ROW SO THAT DATE COLUMNS ARE STORED USING UNIX DATETIME
			if($row === false)
				throw new SERIA_Exception('Not found');
			$row = self::_rowFromDB($row, $spec);

			$item = new $className($row);
			return $item;
		}

		public static function save(SFluentObject $instance)
		{
			$className = get_class($instance);
			$spec = self::_getSpec($className);
			$row = self::_rowToDB($originalRow = $instance->FluentBackdoor('get_row'), $spec);

			$errors = self::validate($instance);

			if($errors!==false)
				throw new SERIA_ValidationException('Validation errors', $errors);

			if($originalRow[$spec['primaryKey']] != $row[$spec['primaryKey']])
			{
				$spec['fields'][$spec['primaryKey']] = true; // simple way of allowing insertion of the primary key as well
				$res = SERIA_Base::db()->insert($spec['table'], array_keys($spec['fields']), $row);
				$originalRow[$spec['primaryKey']] = $row[$spec['primaryKey']];
				$instance->FluentBackdoor('set_row', $originalRow);
				return $res;
			}
			else
			{
				return SERIA_Base::db()->update($spec['table'], $spec['primaryKey'], array_keys($spec['fields']), $row);
			}
		}

		public static function validate(SFluentObject $instance)
		{
			$className = get_class($instance);
			$spec = self::_getSpec($className);
			$row = $instance->FluentBackdoor('get_row');
			$errors = array();

			foreach($spec['fields'] as $fieldName => $spec)
			{
				if(isset($spec['validator']))
				{
					if($e = $spec['validator']->isInvalid(!empty($row[$fieldName]) ? $row[$fieldName] : NULL, array('object' => $instance, 'field' => $fieldName)))
						$errors[$fieldName] = $e;
				}
			}

			if(sizeof($errors)>0)
				return $errors;
			else
				return false;
		}

		public static function delete(SFluentObject $instance)
		{
			$row = $instance->FluentBackdoor('get_row');
			$spec = self::_getSpec(get_class($instance));
			if(empty($row[$spec['primaryKey']]))
				throw new SERIA_Exception('This object is not stored in the database, thus it can\'t be deleted.');
			return SERIA_Base::db()->exec('DELETE FROM '.$spec['table'].' WHERE '.$spec['primaryKey'].'=:'.$spec['primaryKey'], $instance);
		}

		public /*package*/ static function _getSpec($item)
		{
			if(is_object($item))
				return self::_getSpec(get_class($item));

			if(isset(self::$_specCache[$item]))
				return self::$_specCache[$item];

			$spec = call_user_func(array($item, 'Fluent'));

			// REWRITE SPEC TO SUPPORT TEMPLATES FOR FIELDS ETC.

			if(!isset($spec['table']))
				throw new SERIA_Exception('Database table not specified using \'table\' in Fluent for '.$item);

			if(!isset($spec['primaryKey']))
				$spec['primaryKey'] = 'id';

			foreach($spec['fields'] as $key => $info)
			{
				if(!is_array($info))
				{ // $spec['fields']['myField'] = 'createdBy';
					switch($info)
					{
						case "createdBy" : case "modifiedBy" :
							$spec['fields'][$key] = self::_getMappedFieldSpec('SERIA_User');
							$spec['fields'][$key]['special'] = $info;
							break;
						case "createdDate" : case "modifiedDate" :
							$spec['fields'][$key] = self::_getMappedFieldSpec('date');
							$spec['fields'][$key]['special'] = $info;
							break;
						default :
							throw new SERIA_Exception("Unknown special type '$info'.");
					}
				}
				else if(isset($info[0]))
				{ // $spec['fields']['myField'] = array('name required', _t('Name:'));
					$tokens = explode(" ", strtolower(trim($info[0])));
					$spec['fields'][$key] = self::_getMappedFieldSpec($tokens[0]);
					unset($tokens[0]);
					foreach($tokens as $token)
					if(isset($token))
					{
						switch($token)
						{
							case 'unique' :
								$spec['fields'][$key]['validator']->addRule(array(SERIA_Validator::FLUENT_UNIQUE));
								break;
							case 'required' :
								$spec['fields'][$key]['validator']->addRule(array(SERIA_Validator::REQUIRED));
								break;
							default :
								throw new SERIA_Exception("Unknown token in field spec '".$token."'");
								break;
						}
					}

					if(!isset($info[1]))
						throw new SERIA_Exception("Caption not specified in field spec for '$key' in class '$className'. Expected array('<type> [required]','<caption>').");
					$spec['fields'][$key]['caption'] = $info[1];
				}
				else
				{
					$spec['fields'][$key] = $info;
				}
			}


			return self::$_specCache[$item] = $spec;
		}

		private static function _getMappedFieldSpec($specName)
		{
			switch(strtolower($specName))
			{
				case "primarykey" : 
					return array(
						"fieldtype" => "hidden",
						"type" => "integer unsigned",
					);
				case "name" : 
					return array(
						"fieldtype" => "text",
						"type" => "varchar(100)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::MAX_LENGTH, 100),
							array(SERIA_Validator::MIN_LENGTH, 1),
						)),
					);
				case "date" :
				case "birthdate" : // birthdate may be validated in the future as earlier than today
					return array(
						"fieldtype" => "text",
						"type" => "datetime",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::LOCAL_DATE),
						)),
					);
				case "currency" : 
					return array(
						"fieldtype" => "text",
						"type" => "double",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::CURRENCY))),
					);
				case "country" : 
					return array(
						"fieldtype" => "select",
						"type" => "varchar(2)",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::COUNTRYCODE))),
					);
				case "address" : 
					return array(
						"fieldtype" => "textarea",
						"type" => "text",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::MAX_LENGTH, 1000))),
					);
				case "email" : 
					return array(
						"fieldtype" => "text",
						"type" => "varchar(100)",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::EMAIL))),
					);
				case "phone" : 
					return array(
						"fieldtype" => "text",
						"type" => "varchar(20)",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::PHONE))),
					);
				case "url" : 
					return array(
						"fieldtype" => "text",
						"type" => "varchar(150)",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::URL))),
					);
				case "gender" :
					return array(
						"fieldtype" => "select",
						"values" => array('m' => _t('Male'), 'f' => _t('Female')),
						"type" => "char(1)",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::ONE_OF, array('m','f')))),
					);
				default : 
					if(class_exists($specName))
					{
						return array(
							"fieldtype" => $specName,
							"type" => "int",
							"validator" => new SERIA_Validator(array(array(SERIA_Validator::FLUENT_OBJECT, $specName))),
						);
					}
					throw new SERIA_Exception('Unknown field type "'.$specName.'".');
			}	
		}

		/**
		 *	Will return an associative array where date columns are converted to ISO dates,
		 *	and all fields not defined in spec are removed and a ID is added to new rows.
		 */
		protected static function _rowToDB($row, $spec)
		{
			$id = empty($row[$spec['primaryKey']]) ? SERIA_Base::guid() : $row[$spec['primaryKey']];
			$newRow = array();
			$ts = time();
			foreach($spec['fields'] as $name => $info)
			{
				if(!empty($info['special']))
				{
					switch($info['special'])
					{
						case 'createdBy' :
							if(empty($row[$spec['primaryKey']]))
							{ // this is a new row
								$newRow[$name] = ($t=SERIA_Base::userId()) ? $t : NULL;
							}
							break;
						case 'modifiedBy' :
							$newRow[$name] = ($t=SERIA_Base::userId()) ? $t : NULL;
							break;
						case 'createdDate' :
							if(empty($row[$spec['primaryKey']]))
							{ // new row
								$newRow[$name] = date('Y-m-d H:i:s', $ts);
							}
							break;
						case 'modifiedDate' :
							$newRow[$name] = date('Y-m-d H:i:s', $ts);
							break;
					}
				}
				else if(isset($info['type']) && isset($row[$name]))
				{
					$tokens = SERIA_DB::sqlTokenize($info['type']);
					switch(strtolower($tokens[0]))
					{
						case 'date' : case 'datetime' : case 'year' :
							$newRow[$name] = date('Y-m-d H:i:s', intval($row[$name]));
							break;
						default :
							$newRow[$name] = $row[$name];
					}
					
				}
				else if(isset($row[$name]))
				{ // this is not good, since we do not have database model information in the FluentSpec.
					$newRow[$name] = $row[$name];
				}
			}
			if(empty($row[$spec['primaryKey']]))
				$newRow[$spec['primaryKey']] = $id;
			return $newRow;
		}

		/**
		*	Will return an associative array where date columns are converted to unix timestamps,
		*	and all fields not defined in spec are removed.
		*/
		static function _rowFromDB($row, $spec)
		{
			$newRow = array();
			foreach($row as $name => $val)
			{
				if(isset($spec['fields'][$name]) && isset($spec['fields'][$name]['type']))
				{
					$tokens = SERIA_DB::sqlTokenize($spec['fields'][$name]['type']);
					switch(strtolower($tokens[0]))
					{
						case 'date' : case 'datetime' : case 'year' :
							$newRow[$name] = strtotime($row[$name]);
							break;
						default :
							switch($spec['fields'][$name]['fieldtype'])
							{
								case 'text' : case 'hidden' : case 'select' : case 'textarea' :
									$newRow[$name] = $row[$name];
									break;
								default :
									if(class_exists($spec['fields'][$name]['fieldtype']))
									{
										$newRow[$name] = $row[$name];
									}
									else
									{
										throw new SERIA_Exception('Unknown field type "'.$spec['fields'][$name]['fieldtype'].'".');
									}
									break;
							}
							break;
					}
				}
				else 
				{
					if($name != $spec['primaryKey'])
						echo "Removing: ".$name."\n";
					else
						$newRow[$name] = $row[$name];
				}
			}
			return $newRow;
		}

		public /*package*/ function _syncColumnSpec($spec)
		{
			$schema = array();
			if(!isset($spec['fields'][$spec['primaryKey']]))
				$spec['fields'] = array_merge(array($spec['primaryKey'] => self::_getMappedFieldSpec('primaryKey')), $spec['fields']);

			foreach($spec['fields'] as $columnName => $info)
			{
				if(empty($info['type']))
					throw new SERIA_Exception('Unable to sync, type not defined for "'.$columnName.'" ('.$spec['table'].')');
				$schema[] = "$columnName ".$info['type'];
			}

			try
			{
				$old = SERIA_Base::db()->getColumnSpec($spec['table']);
			}
			catch (PDOException $e)
			{ // table does not exist
				SERIA_Base::db()->exec('CREATE TABLE '.$spec['table'].' ('.implode(',', $schema).', PRIMARY KEY('.$spec['primaryKey'].')) ENGINE InnoDB DEFAULT CHARSET utf8');
				return true;
			}

			$sql = "CREATE TEMPORARY TABLE syncColumnSpec (".implode(',', $schema).", PRIMARY KEY(".$spec['primaryKey']."))";
			try {
				SERIA_Base::db()->exec($sql); // if this succeeds, then the schema is valid and we also have the correct 

				$newC = $new = SERIA_Base::db()->getColumnSpec('syncColumnSpec');
				SERIA_Base::db()->exec('DROP TABLE syncColumnSpec');
				$oldC = $old = SERIA_Base::db()->getColumnSpec($spec['table']);
				$addedColumns = $removedColumns = $alteredColumns = array();		// relies on arrays being copied when assigned
				// find changed columns
				foreach($old as $columnName => $columnSpec)
				{
					if(!isset($new[$columnName]))
					{
						$removedColumns[] = $columnName;
					}
					else
					{
						foreach($columnSpec as $field => $val)
						{
							if($new[$columnName][$field] !== $val)
							{
								$alteredColumns[] = $columnName;
								break;
							}
						}
						unset($oldC[$columnName]);
					}
				}
				foreach($new as $columnName => $columnSpec)
					if(!isset($old[$columnName]))
						$addedColumns[] = $columnName;
var_dump($addedColumns);
var_dump($removedColumns);
var_dump($alteredColumns);
				if(sizeof($addedColumns)>0 && sizeof($removedColumns)>0)
					throw new SERIA_Exception('Schema for table "'.$spec['table'].'" has changed too much. It is not possible for me to know if you have renamed or added and deleted tables. Please update the database manually.');

				// add new columns
				foreach($addedColumns as $columnName)
					SERIA_Base::db()->exec('ALTER TABLE '.$spec['table'].' ADD COLUMN '.$columnName.' '.$spec['fields'][$columnName]['type']);

				// drop deleted columns
				foreach($removedColumns as $columnName)
					SERIA_Base::db()->exec('ALTER TABLE '.$spec['table'].' DROP COLUMN '.$columnName);

				// modify existing columns
				foreach($alteredColumns as $columnName)
					SERIA_Base::db()->exec('ALTER TABLE '.$spec['table'].' MODIFY COLUMN '.$columnName.' '.$spec['fields'][$columnName]['type']."\n");
			}
			catch (PDOException $e)
			{
				throw new SERIA_Exception('Illegal SQL syntax; database server returned "'.$e->getMessage().'"');
			}
		}

		public function getColumnSpec($table)
		{
			// MySQL specific

			try {
				$desc = $this->query('DESC '.$table)->fetchAll(PDO::FETCH_ASSOC);
			} catch (PDOException $e) {
				throw new SERIA_Exception('Database table "'.$table.'" not found.');
			}
			$result = array();
			foreach($desc as $column)
			{
				$row = array();

				$row['name'] = $column['Field'];

				$t = strpos($column['Type'],'(');;
				if($t === false)
				{
					$row['type'] = $column['Type'];
				}
				else
				{
					$row['type'] = substr($column['Type'], 0, $t);
					$t = substr($column['Type'], $t+1, strpos($column['Type'], ')')-($t+1));
					$t = explode(",", $t);
					$row['length'] = intval($t[0]);
					if(isset($t[1]))
						$row['decimals'] = $t[1];
				}

				$row['null'] = $column['Null'] == 'YES';

				$row['default'] = $column['Default'];

				$row['primary_key'] = $column['Key'] == 'PRI';
				$result[$row['name']] = $row;
			}
			return $result;
		}		
	}
