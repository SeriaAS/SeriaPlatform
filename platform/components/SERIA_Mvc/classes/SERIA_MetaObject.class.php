<?php
	abstract class SERIA_MetaObject implements SERIA_NamedObject, ArrayAccess, Countable, Iterator, SERIA_IMetaField
	{
		/**
		 *
		 * Array that holds keys of changed row-fields as array(fieldName => true).
		 * @var array
		 */
		protected $changedRow = array();

		abstract public static function Meta($instance=NULL); 
		/* EXAMPLE IMPLEMENTATION {
			return array(
				'table' => '{tablename}',
				'primaryKey' => 'fieldName',
// specifies the format of the primary key
// TODO				'displayField' => 'fieldName',
// specifies which field to display in select boxes or similar by default. Also used for autocomplete selectors. Also used for __toString()
				'fields' => array(
					'name' => array('name required', _t('Name')),
					'address' => array('address', _t('Address')),
				),
			);
		} END EXAMPLE IMPLEMENTATION */

		public function __toString()
		{
			$spec = SERIA_Meta::_getSpec($this);
			if(!isset($spec['displayField']))
				return "[Error in ".get_class($this)."; displayField not configured]";

			return $this->get($spec['displayField']);
		}

		/**
		*	Create an instance of the object.
		*/
		public static function createFromUser($value)
		{
			return SERIA_Meta::load(get_called_class(), $value);
		}

		public static function createFromDb($value)
		{
			return SERIA_Meta::load(get_called_class(), $value);
		}

		public function toDbFieldValue()
		{
			return $this->MetaBackdoor('get_key');
		}

		public static function renderFormField($fieldName, $current, array $params=NULL, $hasError=false)
		{
			$values = SERIA_Meta::all(get_called_class());
			$options[] = array();
			foreach($values as $value)
			{
				$options[] = '<option value="'.$value->MetaBackdoor('get_key').'"'.($value==$current?' selected="selected"':'').'>'.$value->__toString().'</option>';
			}
			return SERIA_ActionForm::renderTag('select', $attributes, array(
				'id' => $fieldName,
				'name' => $fieldName,
				'class' => 'select'.($hasError?' ui-state-error':''),
			), implode("", $options));
		}

		public function toDb()
		{
			return $this->MetaBackdoor('get_key');
		}

		public static function MetaField()
		{
			$className = get_called_class();
			if ($className != 'SERIA_MetaObject')
				$spec = SERIA_Meta::_getSpec($className);
			else
				$spec = array();
			if(isset($spec['caption']))
				$caption = $spec['caption'];
			else
				$caption = '"caption" not defined in Meta spec for class "'.$className.'"';
			$spec = $spec['fields'][$spec['primaryKey']];
			$spec['fieldtype'] = $spec['class'] = $className;
			if(!isset($spec['caption']))
				$spec['caption'] = $caption;
			return $spec;
		}

		/**
		*	Special method to add custom validation rules that the rulesets cannot handle. For
		*	example to check if parent is self. Should return false if nothing is wrong. Should
		*	return an associative array of fieldname => error message if validation errors
		*/
		public function MetaIsInvalid() {
			return false;
		}

		public static function MetaQuery() {
			return SERIA_Meta::all(get_called_class());
		}

		/**
		*	Special method to filter queries for an extra level of access control. For example
		*	you should check if(SERIA_Base::viewMode()=="public") and return 'isPublished=1'.
		*	isPublished=1 will then be added to the queries by SERIA_MetaQuery if the website
		*	is being viewed in the public context.
		*	$return string		Return ordinary SQL
		*/
		public static function MetaSelect() {
			return NULL;
		}

		/**
		*	Special method to check if the object is deletable. Use SERIA_Meta::allowDelete($instance) to check.
		*	@return boolean
		*/
		public function MetaDeletable() {
			return true;
		}

		/**
		*	Special method to check if the object is editable. Use SERIA_Meta::allowEdit($instance) to check.
		*	@return boolean
		*/
		public function MetaEditable() {
			return true;
		}

		/**
		*	Special method to check if the user can create new objects. Use SERIA_Meta::allowCreate('ClassName') to check.
		*	@return boolean
		*/
		public static function MetaCreatable() {
			return true;
		}

		/**
		*	Special method that is called before saving the object to database
		*	@return boolean		True if allowed to continue
		*/
		protected function MetaBeforeSave() {
			return SERIA_Meta::allowEdit($this);
		}

		/**
		*	Special method that is called after saving the object to the database
		*/
		protected function MetaAfterSave() {
		}

		/**
		*	Special method that is called after loading the object from the database
		*/
		protected function MetaAfterLoad() {
		}

		/**
		*	Special method that is called before deleting the object from the database
		*	@return boolean		True if allowed to continue
		*/
		protected function MetaBeforeDelete() {
			return SERIA_Meta::allowDelete($this);
		}

		/**
		*	Special method that is called after the object have been deleted from the database
		*/
		protected function MetaAfterDelete() {
		}

		/**
		*	Special method that is called immediately after the constructor when the object is new
		*/
		protected function MetaAfterCreate() {
		}

		/**
		*	Save the object to the database. Warning! Overriding this method is not perfect, since
		*	SERIA_MetaObjects often are saved using SERIA_Meta::save($object) and this method will
		*	not be invoked.
		*/
		protected final function save() {
			return SERIA_Meta::save($this);
		}

		/**
		*	Load an instance of this object class.
		*/
		protected final static function load($id) {
			return SERIA_Meta::load(get_called_class, $id);
		}

		/**
		*	Returns a string that uniquely identifies this object.
		*/
		public function getObjectId() {
			return SERIA_Meta::getNamedObjectId($this);
		}

		/**
		*	Returns true or false on if this object comes from the database or is new
		*/
		public function MetaIsNew() {
			return $this->metaNew;
		}

		/**
		*	Special method that makes it possible for SERIA_Meta to manipulate Meta Objects
		*	Do not call this directly unless you are working on a component for SERIA_Meta and
		*	know exactly what you are doing, and are prepared for updating your code since the 
		*	interface here might change without warning.
		*
		*	'get_row' always returns a row ready to insert unmodified into the database, but it has not been validated.
		*	'set_row' always overwrite the existing row, and updates the "is_new" status. It assumes that the row comes directly from the database unmodified.
		*	'get_key' returns the primary key for this row
		*	'is_new' returns true if this object has not been saved (e.g. it does not have a primary key)
		*
		*	@param string $action		The action to perform
		*	@param mixed $data		Optional data for the action
		*/
		public function MetaBackdoor($action, $data=NULL)
		{
			switch($action)
			{
				case 'raise_event' :
					switch($data)
					{
						case SERIA_Meta::BEFORE_SAVE_EVENT : return $this->MetaBeforeSave(); break;
						case SERIA_Meta::AFTER_SAVE_EVENT : return $this->MetaAfterSave(); break;
						case SERIA_Meta::AFTER_LOAD_EVENT : return $this->MetaAfterLoad(); break;
						case SERIA_Meta::BEFORE_DELETE_EVENT : return $this->MetaBeforeDelete(); break;
						case SERIA_Meta::AFTER_DELETE_EVENT : return $this->MetaAfterDelete(); break;
						case SERIA_Meta::AFTER_CREATE_EVENT : return $this->MetaAfterCreate(); break;
					}
					break;
				case 'get_row' :
					// Update $this->row so that it is ready to be stored in the database
					$spec = SERIA_Meta::_getSpec($this);
					foreach($this->metaCache as $name => $value)
					{
						if(is_object($value))
						{
							if(!isset($spec['fields'][$name]['class']))
							{
								throw new SERIA_Exception('Class not specified in field type for field '.$name.'. This is done in SERIA_Meta::_getSpec().');
							}
							else if($spec['fields'][$name]['class']==='SERIA_MetaObject')
							{
								$this->row[$name] = SERIA_Meta::getReference($value);
							}
							else if(is_subclass_of($spec['fields'][$name]['class'], 'SERIA_MetaObject'))
							{
								// unsaved MetaObjects are returned by instance, instead of by key. Must handle this when validating and saving elsewhere.
								if(($this->row[$name] = $value->MetaBackdoor('get_key'))===NULL)
									$this->row[$name] = $value;
							}
							else if(in_array('SERIA_IMetaField', class_implements($spec['fields'][$name]['class'])))
							{
								$this->row[$name] = call_user_func(array($value, 'toDbFieldValue'));
							}
							else if(is_subclass_of($spec['fields'][$name]['class'], 'SERIA_FluentObject') || in_array('SERIA_IFluentObject', class_implements($spec['fields'][$name]['class'])))
							{
								$this->row[$name] = call_user_func(array($value, 'getKey'));
							}
							else
								throw new SERIA_Exception('Unsupported class "'.get_class($value).'" specified for field "'.$name.'".');
						}
						else if(!empty($spec['fields'][$name]['type']))
						{
							$tokens = SERIA_DB::sqlTokenize($spec['fields'][$name]['type']);
							switch(strtolower($tokens[0]))
							{
								case 'year' : case 'date' : case 'datetime' :
									if(trim($this->row[$name],'0- :')=='') $this->row[$name] = NULL;
									break;
							}
						}
						else
						{
							throw new Exception('I do not know how to convert ->metaCache['.$name.'] to ->row['.$name.']. Either make sure this value is never inserted to the metaCache, or make sure I know how to convert it.');
						}
					}
					return $this->row;
					break;
				case 'get_update_row':
					$fullRow = $this->MetaBackdoor('get_row');
					if ($this->metaNew)
						return $fullRow;
					$updateRow = array();
					foreach ($this->changedRow as $name => $changed) {
						if ($changed && isset($fullRow[$name]))
							$updateRow[$name] = $fullRow[$name];
					}
					if ($fullRow[$spec['primaryKey']])
						$updateRow[$spec['primaryKey']] = $fullRow[$spec['primaryKey']];
					return $updateRow;
					break;
				case 'set_row' : 
					$spec = SERIA_Meta::_getSpec($this);
					$this->metaNew = empty($data[$spec['primaryKey']]);
					$this->metaCache = array();
					$this->row = $data;
					break;
				case 'get_key' : 
					$spec = SERIA_Meta::_getSpec($this);
					return $this->row[$spec['primaryKey']];
					break;
				case 'is_new' : 
					return $this->metaNew;
					break;
				default : throw new SERIA_Exception('Unknown action "'.$action.'".');
			}
		}

		private $row = array();		// store row data as it is represented in the database
		private $metaNew = true;	// store wether or not this is an unsaved instance
		private $metaCache = array();	// cache prepared data for each column
		/**
		*	Get a value. If the field references a SERIA_Meta-object, SERIA_MetaField or a SERIA_FluentObject
		*	the object will be instantiated. If the field does not exist, an exception will be thrown. Datetime,
		*	date and time column types will NOT be changed to a unix timestamp. Expected format YYYY-MM-DD HH:MM:SS
		*
		*	@param $name	The field name to fetch. The field name may be aliased in the Meta() fieldAliases array.
		*	@return mixed
		*/
		public function get($name)
		{
			// todo: make sure that $this->$name is a non-static method and that it has no parameters
			if((substr($name, -6)==='Action' || substr($name, -4)==='View') && is_callable(array($this,$name)))
				return call_user_func(array($this,$name));

			$spec = SERIA_Meta::_getSpec(get_class($this));
			if(isset($spec['fieldAliases']) && isset($spec['fieldAliases'][$name]))
				$name = $spec['fieldAliases'][$name];

			if(isset($this->metaCache[$name]))
			{
				return $this->metaCache[$name];
			}

			if(!isset($spec['fields'][$name]))
			{
				throw new SERIA_Exception('No such field '.$name, SERIA_Exception::NOT_FOUND);
			}

			if(!isset($this->row[$name]))
			{
				return NULL;
			}

			// Performance tweak; do not check if this can be an object when the value is empty.
			if(empty($this->row[$name]))
				return $this->row[$name];


			// Referred classes are instantiated, stored in $this->metaCache[$name] then returned, but only when they are accessed.
			if(isset($spec['fields'][$name]['class']))
			{ // this field is a special field type that should return an instance of the specified class

				if($spec['fields'][$name]['class'] == 'SERIA_MetaObject')
					return $this->metaCache[$name] = SERIA_Meta::getByReference($this->row[$name]);
				else if(is_subclass_of($spec['fields'][$name]['class'], 'SERIA_MetaObject'))
					return $this->metaCache[$name] = SERIA_Meta::load($spec['fields'][$name]['class'], $this->row[$name]);
				else if(in_array('SERIA_IMetaField', class_implements($spec['fields'][$name]['class'])))
					return $this->metaCache[$name] = call_user_func(array($spec['fields'][$name]['class'], 'createFromDb'), $this->row[$name]);
				else if(is_subclass_of($spec['fields'][$name]['class'], 'SERIA_FluentObject') || in_array('SERIA_IFluentObject', class_implements($spec['fields'][$name]['class'])))
					return $this->metaCache[$name] = SERIA_Fluent::load($spec['fields'][$name]['class'], $this->row[$name]);
				else
					throw new SERIA_Exception('Unsupported class "'.$spec['fields'][$name]['class'].'" specified for field "'.$name.'".');

			}


			if(isset($spec['fields'][$name]['type']))
			{
				$tokens = SERIA_DB::sqlTokenize($spec['fields'][$name]['type']);
				switch(strtolower($tokens[0]))
				{
					case 'date' : case 'year' : case 'datetime' :
						if(trim($this->row[$name],'0- :')=='') return NULL;
						break;
				}
			}

			return $this->row[$name];
		}
		public function offsetGet($name) { return $this->get($name); }

		/**
		*	Set a value. If the value is such that it cannot be stored directly in the database - for example an object, then it is stored in $this->metaCache. Only when the object
		*	is saved, then $this->row is updated. This function expects objects, unix timestamps and scalars as values.
		*
		*	@param string $name		The name of the field to set
		*/
		public function set($name, $value)
		{
			$spec = SERIA_Meta::_getSpec($this);
			if(!isset($spec['fields'][$name]))
				throw new SERIA_Exception('No such field '.$name);

			if(is_object($value))
			{
				$this->metaCache[$name] = $value;
			}
			else
			{
				if(isset($spec['fields'][$name]['type']))
				{
					$tokens = SERIA_DB::sqlTokenize($spec['fields'][$name]['type']);
					switch(strtolower($tokens[0]))
					{
/*
						case 'year' :
							if(strlen($value)==4) // assume year
								$this->row[$name] = $value;
							else // assume timestamp
								$this->row[$name] = date('Y', $value);
						case 'date' : case 'datetime' :
							if(trim($value, "0123456789")=="") // assume timestamp
								$this->row[$name] = date('Y-m-d H:i:s', $value);
							else // assume database format
								$this->row[$name] = $value;
							break;
*/
						default :
							$this->row[$name] = $value;
							break;
					}
				}

				$this->metaCache[$name] = $value;
			}

			$this->changedRow[$name] = true;

			return $this;
		}
		/**
		*	@see SERIA_MetaObject::set
		*/
		public function offsetSet($name, $value) { return $this->set($name, $value); }

		public function offsetExists($name) {
			$spec = SERIA_Meta::_getSpec($this);
			return isset($spec['fields'][$name]);
		}

		// Countable::count
		public function count() {
			$spec = SERIA_Meta::_getSpec($this);
			return sizeof($spec['fields']);
		}

		// Iterator::current
		private $_iteratorField = 0;
		private $_iteratorFields = NULL;
		public function rewind() { $this->_iteratorField = 0; }
		public function current() {
			if($this->_iteratorFields===NULL)  
			{
				$spec = SERIA_Meta::_getSpec($this);
				$this->_iteratorFields = array_keys($spec['fields']);
			}
			if(!isset($this->_iteratorFields[$this->_iteratorField]))
				return NULL;

			return $this->get($this->_iteratorFields[$this->_iteratorField]);
		}
		public function key() { 
			if($this->_iteratorFields===NULL)  
			{
				$spec = SERIA_Meta::_getSpec($this);
				$this->_iteratorFields = array_keys($spec['fields']);
			}
			return $this->_iteratorFields[$this->_iteratorField];
		}
		public function next() { 
			$this->_iteratorField++; 
			return $this->current();
		}
		public function valid() {
			if($this->_iteratorFields===NULL)
			{
				$spec = SERIA_Meta::_getSpec($this);
				$this->_iteratorFields = array_keys($spec['fields']);
			}
			return isset($this->_iteratorFields[$this->_iteratorField]);
		}

		public function offsetUnset($name) { throw new SERIA_Exception('Not supported'); }

		public final function __construct($p=false)
		{
			if(!SERIA_Meta::allowCreate(get_class($this)))
				throw new SERIA_Exception('Access denied creating an instance of "'.get_class($this).'".', SERIA_Exception::ACCESS_DENIED);

			if($p === false)
			{ // entirely new object
				$this->primaryKey = false;
				$this->row = array();
				$this->MetaBackdoor('raise_event', SERIA_Meta::AFTER_CREATE_EVENT);
			}
			else if(is_array($p))
			{
				$this->row = $p;
				$this->metaNew = false;
				$this->MetaBackdoor('raise_event', SERIA_Meta::AFTER_LOAD_EVENT);
			}
			else
			{
				$spec = SERIA_Meta::_getSpec($this);
                                $this->row = SERIA_DbData::table($spec['table'])->where($spec['primaryKey'].'=:key', array('key' => $p))->limit(1)->current();
				$this->metaNew = false;

				if(!$this->row)
					throw new SERIA_NotFoundException('Could not find '.get_class($this).' with id='.$p);

				$this->MetaBackdoor('raise_event', SERIA_Meta::AFTER_LOAD_EVENT);
                        }
                }

                /**
                *       Methods related to collections of data
                */
                // public static function getCollectionApi($start=0, $length=1000, $options=NULL); // returns an array with $length items representing table rows, starting at offset $start. $options is an associative arr$
                // public static function putCollectionApi($values, $options=NULL); // overwrite entire collection, return true or false
                // public static functino postCollectionApi($values, $options); // insert a new element to the collection, return the new primary key or throw an exception
                // public static function deleteCollectionApi($options=NULL); // delete the entire collection, return true or throw an exception

                /**
                *       Methods related to a specified element belonging to a collection
                */
                // public static function getElementApi($key, $options=NULL) // returns an array of key=>value pairs
                // public static function putElementApi($key, $values, $options=NULL) // overwrite or create element, return true or false
                // public static function deleteElementApi($key, $options=NULL) // delete an element, return true or throw an exception

		/**
		*	SERIA_IApiAccess method. Requires the MetaSelect and MetaFields methods to be overridden, for security reasons.
		*	When overriding the methods, make sure that MetaSelect returns a query where part that ONLY returns the rows that
		*	the current user is allowed to see and that MetaFields returns an array containing only the columns that the user
		*	is allowed to see.
		*/
		public static function getCollectionApi($start=0, $length=1000, $options=NULL)
		{
			$class = get_called_class();
			$metaSelectReflect = new ReflectionMethod($class, 'MetaSelect');
			if($metaSelectReflect->class != $class)
				throw new SERIA_Exception('The "'.$class.'::MetaSelect"-method has not been overridden. You must apply security restrictions before this api can be made available. The method must return a where clause or NULL if you wish to make everything available to everybody.');
			try {
				$metaFieldsReflect = new ReflectionMethod($class, 'MetaFields');
			} catch (ReflectionException $e) {
				throw new SERIA_Exception('The "'.$class.'::MetaFields"-method has not been declared. You must apply security restrictions before this api can be made available. The method must return an array containing the field names the user is allowed to read or NULL if all fields should be available.');
			}
			if($metaFieldsReflect->class != $class)
				throw new SERIA_Exception('The "'.$class.'::MetaFields"-method has not been declared. You must apply security restrictions before this api can be made available. The method must return an array containing the field names the user is allowed to read or NULL if all fields should be available.');

			$elements = SERIA_Meta::all($class)->limit($start, $length);
			$fields = call_user_func(array($class, 'MetaFields'), $options);
			$results = array();
			foreach($elements as $element)
			{
				$result = array();
				foreach($fields as $field)
				{
					$result[$field] = $element->get($field);
				}
				$results[] = $result;
			}
			return $results;

		}

		/**
		*	SERIA_IApiAccess method. Requires the MetaSelect and MetaFields methods to be overridden. @see SERIA_MetaObject::getCollectionApi
		*/
		public static function getElementApi($key, $options)
		{
			$class = get_called_class();
			$metaSelectReflect = new ReflectionMethod($class, 'MetaSelect');
			if($metaSelectReflect->class != $class)
				throw new SERIA_Exception('The "'.$class.'::MetaSelect"-method has not been overridden. You must apply security restrictions before this api can be made available. The method must return a where clause or NULL if you wish to make everything available to everybody.');
			try {
				$metaFieldsReflect = new ReflectionMethod($class, 'MetaFields');
			} catch (ReflectionException $e) {
				throw new SERIA_Exception('The "'.$class.'::MetaFields"-method has not been declared. You must apply security restrictions before this api can be made available. The method must return an array containing the field names the user is allowed to read or NULL if all fields should be available.');
			}
			if($metaFieldsReflect->class != $class)
				throw new SERIA_Exception('The "'.$class.'::MetaFields"-method has not been declared. You must apply security restrictions before this api can be made available. The method must return an array containing the field names the user is allowed to read or NULL if all fields should be available.');

			$element = SERIA_Meta::load($class, $key);
			$fields = call_user_func(array($class, 'MetaFields'), $options);
			$result = array();
			foreach($fields as $field)
				$result[$field] = $element->get($field);

			return $result;
		}
	}
