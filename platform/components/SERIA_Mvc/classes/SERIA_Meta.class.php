<?php
	/**
	*	Todo:
	*	- LOAD		OK
	*	- SAVE		OK
	*	- VALIDATE	OK
	*	- DELETE	OK
	*	- ALL		OK
	*	- TABLE CREATE	OK
	*
	*	- FULLTEXT		Specify which fields to fulltext index.
	*
	*	- TAGGING		Create a separate MyISAM table that can support tags, and use a global table that maps tags to 4 character words
	*				Searching by tags must be done trough the SERIA_MetaQuery class, via the method ->withTags(array(tagnames)) and ->withoutTags(array(tagnames))
	*				Must specify a sort column for tagging, or can we join?
	*
	*	- RELATIONSHIPS		Support some notion of "friends" internally. See if it is possible to implement friend of a friend style indexing.
	*
	*	- CACHING		See if we can implement instance caching automatically; if we detect loading of instances by primary key using SERIA_Meta::load() often, then 
	*				instances may be cached efficiently. Perhaps different cache backends should be considered differently; memcache is shared, while apc is not.
	*				MySQL based cache tables might not perform better.
	*
	*
	*/
	class SERIA_Meta
	{
		/**
		* Events that are raised on SERIA_MetaObject objects. The method you can override is specified below:
		*/
		const AFTER_SAVE_EVENT = 'after_save';		// MetaAfterSave()
		const BEFORE_SAVE_EVENT = 'before_save';	// MetaBeforeSave() returns boolean false if you wish to deny saving
		const AFTER_DELETE_EVENT = 'after_delete';	// MetaAfterDelete()
		const BEFORE_DELETE_EVENT = 'before_delete';	// MetaBeforeDelete() returns boolean false if you wish to deny deleting
		const AFTER_LOAD_EVENT = 'after_load';		// MetaAfterLoad()
		const AFTER_CREATE_EVENT = 'after_create';	// MetaAfterCreate()

		private static $_specCache = array();
		private static $_mTimes = false;
		private static $_cache = false;
		private static $_primaryKeySpecs = array();

		/**
		*	Outputs a simple help text for the given classname.
		*
		*	@param string $className	Name of the Metaclass you want help for
		*/
		public static function help($className)
		{
			$spec = self::_getSpec($className);

			$maxLength = 0;
			foreach($spec['fields'] as $name => $info)
			{
				$l = strlen($name);
				if($l > $maxLength) $maxLength = $l;
			}

			foreach($spec['fields'] as $name => $info)
			{
				echo str_pad($name, $maxLength + 1);
				echo $info['caption']."          <br>\n";
			}
		}

		/**
		*	Return a SERIA_MetaQuery object which can be used to access the database
		*	and returns SERIA_MetaObjects.
		*	@param $className	Name of a class extending SERIA_MetaObject
		*	@return SERIA_MetaQuery
		*/
		public static function all($className)
		{
			return new SERIA_MetaQuery($className);
			// return a metaquery for the class
		}

		/**
		*	Load an instance of a class providing the primary key. selectWhere is automatically added,
		*	so you will be unable to load a class unless you have access to it trough the selectWhere-clause
		*	in Meta()-spec
		*
		*	@param mixed $shardValue	Only supply this if you are using sharding. If you do not, all shards will be inspected unless sharding by primary key.
		*/
		public static function load($className, $key, $shardValue=NULL)
		{
			if(is_array($key))
				return new $className($key);

			$spec = self::_getSpec($className);
			$rs = SERIA_DbData::table($spec['table'], $spec['primaryKey'], $spec['shardBy'])->where($spec['primaryKey'].'=:key', array(':key' => $key), $shardValue);
			if(isset($spec['selectWhere']))
				$rs->where($spec['selectWhere']);
			if($where = call_user_func(array($className, 'MetaSelect'))) {
				if (is_string($where))
					$rs->where($where);
				else if (is_array($where))
					$rs->where($where[0], $where[1]);
				else
					throw new SERIA_Exception('Invalid return value from MetaSelect');
			}
			$row = $rs->limit(1)->current();
			if($row === false)
				throw new SERIA_Exception($className.':'.$key.' not found', SERIA_Exception::NOT_FOUND);

			$item = new $className($row);
			return $item;
		}

		/**
		*	Save a SERIA_MetaObject-instance to the database.
		*
		*	@param SERIA_MetaObject $instance	A SERIA_MetaObject
		*	@param boolean $saveReferences		Save referenced objects?
		*	@return boolean
		*/
		public static function save(SERIA_MetaObject $instance, $saveReferences=false)
		{
			$className = get_class($instance);
			$spec = self::_getSpec($className);

			if(!$instance->MetaBackdoor('raise_event', self::BEFORE_SAVE_EVENT))
				throw new SERIA_Exception('Unable to save, access denied by MetaBeforeSave()', SERIA_Exception::ACCESS_DENIED);

			$errors = self::validate($instance);


			if($saveReferences && $errors)
			{ // want to save references. Only do that is there is no errors but unsaved META_OBJECTS
				foreach($errors as $k => $v)
				{ // look for OTHER errors than META_OBJECT_INSTANCE
					if($v!==SERIA_Validator::META_OBJECT_INSTANCE)
						throw new SERIA_ValidationException('Validation errors', $errors); // there were other errors than META_OBJECT_INSTANCE, meaning the recursive search did not validate
				}
				$errors = false; // no errors, _rowToDb() will save everything
			}

			$new = $instance->MetaBackdoor('is_new');

			$row = self::_rowToDB($instance->MetaBackdoor('get_update_row'), $spec, $new, $saveReferences);

			if ($new)
			{
				if($errors!==false)
					throw new SERIA_ValidationException('Validation errors', $errors);
				$res = SERIA_DbData::table($spec['table'], $spec['primaryKey'], $spec['shardBy'])->insert($row);
			}
			else
			{
				if ($errors!==false) {
					foreach ($errors as $name => $err) {
						if (!isset($row[$name])) {
							/*
							 * Not changed! Don't fail on validation.
							 */
							unset($errors[$name]);
						}
					}
					if ($errors)
						throw new SERIA_ValidationException('Validation errors', $errors);
				}
				$res = SERIA_DbData::table($spec['table'], $spec['primaryKey'], $spec['shardBy'])->update($row[$spec['primaryKey']], $row);
				/*
				 * Possible return values:
				 * 0: No rows changed. Ok. (local and db match )
				 * 1: One row changed. Ok.
				 * FALSE: Error.
				 *
				 * I believe people expect this function to return TRUE/FALSE based on Ok/Error,
				 * but to make as little change as possible I'll change just 0 to TRUE and
				 * leave 1 (and FALSE) as before.
				 * - J-E P
				 */
				if ($res === 0)
					$res = TRUE;
			}
			$instance->MetaBackdoor('update_row', $row);

			$instance->MetaBackdoor('raise_event', self::AFTER_SAVE_EVENT);

			return $res;
		}

		public static function validateSubset($metaObjectClass, $fields, $values) {
			if(is_object($values)) {
				$vals = array();
				foreach($fields as $fieldName)
					$vals[$fieldName] = $values->$fieldName;
				$values = $vals;
			}
			$o = new $metaObjectClass();
			foreach($fields as $fieldName)
				$o->set($fieldName, !empty($values[$fieldName]) ? $values[$fieldName] : NULL);
			if(!($errors = self::validate($o, TRUE)))
				return FALSE;

			$result = array();
			foreach($fields as $fieldName) {
				if(isset($errors[$fieldName])) $result[$fieldName] = $errors[$fieldName];
			}

			if(sizeof($result)==0)
				return FALSE;
			return $result;
		}


		/**
		 * Get the standard field validator. Be aware of that custom validation may
		 * exist in addition to the standard (MetaIsInvalid).
		 *
		 * @param $instance SERIA_MetaObject|string Instance of SERIA_MetaObject or class name.
		 * @param $field string Field name.
		 * @return SERIA_Validator
		 * @throws SERIA_Exception
		 */
		public static function getFieldValidator($instance, $field)
		{
			$spec = self::_getSpec($instance);
			if (isset($spec['fields']) && isset($spec['fields'][$field]))
				$spec = $spec['fields'][$field];
			else
				throw new SERIA_Exception('Requested field '.$field.' not found!');
			if (isset($spec['validator'])) {
				assert($spec['validator'] instanceof SERIA_Validator);
				return $spec['validator'];
			} else
				throw new SERIA_Exception('No validator for meta field '.$field.'!');
		}

		/**
		 * Validate a single field value.
		 *
		 * @param $instance SERIA_MetaObject|string Instance of SERIA_MetaObject or class name.
		 * @param $field string Field name.
		 * @param $value mixed Value.
		 * @return bool|string FALSE on success, otherwise an error message as a string.
		 * @throws SERIA_Exception
		 */
		public static function validateField($instance, $field, $value)
		{
			/*
			 * Make sure we don't do anything with the original object.
			 * (Need to do a set-call here to run the custom validator)
			 */
			if (is_object($instance))
				$instance = get_class($instance);

			/*
			 * The normal validator will let the custom errors override the
			 * standard validation errors. So we check custom first, possibly
			 * shorting the standard validation.
			 */
			if (!class_exists($instance) || !is_subclass_of($instance, 'SERIA_MetaObject'))
				throw new SERIA_Exception('Cannot request a validator for a field of an object or class that is not a SERIA_MetaObject');
			$instance = new $instance();
			assert($instance instanceof SERIA_MetaObject);
			$instance->set($field, $value);
			$customErrors = $instance->MetaIsInvalid();
			if (isset($customErrors[$field]))
				return $customErrors[$field];

			/*
			 * If we have got no custom errors, try standard validation.
			 */
			$validator = static::getFieldValidator($instance, $field);
			return $validator->isInvalid($value);
		}

		/**
		*	Validate field by field a SERIA_MetaObject, by accessing its row.
		*
		*	@param SERIA_MetaObject $instance	Validate and return array of errors or boolean false.
		*	@param boolean $ignoreUnsavedReferences	Ignore the SERIA_Validator::META_OBJECT_INSTANCE error (i.e. as long as all referenced instances validate, this instance validates). Remember to save the referenced objects!
		*/
		public static function validate(SERIA_MetaObject $instance, $ignoreUnsavedReferences=false)
		{
			$className = get_class($instance);
			$spec = self::_getSpec($className);
			$row = $instance->MetaBackdoor('get_row');
			$errors = array();

			foreach($spec['fields'] as $fieldName => $spec)
			{
				if(isset($spec['validator']))
				{
					$data = array('object' => $instance, 'field' => $fieldName);
					if($e = $spec['validator']->isInvalid((!empty($row[$fieldName]) || $row[$fieldName] === 0 || $row[$fieldName] === '0') ? $row[$fieldName] : NULL, $data))
					{
						if(!($ignoreUnsavedReferences && $e===SERIA_Validator::META_OBJECT_INSTANCE))
							$errors[$fieldName] = $e;
					}
				}
			}

			$customErrors = $instance->MetaIsInvalid();
			if($customErrors)
			{
				foreach($customErrors as $key => $value)
				{
					$errors[$key] = $value;
				}
			}

			if(sizeof($errors)>0)
				return $errors;
			else
				return false;
		}

		/**
		*	Get a short identifier for a MetaObject that can be used to retrieve this MetaObject at a later
		*	time.
		*	@param SERIA_MetaObject $instance	An instance of a SERIA_MetaObject
		*/
		public static function getReference(SERIA_MetaObject $instance)
		{
			return get_class($instance).":".$instance->MetaBackdoor('get_key');
		}

		/**
		*	Get an instance by passing only a string that is generated by SERIA_Meta::getReference()
		*	@param string $identifier		A string identifying a MetaObject
		*	@return SERIA_MetaObject
		*/
		public static function getByReference($identifier)
		{
			$parts = explode(":", $identifier);
			return SERIA_Meta::load($parts[0], $parts[1]);
		}

		/**
		*	Create a named object from a SERIA_MetaObject.
		*	@param SERIA_MetaObject $instance	An instance of a SERIA_MetaObject
		*	@return array
		*/
		public static function getNamedObjectId(SERIA_MetaObject $instance)
		{
			$id = (string) $instance->MetaBackdoor('get_key');
			if(empty($id))
				throw new SERIA_Exception("I have no ID!");
			$className = get_class($instance);
			return array('SERIA_Meta','load',$className,$id);
		}

		/**
		*	Delete a SERIA_MetaObject
		*
		*	@param SERIA_MetaObject $instance	Delete the SERIA_MetaObject
		*/
		public static function delete(SERIA_MetaObject $instance)
		{
			if(!$instance->MetaBackdoor('raise_event', self::BEFORE_DELETE_EVENT))
				return false;

			$row = $instance->MetaBackdoor('get_row');
			$spec = self::_getSpec(get_class($instance));
			if(empty($row[$spec['primaryKey']]))
				throw new SERIA_Exception('This object is not stored in the database, thus it can\'t be deleted.');

                        $className = get_class($instance);
                        $spec = self::_getSpec($className);
			$res = SERIA_DbData::table($spec['table'], $spec['primaryKey'], $spec['shardBy'])->delete($row[$spec['primaryKey']]);
			if($res) {
				$instance->MetaBackdoor('raise_event', self::AFTER_DELETE_EVENT);
			}
			return $res;

			$res = SERIA_Base::db()->exec('DELETE FROM '.$spec['table'].' WHERE `'.$spec['primaryKey'].'`=:'.$spec['primaryKey'], $instance);
			if($res)
			{
				$instance->MetaBackdoor('raise_event', self::AFTER_DELETE_EVENT);
			}
			return $res;
		}

		/**
		*	The Fluent API is deprecated. For smooth transition to Meta API, the Meta API supports Fluent objects.
		*/
		protected static function _getSpecFromFluent($item, $onlyPrimaryKey=FALSE)
		{
//FRODE
			$res = array();
			if(is_object($item))
			{
				$item = get_class($item);
			}

			$fluentSpec = call_user_func(array($item, 'fluentSpec'));

			$res['table'] = $fluentSpec['table'];
			if(isset($fluentSpec['primaryKey'])) 
				$res['primaryKey'] = $fluentSpec['primaryKey'];
			else
				$res['primaryKey'] = 'id';
			$res['fields'] = call_user_func(array($item, 'getFieldSpec'));
			return $res;
		}

		public static function parseFieldSpec($fieldSpec, &$entireSpec=NULL) {
			if(!is_array($fieldSpec)) {	// 'createdBy', 'createdDate' etc
				return self::_parseFieldSpecSpecial($fieldSpec, $entireSpec);
			} else if(isset($fieldSpec[0])) { // array('address required', _t("Address Label"))
				return self::_parseFieldSpecSimple($fieldSpec, $entireSpec);
			} else {
				return self::_parseFieldSpecFull($fieldSpec, $entireSpec);
			}
		}

		protected static function _parseFieldSpecFull($fieldSpec, &$entireSpec=NULL) {
			if(isset($fieldSpec['class']))
			{
				if(in_array('SERIA_IMetaField', class_implements($fieldSpec['class'])))
				{
					if ($fieldSpec['class'] == $item)
						$info = call_user_func(array($fieldSpec['class'], 'MetaField'), $spec);
					else
						$info = call_user_func(array($fieldSpec['class'], 'MetaField'));
					foreach($info as $k => $v)
					{
						$fieldSpec[$k] = $v;
					}
				}
			}
			return $fieldSpec;
		}

		protected static function _parseFieldSpecSimple($fieldSpec, &$entireSpec=NULL) {
			$tokens = explode(" ", trim($fieldSpec[0]));
			$res = self::_getMappedFieldSpec($tokens[0], $fieldSpec);
			unset($tokens[0]);

			if(isset($res['class']))
			{
				if(in_array('SERIA_IMetaField', class_implements($res['class'])))
				{
					if($res['class'] == $item)
						$fieldclass_info = call_user_func(array($res['class'], 'MetaField'), $entireSpec);
					else
						$fieldclass_info = call_user_func(array($res['class'], 'MetaField'));

					/*
					 * We are not loading in the class of the primary key of the target class.
					 * The class is specified by this spec/definition and not the target class.
					 */
					if (isset($fieldclass_info['class']))
						unset($fieldclass_info['class']);
					/* Don't override the caption with the caption of the target class */
					if (isset($res['caption']) && isset($fieldclass_info['caption']))
						unset($fieldclass_info['caption']);

					foreach($fieldclass_info as $k => $v)
					{
						$res[$k] = $v;
					}
				}
			}
			foreach($tokens as $token) if(isset($token))
			{
				switch(strtolower($token))
				{
					case 'unique' :
						$res['validator']->addRule(array(SERIA_Validator::META_UNIQUE));
						break;
					case 'required' :
						$res['validator']->addRule(array(SERIA_Validator::REQUIRED));
						break;
					default :
						if(substr(strtolower($token), 0, 7)==="unique(")
						{
							$name = substr($token, 7, strpos($token, ")")-7);
							if(isset($entireSpec['fields'][$name]))
							{
								$res['validator']->addRule(array(SERIA_Validator::META_UNIQUE, NULL, $name));
								break;
							}
							else
							{
								throw new SERIA_Exception("Unknown column '".$name."' in UNIQUE spec for column '".$tokens[1]."'.");
							}
						} else if(substr(strtolower($token), 0, 7)==="minval(") {
							$val = substr($token, 7, strpos($token, ")")-7);
							$res['validator']->addRule(array(SERIA_VALIDATOR::MIN_VALUE, floatval($val)));
						} else if(substr(strtolower($token), 0, 7)==="maxval(") {
							$val = substr($token, 7, strpos($token, ")")-7);
							$res['validator']->addRule(array(SERIA_VALIDATOR::MAX_VALUE, floatval($val)));
						} else if(substr(strtolower($token), 0, 7)==="minlen(") {
							$val = substr($token, 7, strpos($token, ")")-7);
							$res['validator']->addRule(array(SERIA_VALIDATOR::MIN_LENGTH, intval($val)));
						} else if(substr(strtolower($token), 0, 7)==="maxlen(") {
							$val = substr($token, 7, strpos($token, ")")-7);
							$res['validator']->addRule(array(SERIA_VALIDATOR::MAX_VALUE, intval($val)));
						} else
							throw new SERIA_Exception("Unknown token in field spec '".$token."'");
						break;
				}
			}

			if(!isset($fieldSpec[1]))
				throw new SERIA_Exception("Caption not specified in field spec for '$key' in class '$className'. Expected array('<type> [required]','<caption>').");

			$res['caption'] = $fieldSpec[1];

			if(isset($fieldSpec[2]) && is_array($fieldSpec[2]))
			{
				foreach($fieldSpec[2] as $k => $v)
					$res[$k] = $v;
			}

			return $res;
		}

		protected static function _parseFieldSpecSpecial($fieldSpec, &$entireSpec=NULL) {
			/*
			 * Split info in tokens by any whitespace (regex any-whitespace=\s).
			 */
			$tokens = preg_split("/[\\s]+/", $fieldSpec);
			if (!$tokens)
				throw new SERIA_Exception('No type or spec of field '.$key.' specified!');
			$specialName = array_shift($tokens);
			switch($specialName)
			{
				case "createdBy" : case "modifiedBy" :
					$res = self::_getMappedFieldSpec('SERIA_User');
					$res['caption'] = ($specialName==='createdBy' ? _t("Registered by") : _t('Modified by'));
					$res['special'] = $specialName;
					return $res;
				case "createdDate" : case "modifiedDate" :
					$res = self::_getMappedFieldSpec('datetime');
					$res['caption'] = ($specialName==='createdDate' ? _t("Created date") : _t('Last modified date'));
					$res['special'] = $specialName;
					if ($tokens) {
						foreach ($tokens as $token) {
							if ($token == 'sortable')
								$res['sortable'] = true;
						}
					}
					return $res;
				case "isEnabled" :
					$res = self::_getMappedFieldSpec('boolean');
					$res['caption'] = _t("Enabled");
					$res['special'] = $specialName;
					return $res;
				case "parent":
					// try to identify the primary key spec here, without causing a loop
					$res = array(
						"type" => self::$_primaryKeySpecs[$item],
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::META_OBJECT, $item))),
						"class" => $item,
						"caption" => _t("Parent"),
						"special" => $specialName,
					);
					if ($tokens) {
						foreach ($tokens as $token) {
							if($token=='required')
							{
								$res['validator']->addRule(array(SERIA_Validator::REQUIRED));
							}
						}
					}
					return $res;
				default :
					throw new SERIA_Exception("Unknown special type '$fieldSpec'.");
			}
			throw new Exception("Unable to parse special field '$fieldSpec'");
		}

		/**
		*	Method for internal usage within SERIA_Meta. Get field specification for any SERIA_MetaObject
		*
		*	@param var $item	Classname or instance of a SERIA_MetaObject
		*/
		public /*package*/ static function _getSpec($item, $onlyPrimaryKey=FALSE)
		{
			if(is_object($item))
				return self::_getSpec(get_class($item), $onlyPrimaryKey);

			if(!class_exists($item))
			{
				throw new SERIA_Exception('No such SERIA_MetaObject class "'.$item.'"');
			}

			if(!$onlyPrimaryKey && isset(self::$_specCache[$item]))
			{
				return self::$_specCache[$item];
			}

			if(!is_subclass_of($item, 'SERIA_MetaObject'))
			{
				// Support _getSpec for deprecated fluent objects

				if(is_subclass_of($item, 'SERIA_FluentObject') || in_array('SERIA_IFluentObject', class_implements($item)))
				{
					return self::_getSpecFromFluent($item, $onlyPrimaryKey);
				}

				throw new SERIA_Exception('Class "'.$item.'" must extend SERIA_MetaObject');
			}

			$spec = call_user_func(array($item, 'Meta'));

			if($onlyPrimaryKey) { // Prevents loops by not inspecting other fields in the target type
				$primaryKeyName = isset($spec['primaryKey']) ? $spec['primaryKey'] : 'id';
				$caption = isset($spec['caption']) ? $spec['caption'] : "No caption specified for '$item'";
				if(isset($spec['fields'][$primaryKeyName])) {
					$spec = $spec['fields'][$primaryKeyName];
				} else {
					$spec = 'primarykey';
				}
				if (!is_array($spec))
					$spec = self::parseFieldSpec(array($spec, $caption));
				else
					$spec = self::parseFieldSpec($spec);
				self::$_primaryKeySpecs[$item] = $spec['type'];
				return $spec;
			}

			// REWRITE SPEC TO SUPPORT TEMPLATES FOR FIELDS ETC.

			if(!isset($spec['table']))
			{
				throw new SERIA_Exception('Database table not specified using \'table\' in Meta for '.$item);
			}

			if(!isset($spec['primaryKey']))
				$spec['primaryKey'] = 'id';

			if(!isset($spec['shardBy']))
				$spec['shardBy'] = NULL;

			if(!isset($spec['fields'][$spec['primaryKey']]))
			{
				$spec['fields'] = array_merge(array($spec['primaryKey'] => self::_getMappedFieldSpec('primaryKey')), $spec['fields']);
			}

			// need these to avoid recursive loop for self referencing specs (parentId references same class)
			self::$_primaryKeySpecs[$item] = $spec['fields'][$spec['primaryKey']]['type'];
			foreach($spec['fields'] as $key => $info)
			{
				if(!is_array($info))
				{ // $spec['fields']['myField'] = 'createdBy';
					$spec['fields'][$key] = self::parseFieldSpec($info, $spec);

				}
				else if(isset($info[0]))
				{ // $spec['fields']['myField'] = array('name required', _t('Name:'));
					$spec['fields'][$key] = self::parseFieldSpec($info, $spec);
				}
				else
				{
					$spec['fields'][$key] = self::parseFieldSpec($info, $spec);
				}
			}

			$cache = new SERIA_Cache('SERIA_Meta:classmtimes');
			$cTime = $cache->get($item);
			$mTime = $GLOBALS['seria']['classmtimes'][$item];

			if($cTime===NULL || $cTime != $mTime)
			{
				self::_syncColumnSpec($spec);
				$cache->set($item, $mTime);
			}
			return self::$_specCache[$item] = $spec;
		}

		public static $_isMapping = 0;
		/**
		*	Method for internal usage within SERIA_Meta. Returns an array of metaspec,
		*	which simplifies writing metaspecs... :-)
		*
		*	@param string $specName		The name of the spec, for example "name" or "number".
		*/
		private static function _getMappedFieldSpec($specName, $info=NULL)
		{
			switch(strtolower($specName))
			{
				case "primarykey" : 
					return array(
						"fieldtype" => "hidden",
						"type" => "integer unsigned",
						"validator" => new SERIA_Validator(array()),
					);
				case "seed" : // A seed that can be used in encryption - for example to perform one way encryption of a password
					return array(
						"fieldtype" => "text",
						"type" => "varchar(50)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::MAX_LENGTH, 50),
							array(SERIA_Validator::MIN_LENGTH, 1),
						)),
					);
				case "uid" : // Store any ID
					return array(
						"fieldtype" => "text",
						"type" => "varchar(100)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::MAX_LENGTH, 100),
							array(SERIA_Validator::MIN_LENGTH, 1),
						)),
					);
				case "title" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(100)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::MAX_LENGTH, 100),
							array(SERIA_Validator::MIN_LENGTH, 1),
						)),
					);
				case "safestring" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(100)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::MAX_LENGTH, 100),
							array(SERIA_Validator::LEGAL_CHARS, str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_')),
						)),
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
				case "string" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(250)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::MAX_LENGTH, 250),
							array(SERIA_Validator::MIN_LENGTH, 1),
						)),
					);
				case "slug" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(100)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::SLUG),
							array(SERIA_Validator::MAX_LENGTH, 100),
						)),
					);
				case "filepath" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(200)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::FILEPATH),
							array(SERIA_Validator::MAX_LENGTH, 200),
						)),
					);
				case "internetmediatype" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(50)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::INTERNET_MEDIA_TYPE),
						)),
					);
				case "fileextension" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(20)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::MAX_LENGTH, 20),
							array(SERIA_Validator::MIN_LENGTH, 1),
						)),
					);
				case "mimetype":
					return array(
						'fieldtype' => 'text',
						'type' => 'varchar(100)',
						'validator' => new SERIA_Validator(array(
							array(SERIA_Validator::MIN_LENGTH, 3),
							array(SERIA_Validator::MAX_LENGTH, 100),
							array(SERIA_Validator::INTERNET_MEDIA_TYPE),
						)),
					);
				case "username" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(50)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::MAX_LENGTH, 100),
							array(SERIA_Validator::MIN_LENGTH, 4),
						)),
					);
				case "password" :
					return array(
						"fieldtype" => "password",
						"type" => "varchar(50)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::MIN_LENGTH, 5),
							array(SERIA_Validator::MAX_LENGTH, 50),
							array(SERIA_Validator::REQUIRED_CHARS, 'abcdefghijklmnopqrstuvwxyz', _t("Passwords must consist of numbers and upper- and lowercase english characters.")),
							array(SERIA_Validator::REQUIRED_CHARS, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', _t("Passwords must consist of numbers and upper- and lowercase english characters.")),
							array(SERIA_Validator::REQUIRED_CHARS, '0123456789', _t("Passwords must consist of numbers and upper- and lowercase english characters.")),
						)),
					);
				case "iso4217" :
				case "currencycode" :
					return array(
						"fieldtype" => "select",
						"type" => "varchar(3)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::CURRENCYCODE)
						)),
						"values" => SERIA_Dictionary::getDictionary('iso-4217'),
					);
				case "currency" : 
					return array(
						"fieldtype" => "text",
						"type" => "double",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::CURRENCY))),
					);
				case "integer" : case "int" :
					return array(
						"fieldtype" => "text",
						"type" => "integer",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::INTEGER))),
					);
				case "float" : case "int" :
					return array(
						"fieldtype" => "text",
						"type" => "float",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::FLOAT))),
					);
				case "country" : 
					return array(
						"fieldtype" => "select",
						"type" => "varchar(2)",
						"values" => SERIA_Dictionary::getDictionary('iso-3166'),
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::COUNTRYCODE))),
					);
				case "tags" :
				case "text" :
				case "note" :
				case "message" :
					return array(
						"fieldtype" => "textarea",
						"type" => "text",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::MAX_LENGTH, 200000))),
					);
				case "html" :
					return array(
						"fieldtype" => "htmlarea",
						"type" => "text",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::MAX_LENGTH, 100000))),
					);
				case "address" :
					return array(
						"fieldtype" => "textarea",
						"type" => "text",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::MAX_LENGTH, 1000))),
					);
				case "email" :
					return array(
						"fieldtype" => "email",
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
				case "rtmp_url" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(150)",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::RTMP_URL))),
					);
				case "rtp_url" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(150)",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::RTP_URL))),
					);
				case "rtsp_url" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(150)",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::RTSP_URL))),
					);
				case "gender" :
					return array(
						"fieldtype" => "select",
						"values" => array('m' => _t('Male'), 'f' => _t('Female')),
						"type" => "char(1)",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::ONE_OF, array('m','f')))),
					);
				case "ip" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(20)",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::IP_ADDRESS))),
					);
				case "domain" :
				case "hostname" :
					return array(
						"fieldtype" => "text",
						"type" => "varchar(100)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::HOSTNAME)
						)),
					);
				case "boolean" :
					return array(
						"fieldtype" => "checkbox",
						"type" => "tinyint(1)",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::ONE_OF, array(0,1)))),
					);
				case "timezone" :
					return array(
						"fieldtype" => "select",
						"type" => "varchar(100)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::TIMEZONE),
						)),
						"values" => new SERIA_TimezoneDictionary(),
					);
				case "latitude" :
					return array(
						'fieldtype' => 'text',
						'type' => 'float',
						'validator' => new SERIA_Validator(array(
							array(SERIA_Validator::FLOAT),
							array(SERIA_Validator::MIN_VALUE, -90),
							array(SERIA_Validator::MAX_VALUE, 90),
						)),
					);
				case "longitude" :
					return array(
						'fieldtype' => 'text',
						'type' => 'float',
						'validator' => new SERIA_Validator(array(
							array(SERIA_Validator::FLOAT),
							array(SERIA_Validator::MIN_VALUE, -180),
							array(SERIA_Validator::MAX_VALUE, 180),
						)),
					);
				case "duration" :
					return array(
						'fieldtype' => 'duration',
						'type' => 'float',
						'validator' => new SERIA_Validator(array(
							array(SERIA_Validator::FLOAT),
							array(SERIA_Validator::MIN_VALUE, 0),
						)),
					);
				case "classname" :
					if(!isset($info[2]) || (!isset($info[2]['extends']) && !isset($info[2]['implements'])))
						throw new SERIA_Exception("The 'classname' type requires 'extends' or 'implements' to be specified as an associative array in third parameter.");

					return array(
						'fieldtype' => 'text',
						'type' => 'varchar(100)',
						'validator' => new SERIA_Validator(array(
							array(SERIA_Validator::CALLBACK, array('SERIA_Meta', 'classNameValidator'), $info[2]),
						)),
					);
				case "hexcolor" :
					return array(
						"fieldtype" => 'text',
						"type" => "varchar(8)",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::MIN_LENGTH, 6),
							array(SERIA_Validator::MAX_LENGTH, 8),
							array(SERIA_Validator::LEGAL_CHARS, str_split('aAbBcCdDeEfF1234567890'))
						))
					);
				case "datetime" :
