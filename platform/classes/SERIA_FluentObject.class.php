<?php
	/**
	*	Special interface indicating that the class supports creating objects by
	*	passing a database row to the createObject method and back to the database by
	*	calling toRow().
	*/
	abstract class SERIA_FluentObject extends SERIA_EventDispatcher implements SERIA_IFluentObject, SERIA_NamedObject
	{
		protected $row = array();
		protected $specCache = array();
		public $errors = false;

		/**
		*	Format:
		*
		*	array(
		*		'fieldname' => array( // None of these attributes are required
		*			'fieldtype' => [text|textarea|checkbox|datepicker|email|etc],
		*			'caption' => _t('A translated caption for this field'),
		*			'weight' => 0,						// Fields are sorted according to their weight
		*			'validator' => [SERIA_Validator object],
		*			'default' => 'some default value',
		*			'helptext' => 'Simple <strong>html</strong> text',	// Only use HTML if needed. Never use block level elements in the help text.
		*		),
		*		...
		*	);
		*/
		/* FROM SERIA_IFluentObject */// abstract public static function getFieldSpec(); // returns array() specifying rules for the columns
		/* FROM SERIA_IFluentObject */// abstract public static function fluentSpec(); // returns array('table' => '{tablename}', 'primaryKey' => 'id', 'selectWhere' => 'ownerId=123')
		/* FROM SERIA_IFluentObject */// abstract public static function fromDB($row); // 
		/* FROM SERIA_IFluentObject */// abstract public static function createObject($p=false); // should accept false, an array or a primay key and return a single instance of the object (must use caching)

		public function __construct($p=false)
		{
			if($p === false)
			{
				$this->row = array();
			}
			else if(is_array($p))
			{
				$this->row = $p;
			}
			else
			{
				$flS = $this->_getFluentSpec();
				$this->row = SERIA_Base::db()->query('SELECT * FROM '.$flS['table'].' WHERE '.$flS['primaryKey'].'=:key', array('key' => $p))->fetch(PDO::FETCH_ASSOC);

				if(!$this->row)
					throw new SERIA_NotFoundException('Could not find '.get_class($this).' with ID='.$p);

				$this->row = SERIA_Fluent::_prepareRowForObject($this->row, $this->_getFieldSpec());
			}
		}

		public function form()
		{
			return new SERIA_FluentForm($this);
		}

		public function toDB()
		{
			return $this->row;
		}

		public function getKey()
		{
			$fluentSpec = $this->_getFluentSpec();

			if(!$this->row || !isset($this->row[$fluentSpec['primaryKey']]))
				return false;

			return $this->row[$fluentSpec['primaryKey']];
		}

		// Special function used when saving a fluent object by SERIA_Fluent
		public function _setKey($key)
		{
			$fluentSpec = $this->_getFluentSpec();
			$this->row[$fluentSpec['primaryKey']] = $key;
		}

		public function get($name)
		{
			$fieldSpec = $this->_getFieldSpec();
			if(!isset($fieldSpec[$name]))
				throw new SERIA_Exception('No such field '.$name);
			if(!isset($this->row[$name]))
				return false;

			return $this->row[$name];
		}

		public function set($name, $value)
		{
			if($value instanceof SERIA_IFluentObject)
				$value = $value->getKey();
			$fieldSpec = $this->_getFieldSpec();
			if(!isset($fieldSpec[$name]))
				throw new SERIA_Exception('No such field '.$name);
			$this->row[$name] = $value;

			return true;
		}

                /**
                *       Validates all fields, adds error messages to $this->errors and returns true if no errors were found.
                */
		public function validate()
		{
			$fields = $this->_getFieldSpec();
			$errors = array();
			foreach($fields as $fieldName => $spec)
			{
				if(is_array($spec))
				{ // special fields are assumed correct
					if(isset($spec['validator']))
					{
						if($e = $spec['validator']->isInvalid($this->row[$fieldName], array('object' => $this, 'field' => $fieldName)))
							$errors[$fieldName] = $e;
					}
				}
			}

			if(sizeof($errors)>0)
			{
				$this->errors = $errors;
				return false;
			}
			else
			{
				$this->errors = false;
				return true;
			}
		}

                /**
                *       Inverted alias of ->validate, since some people may like this syntax better:-)
                */
                public function isInvalid()
                {
                        return !$this->validate();
                }

		public function save()
		{
			SERIA_Fluent::save($this);
		}

		protected function _getFluentSpec()
		{
			if(!isset($this->specCache['fluent']))
			{
				$className = get_class($this);
				$this->specCache['fluent'] = call_user_func(array($className, 'fluentSpec'));
			}
			return $this->specCache["fluent"];
		}
		protected function _getFieldSpec()
		{
			if(!isset($this->specCache['field']))
			{
				$className = get_class($this);
				$this->specCache['field'] = SERIA_Fluent::getFieldSpec($className);
			}
			return $this->specCache['field'];
		}

		function getObjectId()
		{
			$fls = $this->_getFluentSpec();
			if(!isset($this->row[$fls['primaryKey']]))
				throw new SERIA_Exception("Can't get object id when object is not saved.");
			return array('SERIA_Fluent','load',get_class($this),$this->getKey());
		}

		function delete() {
			return SERIA_Fluent::delete($this);
		}
	}
