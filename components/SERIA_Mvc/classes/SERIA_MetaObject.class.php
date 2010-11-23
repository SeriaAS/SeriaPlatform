<?php
	abstract class SERIA_MetaObject implements SERIA_NamedObject, ArrayAccess, Countable, Iterator
	{
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
		*	Special method to filter queries for an extra level of access control. For example
		*	you should check if(SERIA_Base::viewMode()=="public") and return 'isPublished=1'.
		*	isPublished=1 will then be added to the queries by SERIA_MetaQuery if the website
		*	is being viewed in the public context.
		*/
		public static function MetaSelect() {
			return NULL;
		}

		/**
		*	Special method to check if the object is deletable. Use SERIA_Meta::isDeletable($instance) to check.
		*	@return boolean
		*/
		public function MetaDeletable() {
			return true;
		}

		/**
		*	Special method to check if the object is editable. Use SERIA_Meta::isEditable($instance) to check.
		*	@return boolean
		*/
		public function MetaEditable() {
			return true;
		}

		/**
		*	Special method to check if the user can create new objects. Use SERIA_Meta::canCreate('ClassName') to check.
		*	@return boolean
		*/
		public function MetaCreatable() {
			return true;
		}

		/**
		*	Special method that is called before saving the object to database
		*	@return boolean		True if allowed to continue
		*/
		protected function MetaBeforeSave() {
			return true;
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
			return true;
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
						case 'before_save' : return $this->MetaBeforeSave(); break;
						case 'after_save' : return $this->MetaAfterSave(); break;
						case 'after_load' : return $this->MetaAfterLoad(); break;
						case 'before_delete' : return $this->MetaBeforeDelete(); break;
						case 'after_delete' : return $this->MetaAfterDelete(); break;
						case 'after_create' : return $this->MetaAfterCreate(); break;
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
								$this->row[$name] = call_user_func(array($value, 'toDb'));
							}
							else if(is_subclass_of($spec['fields'][$name]['class'], 'SERIA_FluentObject') || in_array('SERIA_IFluentObject', class_implements($spec['fields'][$name]['class'])))
							{
								$this->row[$name] = call_user_func(array($value, 'getKey'));
							}
							else
								throw new SERIA_Exception('Unsupported class "'.get_class($value).'" specified for field "'.$name.'".');
						}
						else if(isset($spec['fields'][$name]['type']))
						{
							$tokens = SERIA_DB::sqlTokenize($spec['fields'][$name]['type']);

							switch(strtolower($tokens[0]))
							{
								case 'date' : case 'datetime' :
									$this->row[$name] = date('Y-m-d H:i:s', $this->metaCache[$name]);
									break;
								case 'year' :
									$this->row[$name] = date('Y', $this->metaCache[$name]);
									break;
								case 'tinyint' :
									
							}
						}
						else
						{
							throw new Exception('I do not know how to convert ->metaCache['.$name.'] to ->row['.$name.']. Either make sure this value is never inserted to the metaCache, or make sure I know how to convert it.');
						}
					}
					return $this->row;
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
		*	date and time column types will be changed to a unix timestamp.
		*
		*	@param $name	The field name to fetch. The field name may be aliased in the Meta() fieldAliases array.
		*	@return mixed
		*/
		public function get($name)
		{
			$spec = SERIA_Meta::_getSpec(get_class($this));
			if(isset($spec['fieldAliases']) && isset($spec['fieldAliases'][$name]))
				$name = $spec['fieldAliases'][$name];

			if(isset($this->metaCache[$name]))
				return $this->metaCache[$name];

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
					case 'date' : case 'datetime' :
						return $this->metaCache[$name] = strtotime($this->row[$name]);
					case 'year' :
						return $this->metaCache[$name] = strtotime($this->row[$name].'-01-01');
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
						case 'date' : case 'datetime' : case 'year' :
							$this->row[$name] = date('Y-m-d H:i:s', $value);
							break;
						default :
							$this->row[$name] = $value;
							break;
					}
				}

				$this->metaCache[$name] = $value;
			}

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
			if($p === false)
			{ // entirely new object
				$this->primaryKey = false;
				$this->row = array();
				$this->MetaAfterCreate();
			}
			else if(is_array($p))
			{
				$this->row = $p;
				$this->metaNew = false;
				$this->MetaAfterLoad();
			}
			else
			{
				$spec = SERIA_Meta::_getSpec($this);
                                $this->row = SERIA_DbData::table($spec['table'])->where($spec['primaryKey'].'=:key', array('key' => $p))->limit(1)->current();
				$this->metaNew = false;

				if(!$this->row)
					throw new SERIA_NotFoundException('Could not find '.get_class($this).' with id='.$p);

				$this->MetaAfterLoad();
                        }
                }
	}