//					return SERIA_DateTimeMetaField::MetaField();
//				case "datetime2" :
					return array(
						"fieldtype" => 'datetime',
						"type" => 'datetime',
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::ISODATETIME),
						)),
					);
				case "date" :
					return array(
						"fieldtype" => "date",
						"type" => "date",
						"validator" => new SERIA_Validator(array(
							array(SERIA_Validator::ISODATE),
						)),
					);
					return SERIA_DateMetaField::MetaField();
				case "seria_metaobject" :
					return array(
						"fieldtype" => $specName,
						"type" => "varchar(40)",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::META_OBJECT, $specName))),
						"class" => 'SERIA_MetaObject',
					);
				case "enum" :
					if(!isset($info[2]) || !isset($info[2]['values'])) {
						throw new SERIA_Exception("The 'enum' type requires 'values' to be specified as an associative array in third parameter array('values'=>array()).");
					}

					return array(
						"fieldtype" => "select",
						"values" => $info[2]['values'],
						"type" => isset($info[2]['type']) ? $info[2]['type'] : 'varchar(200)',
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::ONE_OF, array_keys($info[2]['values'])))),
					);
				default :
					if(class_exists($specName))
					{
						if(is_subclass_of($specName, 'SERIA_MetaObject'))
						{
							if(!isset(self::$_primaryKeySpecs[$specName]))
							{
								// try to identify the primary key spec here, without causing a loop
								$tmp = call_user_func(array($specName, 'Meta'));
								$tmpPK = empty($tmp['primaryKey']) ? 'id' : $tmp['primaryKey'];
								if(empty($tmp['fields'][$tmpPK]['type']))
								{
									$tmpSpec = self::_getMappedFieldSpec('primarykey');
									self::$_primaryKeySpecs[$specName] = $tmpSpec['type'];
								}
								else
								{
									self::$_primaryKeySpecs[$specName] = $tmp['fields'][$tmpPK]['type'];
								}
							}
							$type = self::$_primaryKeySpecs[$specName];
						}
						else
						{
							$type = "int";
						}

						$res = array(
							"fieldtype" => $specName,
							"type" => $type,
							"validator" => new SERIA_Validator(array(array(SERIA_Validator::META_OBJECT, $specName))),
							"class" => $specName,
						);
						return $res;
					}

					throw new SERIA_Exception('Unknown field type "'.$specName.'".');
			}
		}

