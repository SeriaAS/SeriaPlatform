<?php

class SERIA_Validator
{
	private $trim;
	const REQUIRED = 1;
	const MIN_LENGTH = 2;			// array(SERIA_Validator::MIN_LENGTH, 3[, 'Custom error message']);
	const CALLBACK = 3;			// array(SERIA_Validator::CALLBACK, $callback); // The callback will be called with arguments ($value, $object)
	const MAX_LENGTH = 4;			// array(SERIA_Validator::MAX_LENGTH, 50[, 'Custom error message']);
	const EMAIL = 5;			// array(SERIA_Validator::EMAIL[, 'Custom error message']);
	const LOCAL_DATE = 7;
	const LOCAL_TIME = 8;
	const INTEGER = 9;			// array(SERIA_Validator::INTEGER[, 'Custom error message']);
	const IP_ADDRESS = 10;  		// array(SERIA_Validator::IP_ADDRESS[, 'Custom error message']);
	const FLUENT_UNIQUE = 11;		// array(SERIA_Validator::FLUENT_UNIQUE[, 'Custom error message']);
	const FLUENT_OBJECT = 12;		// array(SERIA_Validator::FLUENT_OBJECT, 'classname'[, 'Custom error message']);
	const CURRENCY = 13;			// array(SERIA_Validator::CURRENCY[, 'Custom error message']);
	const ADDRESS = 14;			// array(SERIA_Validator::ADDRESS[, 'Custom error message']);
	const PHONE = 15;			// array(SERIA_Validator::PHONE[, 'Custom error message']);
	const URL = 16;				// array(SERIA_Validator::URL[, 'Custom error message']);
	const COUNTRYCODE = 17;			// array(SERIA_Validator::COUNTRYCODE[, 'Custom error message']);
	const MIN_VALUE = 18;			// array(SERIA_Validator::MIN_VALUE, 0[, 'Custom error message']);
	const ONE_OF = 19;			// array(SERIA_Validator::ONE_OF, <array of values>[, 'Custome error message']);
	const REQUIRED_CHARS = 20;		// array(SERIA_Validator::REQUIRED_CHARS, <array of chars>[, 'Custom error message']);
	const META_UNIQUE = 21;			// array(SERIA_Validator::META_UNIQUE[, 'Custom error message']);
	const META_OBJECT = 22;			// array(SERIA_Validator::META_OBJECT, 'classname'[, 'Custom error message']);
	const META_OBJECT_WRONG_CLASS = 'meta_object_wrong_class';	// returned by META_OBJECT validator if the value is an instance, and the class is wrong
	const META_OBJECT_INVALID = 'meta_object_invalid';		// returned by META_OBJECT validator if the value is an instance, and if it does not validate
	const META_OBJECT_INSTANCE = 'meta_object_instance';		// returned by META_OBJECT validator if receiving an instance. Should receive an ID or a MetaReference (classname:id)
	const MAX_VALUE = 23;			// array(SERIA_Validator::MAX_VALUE, 100[, 'Custom error message']);
	const HOSTNAME = 24;			// array(SERIA_Validator::HOSTNAME[, 'Custom error message']);
	const FLOAT = 25;			// array(SERIA_Validator::FLOAT[, 'Custom error message']);
	const LEGAL_CHARS = 26;			// array(SERIA_Validator::LEGAL_CHARS, <array of chars>[, 'Custom error message']);
	const INTERNET_MEDIA_TYPE = 27;		// array(SERIA_Validator::INTERNET_MEDIA_TYPE[, 'Custom error message']);
	const RTMP_URL = 28;			// array(SERIA_Validator::RTMP_URL[, 'Custom error message']);
	const TIMEZONE = 29;			// array(SERIA_Validator::TIMEZONE[, 'Custom error message']);
	const CURRENCYCODE = 30;		// array(SERIA_Validator::CURRENCYCODE[, 'Custom error message']);
	const FILEPATH = 31;			// array(SERIA_Validator::FILEPATH[, 'Custom error message']);
	const SLUG = 32;			// array(SERIA_Validator::SLUG[, 'Custom error message']);
	const ISODATE = 33;			// array(SERIA_Validator::ISODATE[, 'Custom error message']);
	const ISOTIME = 34;			// array(SERIA_Validator::ISOTIME[, 'Custom error message']);
	const ISODATETIME = 35;			// array(SERIA_Validator::ISODATETIME[, 'Custom error message']);
	const RTSP_URL = 36;			// array(SERIA_Validator::RTSP_URL[, 'Custom error message']);
	const RTP_URL = 37;			// array(SERIA_Validator::RTP_URL[, 'Custom error message']);
	const HTTP_URL = 38;			// array(SERIA_Validator::HTTP_URL[, 'Custom error message']);

