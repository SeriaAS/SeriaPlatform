<?php
	class SERIA_Fluent
	{
		static $cache = array();

		static function createObject($className, $p=false)
		{
			if($p===false)
				return new $className();

			if(!isset(self::$cache[$className]))
				self::$cache[$className] = array();

			if(is_array($p))
			{
				$pk = call_user_func(array($className, 'fluentSpec'));
				$pk = $pk['primaryKey'];
				if(isset($p[$pk]))
				{ // find in cache
					if (is_numeric($p[$pk]) && intval($p[$pk]) == trim($p[$pk])) {
						/*
						 * Normally the primary key is an integer
						 */
						if(isset(self::$cache[$className][intval($p[$pk])]))
							return self::$cache[$className][intval($p[$pk])];
						return self::$cache[$className][intval($p[$pk])] = new $className($p);
					} else {
						/*
						 * If the primary key type differs from integer we have to use string keys in the array.
						 */
						$spec = self::getFieldSpec($className);
//						$spec = call_user_func(array($className, 'getFi_REMOVED_eldSpec'));
						if (!isset($spec[$pk]) || !isset($spec[$pk]['fieldtype']) || $spec[$pk]['fieldtype'] == 'integer')
							throw new SERIA_Exception('Supplied primary key is not an integer. (Invalid argument)');
						if (isset(self::$cache[$className][$p[$pk]]))
							return self::$cache[$className][$p[$pk]];
						return self::$cache[$className][$p[$pk]] = new $className($p);
					}
				}
				else
				{
					throw new SERIA_Exception('Passing an array that does not contain the primary key to the constructor of "'.$className.'" is not allowed.');
				}

				
			}
			else if(is_numeric($p) && intval($p) == $p)
			{
				if(!isset(self::$cache[$className][intval($p)]))
					self::$cache[$className][intval($p)] = new $className($p);
				return self::$cache[$className][intval($p)];
			}
			else
			{
				/*
				 * Check first if the primary key is not an integer
				 */
				$pk = call_user_func(array($className, 'fluentSpec'));
				$pk = $pk['primaryKey'];
				$spec = self::getFieldSpec($className);
//				$spec = call_user_func(array($className, 'getFie_REMOVED_ldSpec'));
				/*
				 * Fail if fieldspec says it is an integer (default if unspec):
				 */
				if (!isset($spec[$pk]) || !isset($spec[$pk]['fieldtype']) || $spec[$pk]['fieldtype'] == 'integer')
					throw new SERIA_Exception('Invalid argument "'.$p.'" passed to constructor of "'.$className.'".');
				if (isset(self::$cache[$className][$p]))
					return self::$cache[$className][$p];
				return self::$cache[$className][$p] = new $className($p);
			}
		}

		static function delete(SERIA_FluentObject $o)
		{
			if($o->isDeletable())
			{
				$className = get_class($o);
				$key = $o->getKey();
				eval('$fls = '.$className.'::fluentSpec();');
				$sql = 'DELETE FROM '.$fls['table'].' WHERE '.$fls['primaryKey'].'=:key';
				if(SERIA_Base::db()->exec($sql, array('key' => $key)))
				{
					if(isset(self::$cache[$className]) && isset(self::$cache[$className][$key]))
						unset(self::$cache[$className][$key]);
					return true;
				}
			}
			return false;
		}

		static function load($className, $key)
		{
			return self::createObject($className, $key);
		}

		static function save(SERIA_FluentObject $object)
		{
			$className = get_class($object);

			$fis = self::getFieldSpec($className);
			eval('
				$fls = '.$className.'::fluentSpec();
//				$fis = '.$className.'::getFiel__REMOVED__dSpec();
				$row = $object->toDB();
				$key = $object->getKey();
			');

			if(!$object->validate())
			{
				throw new SERIA_ValidationException('Object does not validate', $object->errors);
			}

			// updating an existing?
			$db = SERIA_Base::db();

			$row = self::_prepareRowForDB($row, $fis);
			foreach($fis as $fk => $fv)
			{
				if(!is_array($fv)) switch($fv)
				{
					case 'modifiedBy' :
						$userId = SERIA_Base::userId();
						if($userId === false)
							$row[$fk] = NULL;
						else
							$row[$fk] = $userId;
						break;
					case 'modifiedDate' :
						$row[$fk] = date("Y-m-d H:i:s");
						break;
					case 'createdBy' :
						if(!$key)
						{
							$userId = SERIA_Base::userId();
							if($userId === false)
								$row[$fk] = NULL;
							else
								$row[$fk] = $userId;
						}
						break;
					case "createdDate" :
						if(!$key)
						{
							$row[$fk] = date('Y-m-d H:i:s');
						}
						break;
					default :
						throw new SERIA_Exception('Unknown fluent template type "'.$fv.'".');
				}
			}

			if($key)
			{ // we should update the database

				$row[$fls['primaryKey']] = $key; // reinject primary key, required for SERIA_DB::update()

				if(!$db->update($fls['table'], array($fls['primaryKey'] => $key), array_keys($fis), $row))
					return false;
			}
			else
			{ // we should insert into the database
				$key = SERIA_Base::guid();
				$row[$fls['primaryKey']] = $key; // adding primary key fetched from guid()

				$fis[$fls['primaryKey']] = true; // must allow inserting the primary key
				$res = $db->insert($fls['table'], array_keys($fis), $row);

				if($res)
				{
					$object->_setKey($key);
				}
				else
					return false;
			}

			if(!isset(self::$cache[$className]))
				self::$cache[$className] = array();

			self::$cache[$className][$key] = $object;

			return $res;
		}

		static function all($className)
		{
			return new SERIA_FluentQuery($className);
		}

		static function getFluent($className)
		{
			return call_user_func(array($className, 'Fluent'));
		}

		/**
		*	Requesting fieldSpec from a class should go trough this function, since
		*	this function translates shortcut names to fully fledget fluent specs.
		*
		*	Old syntax is supported, but shortcuts such as:
		*	"customerName" => array("name required", _t("Customer name"))
		*	makes things much simpler to write.
		*
		*	Also allowed is 
		*	"userId" => array("SERIA_User required", _t("User"))
		*	which will map the column to the primary key of SERIA_User.
		*/
		static function getFieldSpec($className)
		{
			$spec = call_user_func(array($className, 'getFieldSpec'), true);
			$newSpec = array();
			foreach($spec as $key => $info)
			{
				if(!is_array($info))
				{
					switch($info)
					{
						case "createdBy" : case "modifiedBy" :
						case "createdDate" : case "modifiedDate" :
							$newSpec[$key] = $info;
							break;
						default :
							throw new SERIA_Exception("Unknown special type '$info'.");
					}
				}
				else if(isset($info[0]))
				{
					$tokens = explode(" ", strtolower(trim($info[0])));
					$newSpec[$key] = self::getMappedFieldSpec($tokens[0]);
					if(isset($tokens[1]))
					{
						switch($tokens[1])
						{
							case 'required' :
								$newSpec[$key]['validator']->addRule(array(SERIA_Validator::REQUIRED));
								break;
							default :
								throw new SERIA_Exception("Unknown token in field spec '".$tokens[1]."'");
								break;
						}
					}

					if(!isset($info[1]))
						throw new SERIA_Exception("Caption not specified in field spec for '$key' in class '$className'. Expected array('<type> [required]','<caption>').");
					$newSpec[$key]['caption'] = $info[1];
				}
				else
				{
					$newSpec[$key] = $info;
				}
			}
			return $newSpec;
		}

		static function getMappedFieldSpec($specName)
		{
			$specs = array(
				"name" => array(
					"fieldtype" => "text",
					"type" => "varchar(100)",
					"validator" => new SERIA_Validator(array(
						array(SERIA_Validator::MAX_LENGTH, 100),
						array(SERIA_Validator::MIN_LENGTH, 1),
					)),
				),
				"currency" => array(
					"fieldtype" => "text",
					"type" => "double",
					"validator" => new SERIA_Validator(array(array(SERIA_Validator::CURRENCY))),
				),
				"country" => array(
					"fieldtype" => "select",
					"type" => "varchar(2)",
					"validator" => new SERIA_Validator(array(array(SERIA_Validator::COUNTRYCODE))),
				),
				"address" => array(
					"fieldtype" => "textarea",
					"type" => "text",
					"validator" => new SERIA_Validator(array(array(SERIA_Validator::MAX_LENGTH, 1000))),
				),
				"email" => array(
					"fieldtype" => "text",
					"type" => "varchar(100)",
					"validator" => new SERIA_Validator(array(array(SERIA_Validator::EMAIL))),
				),
				"phone" => array(
					"fieldtype" => "text",
					"type" => "varchar(20)",
					"validator" => new SERIA_Validator(array(array(SERIA_Validator::PHONE))),
				),
				"url" => array(
					"fieldtype" => "text",
					"type" => "varchar(150)",
					"validator" => new SERIA_Validator(array(array(SERIA_Validator::URL))),
				),
		
			);
			if(!isset($specs[$specName]))
				throw new SERIA_Exception('Unknown field type "'.$specName.'".');

			return $specs[$specName];
		}

		/**
		* Function that takes a unix timestamp and prepares it for insertion into a database according to its column definition
		*/
		static function _formatDateForDB($time, $columnDef)
		{
			$tokens = SERIA_DB::sqlTokenize($columnDef);
			switch(strtolower($tokens[0])) // the name of the column in the database (varchar/date/datetime etc)
			{
				case 'varchar' : case 'varchar2' : case 'datetime' : case 'date' : case 'timestamp' : case 'year' :
					return date('Y-m-d H:i:s', $time);
					break;
				case 'int' : case 'bigint' : case 'double' : case 'real' : case 'float' : case 'decimal' : case 'numeric' :
					return $time;
					break;
			}
		}

		/**
		*	Will return an associative array where date columns are converted to ISO dates,
		*	and all fields not defined in spec are removed.
		*/
		static function _prepareRowForDB($row, $fieldSpec)
		{
			$newRow = array();

			foreach($row as $name => $val)
			{
				if(isset($fieldSpec[$name]))
				{
					if(isset($fieldSpec[$name]['type']))
					{
						$tokens = SERIA_DB::sqlTokenize($fieldSpec[$name]['type']);
						switch(strtolower($tokens[0]))
						{
							case 'date' : case 'datetime' : case 'year' :
								$newRow[$name] = date('Y-m-d H:i:s', intval($row[$name]));
								break;
							default :
								$newRow[$name] = $row[$name];
						}
						
					}
					else
						$newRow[$name] = $row[$name];
				}
				
			}
			return $newRow;
		}

		/**
		*	Will return an associative array where date columns are converted to unix timestamps,
		*	and all fields not defined in spec are removed.
		*/
		static function _prepareRowForObject($row, $fieldSpec)
		{
			$newRow = array();
			foreach($row as $name => $val)
			{
				if(isset($fieldSpec[$name]) && isset($fieldSpec[$name]['type']))
				{
					$tokens = SERIA_DB::sqlTokenize($fieldSpec[$name]['type']);
					switch(strtolower($tokens[0]))
					{
						case 'date' : case 'datetime' : case 'year' :
							$newRow[$name] = strtotime($row[$name]);
							break;
						default :
							$newRow[$name] = $row[$name];
					}
				}
				else
					$newRow[$name] = $row[$name];
			}
			return $newRow;
		}

		static function loadClass($path)
		{
			SERIA_FluentCompiler::loadClass($path);
		}
	}