/**
		public static function _getPrimaryKeySpec($className)
		{
			$spec = call_user_func(array($className, 'Meta'));
			if(!isset($spec['primaryKey']))
				$primaryKey = 'id';
			else
				$primaryKey = $spec['primaryKey'];
			if(!isset($spec['fields'][$primaryKey]))
			{
				$pkSpec = self::_getMappedFieldSpec('primaryKey');
				return $pkSpec['type'];
			}
			else
			{
				FRODE
			}
		}
*/
		/**
		 *	Prepares a row to be inserted into the database, by handling special fields such as createdBy, modifiedDate and by
		 *	adding a primary key if missing. 
		 *	Will return an associative array, and all fields not defined in spec are removed and a ID is added to new rows.
		 *
		 *	@param array $row	Array of column => value pairs where values are ready to be inserted into the database.
		 * 	@param array $spec	Array of metaspec
		 */
		public /*package*/ static function _rowToDB($row, $spec, $isNew, $autoSave=false)
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
							if ($isNew)
							{ // this is a new row
								$newRow[$name] = ($t=SERIA_Base::userId()) ? $t : NULL;
							}
							break;
						case 'modifiedBy' :
							$newRow[$name] = ($t=SERIA_Base::userId()) ? $t : NULL;
							break;
						case 'createdDate' :
							if ($isNew)
							{ // new row
								$newRow[$name] = date('Y-m-d H:i:s', $ts);
							}
							break;
						case 'modifiedDate' :
							$newRow[$name] = date('Y-m-d H:i:s', $ts);
							break;
						case "isEnabled" :
							$newRow[$name] = 1;
							break;
						case 'parent':
							$newRow[$name] = $row[$name];
							break;
					}
				}
				else if(isset($row[$name]) && is_object($row[$name]))
				{
					if($autoSave)
					{	// saving references
						$res = self::save($row[$name], $autoSave);
						if(!$res) throw new SERIA_Exception('Unable to save object '.get_class($row[$name]).':'.$row->MetaBackdoor('get_key'));
						if($spec['fields'][$name]['fieldtype']===get_class($row[$name]))
							$newRow[$name] = $row[$name]->MetaBackdoor('get_key');
						else if($spec['fields'][$name]['fieldtype']==='SERIA_MetaObject')
							$newRow[$name] = self::getReference($row[$name]);
						else
							throw new Exception('Not supported to save instances of '.get_class($row[$name]));
					}
					else
						throw new SERIA_Exception('_rowToDB does not accept objects in the source row. Objects are converted by MetaBackdoor("get_row"). You can specify $recursive when calling SERIA_Meta::save() to save recursively.');
				}
				else if(isset($info['type']) && isset($row[$name]))
				{
					$tokens = SERIA_DB::sqlTokenize($info['type']);
					switch(strtolower($tokens[0]))
					{
						case 'date' : case 'datetime' : case 'year' :
							if (is_numeric($row[$name]))
								throw new SERIA_Exception('MetaBackdoor(\'get_row\') should have converted from timestamp to ISO-date: '.$row[$name]);
							$newRow[$name] = $row[$name];
							break;
						default :
							$newRow[$name] = $row[$name];
					}
				}
				else if(isset($row[$name]))
				{ // this is not good, since we do not have database model information in the MetaSpec.
					throw new SERIA_Exception('The \'type\' was not specified for field '.$name.' in MetaObject for table '.$spec['table'].'. This is required for updating the database.');
				}
				else
				{
					if(array_key_exists($name, $row) && $row[$name]===NULL)
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
				if(isset($spec['fields'][$name]))
				{
					if(!isset($spec['fields'][$name]['class']) && isset($spec['fields'][$name]['type']))
					{ // unless 'class' is specified, translate datetime, date and year to unix timestamps.
						$tokens = SERIA_DB::sqlTokenize($spec['fields'][$name]['type']);
						switch(strtolower($tokens[0]))
						{
/**
							case 'date' : case 'datetime' : case 'year' :
								$newRow[$name] = strtotime($row[$name]);
								break;
*/
							default :
								$newRow[$name] = $row[$name];
								break;
						}
					}
					else
					{
						$newRow[$name] = $row[$name];
					}
				}
				else
				{
					if($name == $spec['primaryKey'])
						$newRow[$name] = $row[$name];
				}
			}
			return $newRow;
		}

		public /*package*/ function _syncColumnSpec($spec)
		{
			$schema = array();

			foreach($spec['fields'] as $columnName => $info)
			{
				if(empty($info['type']))
				{
					throw new SERIA_Exception('Unable to sync, type not defined for "'.$columnName.'" ('.$spec['table'].')');
				}
				$schema[] = "`$columnName` ".$info['type'];
			}

			try
			{
				$old = SERIA_Base::db()->getColumnSpec($spec['table']);
			}
			catch (PDOException $e)
			{ // table does not exist
				if($e->getCode()==="42S02")
				{
					SERIA_Base::db()->exec('CREATE TABLE '.$spec['table'].' ('.implode(',', $schema).', PRIMARY KEY('.$spec['primaryKey'].')) DEFAULT CHARSET utf8');
					return true;
				}
				throw $e;
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

				if(SERIA_DEBUG)
				{
					if(sizeof($addedColumns)>0)
						SERIA_Base::debug("<strong>Modified table '".$spec['table']."'. Added ".sizeof($addedColumns)." columns.</strong>");
					if(sizeof($removedColumns)>0)
						SERIA_Base::debug("<strong>Modified table '".$spec['table']."'. Removed ".sizeof($removedColumns)." columns.</strong>");
					if(sizeof($alteredColumns)>0)
						SERIA_Base::debug("<strong>Modified table '".$spec['table']."'. Altered ".sizeof($alteredColumns)." columns.</strong>");
				}

				if(sizeof($addedColumns)>0 && sizeof($removedColumns)>0) {
					throw new SERIA_Exception('Schema for table "'.$spec['table'].'" has changed too much (Add: "'.implode(",", $addedColumns).'", remove: "'.implode(", ", $removedColumns).'", alter: "'.implode(", ", $alteredColumns).'"). It is not possible for me to know if you have renamed or added and deleted tables. Please update the database manually.');
				}

				// add new columns
				foreach($addedColumns as $columnName)
					SERIA_Base::db()->exec('ALTER TABLE '.$spec['table'].' ADD COLUMN `'.$columnName.'` '.$spec['fields'][$columnName]['type']);

				// drop deleted columns
				foreach($removedColumns as $columnName)
					SERIA_Base::db()->exec('ALTER TABLE '.$spec['table'].' DROP COLUMN `'.$columnName.'`');

				// modify existing columns
				foreach($alteredColumns as $columnName)
				{
					try {
//						echo ('ALTER TABLE '.$spec['table'].' MODIFY COLUMN `'.$columnName.'` '.$spec['fields'][$columnName]['type']);
						SERIA_Base::db()->exec('ALTER TABLE '.$spec['table'].' MODIFY COLUMN `'.$columnName.'` '.$spec['fields'][$columnName]['type']);
					} catch (PDOException $e) {
						self::_removeForeignKeys($spec['table'], $columnName);
						SERIA_Base::db()->exec('ALTER TABLE '.$spec['table'].' MODIFY COLUMN `'.$columnName.'` '.$spec['fields'][$columnName]['type']);
					}
				}
			}
			catch (PDOException $e)
			{
				throw new SERIA_Exception('Illegal SQL syntax; database server returned "'.$e->getMessage().'"');
			}
		}

		private function _removeForeignKeys($table, $column)
		{
			if($table[0]==="{")
				$table = SERIA_PREFIX."_".substr($table, 1, strlen($table)-2);
			$db = SERIA_Base::db();
			$constraints = $db->query("SELECT * FROM information_schema.key_column_usage WHERE REFERENCED_TABLE_SCHEMA=:database AND REFERENCED_TABLE_NAME=:table AND REFERENCED_COLUMN_NAME=:column", array(
					"database" => SERIA_DB_NAME,
					"table" => $table,
					"column" => $column,
			))->fetchAll(PDO::FETCH_ASSOC);
			foreach($constraints as $c)
				$db->exec("ALTER TABLE ".$c['TABLE_SCHEMA'].".".$c['TABLE_NAME']." DROP FOREIGN KEY ".$c["CONSTRAINT_NAME"]);

			$constraints = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.table_constraints WHERE TABLE_SCHEMA=:database AND TABLE_NAME=:table AND CONSTRAINT_TYPE='FOREIGN KEY'", array())->fetchAll(PDO::FETCH_COLUMN, 0);
			foreach($constraints as $constraint)
				$db->exec("ALTER TABLE ".$table." DROP FOREIGN KEY ".$constraint);
		}
