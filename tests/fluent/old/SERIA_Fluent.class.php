<?php
	class SERIA_Fluent
	{
		// keeps "static" versions of classes, to work around the lack of
		// late static binding in PHP :(
		private static $_statics = array();
		private static $_specCache = array();
		public static function getSpec($item)
		{
			if(is_object($item))
			{
				return self::_rewriteSpec($item->Fluent($item));
			}
			else
			{ 
				// we cache the general spec
				if(isset(self::$_specCache[$item]))
					return self::$_specCache[$item];

				// we maintain a special instance of each fluent class for querying
				if(!isset(self::$_statics[$item]))
					self::$_statics[$item] = new $item();

				return self::$_specCache[$item] = self::_rewriteSpec(self::$_statics[$item]->Fluent());
			}
		}

		/**
		*	Fetches data from a FluentObject that are ready to be inserted
		*	into a database. This mostly apply to boolean and date fields.
		*	
		*/
		public static function getDBRow(SERIA_FluentObject $object)
		{
			$row = $object->FluentBackdoor('get_row');
echo "OK:"; var_dump($row);
			return self::_rowToDB($row, self::getSpec($object));
		}

		/**
		*	Sets data in a FluentObject that are ready to be used by the PHP
		*	application. For example, a boolean field is converted to true or false,
		*	dates are converted to unix timestamps.
		*/
		public static function setDBRow(SERIA_FluentObject $object, array $newRow)
		{
			$newRow = self::_rowFromDB($newRow, self::getSpec($object));
			$object->FluentBackdoor('set_row', $newRow);
		}

		protected static function _rewriteSpec($fluentSpec)
		{
			$spec = $fluentSpec['fields'];

			$newSpec = array();
			foreach($spec as $key => $info)
			{
				if(!is_array($info))
				{
					switch($info)
					{
						case "createdBy" : case "modifiedBy" :
						case "createdDate" : case "modifiedDate" :
							$spec['fields'][$key] = $info;
							break;
						default :
							throw new SERIA_Exception("Unknown special type '$info'.");
					}
				}
				else if(isset($info[0]))
				{
					$tokens = explode(" ", strtolower(trim($info[0])));
					$newSpec[$key] = self::_getMappedFieldSpec($tokens[0]);
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

			$fluentSpec['fields'] = $newSpec;
			return $fluentSpec;
		}

		/**
		 *	Will return an associative array where date columns are converted to ISO dates,
		 *	and all fields not defined in spec are removed.
		 */
		protected static function _rowToDB($row, $spec)
		{
			$fieldSpec = $spec['fields'];
			$newRow = array();
var_dump($row);

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
		static function _rowFromDB($row, $spec)
		{
			$fieldSpec = $spec['fields'];
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

		static function _getMappedFieldSpec($specName)
		{
			switch($specName)
			{
				case "name" : 
					return array(
						"fieldtype" => "text",
						"type" => "varchar(100)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::MAX_LENGTH, 100),
							array(SERIA_Validator::MIN_LENGTH, 1),
						)),
					);
				case "birthdate" :
					return array(
						"fieldtype" => "text",
						"type" => "datetime",
						"validator" => 

SJEKKER VALIDATOREN unix timestamp eller dato? Jeg antar unix timestamp...
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
				default : 
					throw new SERIA_Exception('Unknown field type "'.$specName.'".');
			}	
		}


		static function loadClass($path)
		{
			SERIA_FluentCompiler::loadClass($path);
		}


	}
