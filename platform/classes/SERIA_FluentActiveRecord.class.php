<?php

/*
 * Glue class that implements a SERIA_IFluentObject with SERIA_ActiveRecord-objects as a backend.
 */

class SERIA_FluentActiveRecord extends SERIA_FluentObject
{
	/*
	 * Config: ovveride these..
	 */
	protected $glueToClassName = null; /* You must override this. Specify as string */

	/*
	 * Static cache..
	 */
	protected $emptyActiveRecordObject = null;

	/*
	 * Object attrs
	 */
	protected $activeRecordObject = null;

	public function __construct($activeRecordObject=null)
	{
		if (is_array($activeRecordObject)) {
			$obj = $this->fromDB($activeRecordObject);
			$this->activeRecordObject = $obj->getActiveRecordObject();
			return;
		}
		if ($activeRecordObject !== null && !is_a($activeRecordObject, 'SERIA_ActiveRecord'))
			throw new Exception('Not a SERIA_ActiveRecord object.');
		$this->activeRecordObject =& $activeRecordObject;
	}

	public function getActiveRecordObject()
	{
		return $this->activeRecordObject;
	}

	protected function requireEmptyObject()
	{
		if ($this->emptyActiveRecordObject === null) {
			$class_name = get_class($this);
			$obj = new $class_name();
			$glueToClassName = $obj->glueToClassName;
			if ($glueToClassName === null)
				throw new Exception('SERIA_FluentActiveRecord: You must ovverride the config attributes ($glueToClassName is missing)');
			$this->emptyActiveRecordObject = new $glueToClassName();
		}
	}

	public function static_getFieldSpec() // returns array() specifying rules for the columns
	{
		$this->requireEmptyObject();
		$columns = $this->emptyActiveRecordObject->getColumns();
		$spec = array();
		$defaultValidator = new SERIA_Validator(array(), false);
		foreach ($columns as $col) {
			if ($this->emptyActiveRecordObject->primaryKey == $col)
				continue;
			$spec[$col] = array(
				'caption' => $col,
				'fieldtype' => 'text',
				'validator' => $defaultValidator
			);
		}
		return $spec;
	}
	public function static_fluentSpec() // returns array('table' => '{tablename}', 'primaryKey' => 'id')
	{
		$this->requireEmptyObject();
		$tableName = $this->emptyActiveRecordObject->getTableName();
		$prefix = SERIA_PREFIX;
		$prefix_len = strlen($prefix);
		if (substr($tableName, 0, $prefix_len) == $prefix) {
			$tableName = substr($tableName, $prefix_len);
			if ($tableName[0] == '_')
				$tableName = substr($tableName, 1);
			$tableName = '{'.$tableName.'}';
		}
		return array('table' => $tableName, 'primaryKey' => $this->emptyActiveRecordObject->primaryKey);
	}
	public function static_fromDB($class_name, $row) // returns object
	{
		$this->requireEmptyObject();
		$plural = SERIA_ActiveRecord::getPlural($this->glueToClassName);
		$primaryKey = $this->emptyActiveRecordObject->primaryKey;
		$find_function = 'find_first_by_'.$primaryKey;
		if (isset($row[$primaryKey]) && ($record = call_user_func(array($plural, $find_function), $row[$primaryKey]))) {
			unset($row[$primaryKey]);
			$obj = new $class_name($record);
			foreach ($row as $nam => $val)
				$obj->set($nam, $val);
			return $obj;
		} else {
			/* Steal the object cache */
			$record = $this->emptyActiveRecordObject;
			$this->emptyActiveRecordObject = null;

			$obj = new $class_name($record);
			foreach ($row as $nam => $val)
				$obj->set($nam, $val);
			return $obj;
		}
	}

	public function toDB() // returns array
	{
		$row = array();
		$columns = $this->activeRecordObject->getColumns();
		foreach ($columns as $col)
			$row[$col] = $this->activeRecordObject->$col;
	}
	public function getKey() // returns the primary key for this row
	{
		return $this->activeRecordObject->primaryKey;
	}

	public function get($name) // returns the value from the field $name
	{
		$columns = $this->activeRecordObject->getColumns();
		if (!in_array($name, $columns)) {
			throw new Exception('Column name does not exist: '.$name);
		}
		return $this->activeRecordObject->$name;
	}
	public function set($name, $value) // sets $field = $value, must NEVER save the value immediately - only when ->save is called
	{
		$columns = $this->activeRecordObject->getColumns();
		if (!in_array($name, $columns)) {
			throw new Exception('Column name does not exist: '.$name);
		}
		$this->activeRecordObject->$name = $value;
	}

	public function isDeletable()
	{
		return true;
	}

	public function save()
	{
		return $this->activeRecordObject->save();
	}

	public static function getFieldSpec()
	{
		throw new Exception('Use ->createGlueClass due to PHP-OO limitations!');
	}
	public static function fluentSpec()
	{
		throw new Exception('Use ->createGlueClass due to PHP-OO limitations!');
	}
	public static function fromDB($row)
	{
		throw new Exception('Use ->createGlueClass due to PHP-OO limitations!');
	}

	/**
	 * Creates the glue-class that implements the SERIA_FluentObject with working static functions.
	 * For internal use mostly, please use ::createCompatLayer(...) when possible.
	 *
	 * @param unknown_type $className
	 * @return unknown_type
	 */
	public function createGlueClass($className=null)
	{
		$this_class = get_class($this);
		if ($className === null)
			$className = $this_class.'Glued';
		$classDef = '
class '.$className.' extends '.$this_class.'
{
	private static $obj = null;

	public static function getFieldSpec()
	{
		if (self::$obj === null)
			self::$obj = new '.$this_class.'();
		return self::$obj->static_getFieldSpec();
	}
	public static function fluentSpec()
	{
		if (self::$obj === null)
			self::$obj = new '.$this_class.'();
		return self::$obj->static_fluentSpec();
	}
	public static function fromDB($row)
	{
		if (self::$obj === null)
			self::$obj = new '.$this_class.'();
		return self::$obj->static_fromDB(\''.$className.'\', $row);
	}
}
		';
		eval($classDef);
	}

	/**
	 * Just call this function static on SERIA_FluentActiveRecord to create a new class that implements
	 * SERIA_FluentObject out of an SERIA_ActiveRecord class. Specify the name of the active-record class
	 * as a string, and the desired name of the fluent-object class as a string.
	 *
	 * @param unknown_type $activeRecordClass
	 * @param unknown_type $fluentClassName
	 * @return SERIA_FluentActiveRecord The (internal) glue object. This is a persistent signleton-object of a special glue-class of this compat-layer.
	 */
	public static function createCompatLayer($activeRecordClass, $fluentClassName)
	{
		$code = '
class '.$fluentClassName.'_ extends SERIA_FluentActiveRecord {
	protected $glueToClassName = \''.$activeRecordClass.'\';
}
$obj = new '.$fluentClassName.'_();
$obj->createGlueClass(\''.$fluentClassName.'\');
return $obj;
		';
		return eval($code);
	}
}