	function __construct($rules, $trimTheValue = true)
	{
		$this->rules = $rules;
		$this->trim = $trimTheValue;
	}

	function addRule($rule)
	{
		$this->rules[] = $rule;
	}

	function hasRule($rule)
	{
		foreach($this->rules as $tRule)
			if($tRule[0] == $rule)
				return true;
		return false;
	}

	function isInvalid($value, $extra=false)
	{
		if($this->trim && !is_object($value)) $value = trim($value);
		foreach($this->rules as $rule)
		{
			if($rule[0]!==self::REQUIRED && empty($value))
				continue;

			switch($rule[0])
			{
				case self::REQUIRED :
					if(empty($value) && $value !== 0 && $value !== '0')
						return _t("Required");
					break;
				case self::MIN_LENGTH:
					if (mb_strlen($value) < $rule[1])
						return _t("Minimum length: %LENGTH%", array('LENGTH' => $rule[1]));
					break;
				case self::CALLBACK:
					$args = $rule;
					array_shift($args);
					$func = array_shift($args);
					$args[] = $value;
					$args[] = $extra; 
					$err = call_user_func_array($func, $args);
					if ($err)
						return $err;
					break;
				case self::MAX_LENGTH:
					if (mb_strlen($value) > $rule[1])
						return _t("Maximum length: %LENGTH%", array('LENGTH' => $rule[1]));
					break;
				case self::EMAIL:
					$err = SERIA_IsInvalid::email($value);
					if ($err)
						return isset($rule[1]) ? $rule[1] : $err;
					break;
				case self::LOCAL_DATE:
					$err = SERIA_IsInvalid::localDate($value);
					if ($err)
						return isset($rule[1]) ? $rule[1] : $err;
					break;
				case self::LOCAL_TIME:
					$err = SERIA_IsInvalid::localTime($value);
					if ($err)
						return isset($rule[1]) ? $rule[1] : $err;
					break;
				case self::INTEGER:
					if($value !== '' && (!is_numeric($value) || !(intval($value) == trim($value))))
						return isset($rule[1]) ? $rule[1] : _t('Only use integer values');
					break;
				case self::FLOAT:
					if($value !== '' && (!is_numeric($value) || !(floatval($value) == trim($value))))
						return isset($rule[1]) ? $rule[1] : _t('Only numbers allowed');
					break;
				case self::IP_ADDRESS:
					$parts = explode('.', $value);
					if(sizeof($parts)!=4)
						return isset($rule[1]) ? $rule[1] : _t('Invalid IP-address');
					foreach($parts as $part)
					{
						if(intval($part)!=$part)
							return isset($rule[1]) ? $rule[1] : _t('Invalid IP-address');
						if(intval($part)<0 || intval($part)>255)
							return isset($rule[1]) ? $rule[1] : _t('Invalid IP-address');
					}
					break;
				case self::FLUENT_UNIQUE:
					if(!$extra)
						throw new Exception('Unable to validate FLUENT_UNIQUE without $extra = array("object"=>$object, "field"=>$fieldName)');

					$fls = call_user_func(array(get_class($extra['object']),'fluentSpec'));

					$table = $fls['table'];
					$pk = $fls['primaryKey'];
					$key = $extra['object']->getKey();

					$sql = 'SELECT 1 FROM '.$table.' WHERE '.$extra['field'].'=:value';
					$dbParams = array('value' => $value);

					if($key)
					{
						$sql .= ' AND '.$pk.'<>:key';
						$dbParams['key'] = $key;
					}

					if(SERIA_Base::db()->query($sql, $dbParams)->fetch(PDO::FETCH_COLUMN, 0))
						return isset($rule[1]) ? $rule[1] : _t('Value must be unique');
					break;
				case self::FLUENT_OBJECT:
					eval('$spec = '.$rule[1].'::fluentSpec();');
					$c = SERIA_Base::db()->query('SELECT 1 FROM '.$spec['table'].' WHERE '.$spec['primaryKey'].'=:value', array('value' => $value))->fetch(PDO::FETCH_COLUMN);
					if(!$c)
						return isset($rule[2]) ? $rule[2] : _t('Not found');
					break;
				case self::CURRENCY:
					$parts = explode(".", $value);
					if(sizeof($parts)<=2)
					{
						$null = trim(ltrim($value, '-'), '0123456789.');
						if(empty($null))
							break;
					}
					return isset($rule[1]) ? $rule[1] : _t("Invalid value. Expected '123.45'");
					break;
				case self::ADDRESS:
					$parts = explode("\n", $value);
					foreach($parts as $line)
					{
						$parts[$key] = trim($line);
						if(empty($line))
							return isset($rule[1]) ? $rule[1] : _t("Invalid address. Blank lines are not allowed.");
					}
					if(sizeof($parts)>6)
						return isset($rule[1]) ? $rule[1] : _t("An address can consist of at most 6 lines.");
					break;
				case self::PHONE:
					$err = SERIA_IsInvalid::phone($value);
					if ($err)
						return isset($rule[1]) ? $rule[1] : $err;
					break;
				case self::URL:
					$err = SERIA_IsInvalid::url($value);
					if($err) return isset($rule[1]) ? $rule[1] : $err;
					break;
				case self::HTTP_URL:
					$err = SERIA_IsInvalid::url($value);
					if($err) return isset($rule[1]) ? $rule[1] : $err;
					break;
				case self::RTMP_URL:
					$err = SERIA_IsInvalid::url($value);
					if($err) return isset($rule[1]) ? $rule[1] : $err;
					$schema = substr($value, 0, strpos($value, '://'));
					if($schema!=='rtmp' && $schema!=='rtmpt' && $schema!=='rtmps')
						return isset($rule[1]) ? $rule[1] : _t("RTMP urls must have a rtmp://, rtmpt:// or rtmps:// schema");
					break;
				case self::RTP_URL:
					$err = SERIA_IsInvalid::url($value);
					if($err) return isset($rule[1]) ? $rule[1] : $err;
					$schema = substr($value, 0, strpos($value, '://'));
					if($schema!=='rtp')
						return isset($rule[1]) ? $rule[1] : _t("RTP urls must have an rtp:// schema").'Tried: '.$value;
					break;
				case self::RTSP_URL:
					$err = SERIA_IsInvalid::url($value);
					if($err) return isset($rule[1]) ? $rule[1] : $err;
					$schema = substr($value, 0, strpos($value, '://'));
					if($schema!=='rtsp')
						return isset($rule[1]) ? $rule[1] : _t("RTSP urls must have a rtsp://");
					break;
				case self::COUNTRYCODE:
					$dictionary = SERIA_Dictionary::getDictionary('iso-3166');
					if(!isset($dictionary[$value]))
						return isset($rule[1]) ? $rule[1] : _t("No such country code as '%code%'.", array('code' => $value));
					break;
				case self::MIN_VALUE:
					if($value < $rule[1])
						return isset($rule[2]) ? $rule[2] : _t("Minimum value is %VALUE%.", array('VALUE'=>$rule[1]));
					break;
				case self::MAX_VALUE:
					if($value > $rule[1])
						return isset($rule[2]) ? $rule[2] : _t("Maximum value is %VALUE%.", array('VALUE'=>$rule[1]));
					break;
				case self::ONE_OF:
					$t = array_flip($rule[1]);
					if(!isset($t[$value]))
						return isset($rule[2]) ? $rule[2] : _t("Must be one of %VALUES%.", array('VALUES' => implode(", ", $rule[1])));
					break;
				case self::LEGAL_CHARS:
					$found = false; // found illegal chars?
					foreach($rule[1] as $char)
					{
						if(mb_strpos($value, $char)===false)
						{
							$found = true;
							break;
						}
					}
					if(!$found)
						return isset($rule[2]) ? $rule[2] : _t("Only these characters are allowed: %CHARS%", array('CHARS' => implode('', $rule[2])));
					break;
				case self::REQUIRED_CHARS:
					$found = false;

					$l = mb_strlen($rule[1]);
					for($i = 0; $i < $l; $i++)
					{
						if(mb_strpos($value, mb_substr($rule[1], $i, 1))!==false)
						{
							$found = true;
							break;
						}
					}
					if(!$found) {
						return isset($rule[2]) ? $rule[2] : _t("Must include at least one of these characters: %CHARS%", array('CHARS' => implode('', $rule[2])));
					}
					break;
				case self::META_UNIQUE:
					if(!$extra)
						throw new Exception('Unable to validate META_UNIQUE without $extra = array("object"=>$object, "field"=>$fieldName)');

					$spec = SERIA_Meta::_getSpec($extra['object']);

					$table = $spec['table'];
					$pk = $spec['primaryKey'];
					$row = $extra['object']->MetaBackdoor('get_row');
					$key = $row[$pk];

					$sql = 'SELECT 1 FROM '.$table.' WHERE '.$extra['field'].'=:value';
					$dbParams = array('value' => $value);

					if($key)
					{
						$sql .= ' AND '.$pk.'<>:key';
						$dbParams['key'] = $key;
					}

					if(!empty($rule[2]))
					{
						$sql .= ' AND '.$rule[2].'=:subsetValue';
						$dbParams['subsetValue'] = $row[$rule[2]];
					}

					if(SERIA_Base::db()->query($sql, $dbParams)->fetch(PDO::FETCH_COLUMN, 0))
						return isset($rule[1]) && $rule[1] ? $rule[1] : _t('Value must be unique');
					break;
				case self::META_OBJECT:
					if(is_object($value))
					{ // if receiving an object, always return an error
						if($rule[1]==get_class($value) || is_subclass_of($value, $rule[1]))
						{
							if(SERIA_Meta::validate($value)!==false)
								return self::META_OBJECT_INVALID;
							return self::META_OBJECT_INSTANCE;
						}
						else
						{
							return self::META_OBJECT_WRONG_CLASS;
						}

					}
					else if($rule[1] == 'SERIA_MetaObject')
					{
						$parts = explode(":", $value);
						if(!(sizeof($parts)==2 && class_exists($parts[0]) && is_subclass_of($parts[0], 'SERIA_MetaObject')))
							return isset($rule[2]) ? $rule[2] : _t('Invalid MetaObject reference');
						$spec = SERIA_Meta::_getSpec($parts[0]);
						$c = SERIA_Base::db()->query('SELECT 1 FROM '.$spec['table'].' WHERE '.$spec['primaryKey'].'=:value', array('value' => $parts[1]))->fetch(PDO::FETCH_COLUMN);
						if(!$c)
						{
							return isset($rule[2]) ? $rule[2] : _t('Not found');
						}
					}
					else
					{
						$spec = SERIA_Meta::_getSpec($rule[1]);
						$c = SERIA_Base::db()->query('SELECT 1 FROM '.$spec['table'].' WHERE '.$spec['primaryKey'].'=:value', array('value' => $value))->fetch(PDO::FETCH_COLUMN);
						if(!$c)
							return isset($rule[2]) ? $rule[2] : _t('Not found');
					}
					break;
				case self::HOSTNAME:
					if(strpos($value, '/')!==false)
						return isset($rule[1]) ? $rule[1] : _t("Invalid characters in hostname");

					if(!filter_var('http://'.$value.'/', FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED))
                                		return isset($rule[1]) ? $rule[1] : _t('Invalid hostname');

					break;
				case self::INTERNET_MEDIA_TYPE:
					$parts = explode("/", $value);
					if(sizeof($parts)!=2)
						return isset($rule[1]) ? $rule[1] : _t("Use the format 'type/subtype'.");

					switch($parts[0])
					{
						case "application" :
						case "audio" :
						case "image" :
						case "message" :
						case "model" :
						case "multipart" :
						case "text" :
						case "video" :
						case "application" :
							break;
						default :
							return isset($rule[1]) ? $rule[1] : _t("Main type must be one of application, audio, image, message, model, multipart, text, video or application.");
					}
					break;
				case self::TIMEZONE :
					$dictionary = new SERIA_TimezoneDictionary();
					if(!isset($dictionary[$value]))
						return isset($rule[1]) ? $rule[1] : _t("Invalid timezone.");
					break;
				case self::CURRENCYCODE:
					$dictionary = SERIA_Dictionary::getDictionary('iso-4217');
					if(!isset($dictionary[$value]))
						return isset($rule[1]) ? $rule[1] : _t("No such currency code.");
					break;
				case self::FILEPATH:
					$illegalChars = ":&|><*?\"'";
					$parts = explode("/", str_replace('\\', '/', $value));
					foreach($parts as $part)
						if(empty($part))
							return isset($rule[1]) ? $rule[1] : _t("Problem with path delimiters in %PATH%.", array('PATH' => $value));
					$l = strlen($illegalChars);
					foreach($parts as $part)
					{
						if(!ctype_print($part))
							return isset($rule[1]) ? $rule[1] : _t("Unprintable characters in path component.");
						for($i = 0; $i < $l; $i++)
							if(strpos($part, $illegalChars[$i])!==false)
								return isset($rule[1]) ? $rule[1] : _t("Illegal character %CHAR% in path component.", array('CHAR' => $illegalChars[$i]));
					}
					break;
				case self::SLUG:
					if($value != SERIA_Sanitize::slug($value))
						return isset($rule[1]) ? $rule[1] : _t("Invalid slug. A slug is lowercase characters, and dashes in place of spaces.");
					break;
				case self::ISODATE: // check taht format is exactly YYYY-MM-DD
					if(date('Y-m-d', strtotime($value)) != $value)
						return isset($rule[1]) ? $rule[1] : _t("Invalid date. Expected format is a standard ISO date YYYY-MM-DD.");
					break;
				case self::ISOTIME: // check taht format is exactly HH:MM:SS
					if(date('H:i:s', strtotime($value)) != $value && date('H:i', strtotime($value)) != $value)
						return isset($rule[1]) ? $rule[1] : _t("Invalid time. Expected format is a standard ISO time HH:MM in 24-hour time.");
					break;
				case self::ISODATETIME: // check taht format is exactly HH:MM:SS
					if(date('Y-m-d H:i:s', strtotime($value)) != $value && date('Y-m-d H:i', strtotime($value)) != $value)
						return isset($rule[1]) ? $rule[1] : _t("Invalid date/time. Expected format is a standard ISO date time YYYY-MM-DD HH:MM.");
					break;
			}
		}
		return false;
	}
}