/**
DELETE THIS
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
*/

		/**
		*	Provides a simple way to create an ActionForm for a MetaObject. Should NOT be 
		*	called from views, but should be called via a controller object.
		*	@param string $formId		An identifier that can be used for uniquely identifying this form when it is submitted
		*	@param string $instance		An instance of a SERIA_MetaObject
		*	@param array $fields		Optional array of field names to make available for the ActionForm
		*	@param boolean $saveReferences	Save referenced objects if they validate?
		*	@return SERIA_ActionForm
		*/
		public static function editAction($formId, SERIA_MetaObject $mo, array $fields=NULL, $saveReferences=false)
		{
			$spec = SERIA_Meta::_getSpec($mo);
			if($fields===NULL)
			{
				$fields = array_keys($spec['fields']);
				unset($fields[$spec['primaryKey']]);
			}

			$a = new SERIA_ActionForm($formId, $mo, $fields);
			if($a->hasData())
			{
				foreach($fields as $field)
				{
					$mo->set($field, $a->get($field));
				}
				$a->errors = SERIA_Meta::validate($mo, $saveReferences);
				if(!$a->errors)
				{
					$a->success = SERIA_Meta::save($mo, $saveReferences);
				}
			}
			return $a;
		}

		/**
		*	Return a SERIA_ActionUrl object that can be used to delete the provided SERIA_MetaObject
		*	@param string $name			The GET param name to use
		*	@param SERIA_MetaObject $object		The object you wish to delete
		*	@return SERIA_ActionUrl
		*/
		public static function deleteAction($name, SERIA_MetaObject $object)
		{
			$action = new SERIA_ActionUrl($name, $object);
			try
			{
				if($action->invoked())
					$action->success = SERIA_Meta::delete($object);
			}
			catch (SERIA_Exception $e)
			{
				$action->error = _t("Unable to delete");
			}
			return $action;
		}


		/**
		*	Returns true if the object is not saved in a database
		*/
		public static function isNew(SERIA_MetaObject $object)
		{
			return $object->MetaBackdoor('is_new');
		}

		/**
		*	Return true if the user is allowed to save the object
		*	@param SERIA_MetaObject $object	An instance of a SERIA_MetaObject
		*	@return boolean
		*/
		public static function allowEdit(SERIA_MetaObject $object)
		{
			return $object->MetaEditable();
		}

		/**
		*	Returns true if the user is allowed to delete the object
		*	@param SERIA_MetaObject $object	An instance of a SERIA_MetaObject
		*	@return boolean
		*/
		public static function allowDelete(SERIA_MetaObject $object)
		{
			return $object->MetaDeletable();
		}

		/**
		*	Returns true if the user is allowed to create an instance of the specified class
		*	@param string $className	A classname to check for legality of
		*	@return boolean
		*/
		public static function allowCreate($className)
		{
			return call_user_func(array($className, 'MetaCreatable'));
		}

		/**
		 *
		 * Get url to asset file.
		 *
		 * @param string $manifestName The name of the manifest (::NAME).
		 * @param string $filePath Relative to the components assets/ directory.
		 * @return SERIA_Url Url to asset.
		 * @throws SERIA_Exception
		 */
		public static function assetUrl($manifestName, $filePath) {
			$rootDir = SERIA_ROOT;
			$manifestDir = dirname(SERIA_Manifests::getManifest($manifestName)->getFileName());
			$rootLen = strlen($rootDir);
			if ($rootDir[$rootLen - 1] == DIRECTORY_SEPARATOR) {
				$rootLen--;
				$rootDir = substr($rootDir, 0, $rootLen);
			}
			SERIA_Base::debug('SERIA_Meta::assetUrl: Root dir: '.$rootDir);
			$pathFromRoot = substr($manifestDir, $rootLen);
			SERIA_Base::debug('SERIA_Meta::assetUrl: Uncorrected path: '.$pathFromRoot);
			if (substr($manifestDir, 0, $rootLen) != $rootDir || ($pathFromRoot && $pathFromRoot[0] != DIRECTORY_SEPARATOR)) {
				$rootDir = realpath($rootDir);
				$rootLen = strlen($rootDir);
				if ($rootDir[$rootLen - 1] == DIRECTORY_SEPARATOR) {
					$rootLen--;
					$rootDir = substr($rootDir, 0, $rootLen);
				}
				SERIA_Base::debug('SERIA_Meta::assetUrl: Corrected root dir: '.$rootDir);
				$pathFromRoot = substr($manifestDir, $rootLen);
				SERIA_Base::debug('SERIA_Meta::assetUrl: Corrected path: '.$pathFromRoot);
			}
			if (substr($manifestDir, 0, $rootLen) != $rootDir || ($pathFromRoot && $pathFromRoot[0] != DIRECTORY_SEPARATOR))
				throw new SERIA_Exception('The asset resource can not be found in the http-root because the component '.$manifestName.' is outside SERIA_ROOT.');

			/* Windows uses backslash for local filesystem. They have to be replaced with / for web */
			if (DIRECTORY_SEPARATOR != '/')
				$pathFromRoot = str_replace(DIRECTORY_SEPARATOR, '/', $pathFromRoot);

			if (!$pathFromRoot || $pathFromRoot[0] != '/')
				$pathFromRoot = '/'.$pathFromRoot;
			$url = SERIA_HTTP_ROOT.$pathFromRoot.'/assets/'.$filePath;
			return new SERIA_Url($url);
		}
		
		public static function manifestUrl($manifestName, $page=NULL, array $params=NULL)
		{
			$url = new SERIA_Url(SERIA_HTTP_ROOT."/");
			$route = 'seria/'.$manifestName;
			if($page!==NULL)
			{
				$parts = explode("/", $page);
				foreach($parts as $part) if($part)
					$route .= "/".$part;
			}
			$url->setParam('route', $route);
			if($params!==NULL)
			{
				$parts = array();
				foreach($params as $key => $param)
					$url->setParam($key, $param);
			}
			return $url;
		}

		public static function classNameValidator($spec, $className, $extra)
		{
			if(isset($extra['extends']))
			{
				if(!is_subclass_of($className, $extra['extends']))
					return _t("The class %CLASS% must extend %EXTENDS%.", array("CLASS" => $className, "EXTENDS" => $extra['extends']));
			}
			if(isset($extra['implements']))
			{
				$ref = new ReflectionClass($className);
				if(!$ref->implementsInterface($extra['implements']))
					return _t("The class %CLASS% must implement %INTERFACE%.", array("CLASS" => $className, "INTERFACE" => $extra['implements']));
			}
			return false;
		}
	}
