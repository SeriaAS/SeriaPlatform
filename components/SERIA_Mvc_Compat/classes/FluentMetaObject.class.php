<?php

/**
 * A compat layer: A fluent-access object that operates on a meta-object.
 *
 * @author Jan-Espen Pettersen
 *
 */
class FluentMetaObject implements SERIA_IFluentAccess
{
	protected $metaObject;

	/**
	 * Copied from SERIA_Meta
	 * @var array
	 */
	private static $_specCache = array();

	/**
	 * Copied from SERIA_Meta and slightly modified..
	 *
	 * @param $item
	 * @return unknown_type
	 */
	public /*package*/ static function _getSpec($item)
	{
		if(is_object($item))
			return self::_getSpec(get_class($item));

		if(isset(self::$_specCache[$item]))
			return self::$_specCache[$item];


		/* --- Added SERIA_IMetaCompatibleObject --- */
		if(!is_subclass_of($item, 'SERIA_MetaObject') && !is_subclass_of($item, 'MetaCompatibleObject'))
		{
			// Support _getSpec for deprecated fluent objects
			/* --- Removed --- */
			/*if(is_subclass_of($item, 'SERIA_FluentObject') || in_array('SERIA_IFluentObject', class_implements($item)))
				return self::_getSpecFromFluent($item);*/
			throw new SERIA_Exception('Class "'.$item.'" must extend SERIA_MetaObject');
		}

		$spec = call_user_func(array($item, 'Meta'));

		// REWRITE SPEC TO SUPPORT TEMPLATES FOR FIELDS ETC.

		if(!isset($spec['table']))
		{
			throw new SERIA_Exception('Database table not specified using \'table\' in Meta for '.$item);
		}

		if(!isset($spec['primaryKey']))
			$spec['primaryKey'] = 'id';

		if(!isset($spec['fields'][$spec['primaryKey']]))
			$spec['fields'] = array_merge(array($spec['primaryKey'] => self::_getMappedFieldSpec('primaryKey')), $spec['fields']);

		foreach($spec['fields'] as $key => $info)
		{
			if(!is_array($info))
			{ // $spec['fields']['myField'] = 'createdBy';
				switch($info)
				{
					case "createdBy" : case "modifiedBy" :
						$spec['fields'][$key] = self::_getMappedFieldSpec('SERIA_User');
						$spec['fields'][$key]['caption'] = ($info==='createdBy' ? _t("Registered by") : _t('Modified by'));
						$spec['fields'][$key]['special'] = $info;
						break;
					case "createdDate" : case "modifiedDate" :
						$spec['fields'][$key] = self::_getMappedFieldSpec('datetime');
						$spec['fields'][$key]['caption'] = ($info==='createdDate' ? _t("Registration") : _t('Modification'));
						$spec['fields'][$key]['special'] = $info;
						break;
					case "isEnabled" :
						$spec['fields'][$key] = self::_getMappedFieldSpec('boolean');
						$spec['fields'][$key]['caption'] = _t("Enabled");
						$spec['fields'][$key]['special'] = $info;
						break;
					default :
						throw new SERIA_Exception("Unknown special type '$info'.");
				}
			}
			else if(isset($info[0]))
			{ // $spec['fields']['myField'] = array('name required', _t('Name:'));
				$tokens = explode(" ", trim($info[0]));
				$spec['fields'][$key] = self::_getMappedFieldSpec($tokens[0]);
				unset($tokens[0]);
				foreach($tokens as $token)
				if(isset($token))
				{
					switch(strtolower($token))
					{
						case 'unique' :
							$spec['fields'][$key]['validator']->addRule(array(SERIA_Validator::META_UNIQUE));
							break;
						case 'required' :
							$spec['fields'][$key]['validator']->addRule(array(SERIA_Validator::REQUIRED));
							break;
						default :
							if(substr(strtolower($token), 0, 7)==="unique(")
							{
								$name = substr($token, 7, strpos($token, ")")-7);
								if(isset($spec['fields'][$name]))
								{
									$spec['fields'][$key]['validator']->addRule(array(SERIA_Validator::META_UNIQUE, NULL, $name));
									break;
								}
								else
								{
									throw new SERIA_Exception("Unknown column '".$name."' in UNIQUE spec for column '".$tokens[1]."'.");
								}
							}
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

			if(isset($spec['fields'][$key]['class']))
			{
				if(in_array('SERIA_IMetaField', class_implements($spec['fields'][$key]['class'])))
				{
					$info = call_user_func(array($spec['fields'][$key]['class'], 'MetaField'));
					foreach($info as $k => $v)
					{
						if(!isset($spec['fields'][$key][$k]))
							$spec['fields'][$key][$k] = $v;
					}
				}
			}
		}


		return self::$_specCache[$item] = $spec;
	}

	/**
	 * Copied from SERIA_Meta
	 *
	 * @param unknown_type $specName
	 * @return unknown_type
	 */
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
					"fieldtype" => "text",
					"type" => "varchar(50)",
					"validator" => new SERIA_Validator(array(
						array(SERIA_Validator::MIN_LENGTH, 5),
						array(SERIA_Validator::MAX_LENGTH, 50),
						array(SERIA_Validator::REQUIRED_CHARS, 'abcdefghijklmnopqrstuvwxyz', _t("Passwords must consist of numbers and upper- and lowercase english characters.")),
						array(SERIA_Validator::REQUIRED_CHARS, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', _t("Passwords must consist of numbers and upper- and lowercase english characters.")),
						array(SERIA_Validator::REQUIRED_CHARS, '0123456789', _t("Passwords must consist of numbers and upper- and lowercase english characters.")),
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
					"values" => SERIA_Dictionary::getDictionary('iso-3166'),
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
			case "ip" :
				return array(
					"fieldtype" => "text",
					"type" => "varchar(20)",
					"validator" => new SERIA_Validator(array(array(SERIA_Validator::IP_ADDRESS))),
				);
			case "boolean" :
				return array(
					"fieldtype" => "checkbox",
					"type" => "tinyint(1)",
					"validator" => new SERIA_Validator(array(array(SERIA_Validator::ONE_OF, array(0,1)))),
				);
			case "datetime" :
				return array(
					"class" => 'SERIA_DateTimeMetaField',
				);
			default :
				if(class_exists($specName))
				{
					return array(
						"fieldtype" => $specName,
						"type" => "int",
						"validator" => new SERIA_Validator(array(array(SERIA_Validator::META_OBJECT, $specName))),
					);
				}
				throw new SERIA_Exception('Unknown field type "'.$specName.'".');
		}	
	}

	public function __construct(&$metaObject)
	{
		if ($metaObject instanceof SERIA_MetaObject || $metaObject instanceof MetaCompatibleObject)
			$this->metaObject =& $metaObject;
		else
			throw new SERIA_Exception('Expected a meta-compatible object!');
	}

	public static function getFieldSpec($metaClass)
	{
		$meta = self::_getSpec($metaClass);
		return $meta['fields'];
	}
	public static function getFormSpec($metaClass, $spec=false)
	{
		if ($spec === false) {
			/* all */
			$meta = self::_getSpec($metaClass);
			return $meta['fields'];
		} else {
			/* only specified */
			$meta = self::_getSpec($metaClass);
			foreach ($spec as $name => &$params) {
				if (isset($meta['fields'][$name]))
					$params = array_merge($meta['fields'][$name], $params);
			}
			return $spec;
		}
	}

	public function set($name, $value)
	{
		$this->metaObject->set($name, $value);
	}
	public function get($name)
	{
		return $this->metaObject->get($name);
	}

	public function save()
	{
		if (method_exists($this->metaObject, 'save'))
			$this->metaObject->save();
		else
			SERIA_Meta::save($this->metaObject);
	}

	public function delete()
	{
		if (method_exists($this->metaObject, 'delete'))
			$this->metaObject->delete();
		else
			SERIA_Meta::delete($this->metaObject);
	}
}
