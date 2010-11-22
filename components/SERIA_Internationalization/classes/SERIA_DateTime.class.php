<?php
	/**
	*	Simple class for working with date and time and localizing it
	*	for Seria Platform.
	*
	*	Todo:
	*	- Integrate with the user account so that a user can have a default date format.
	*	- If no user is logged in, support storing date formats in session
	*	- Use default date format
	*
	*	Know that:
	*	- Internally, Seria Platform should always work with unix timestamps.
	*	- Unix timestamps are the number of seconds since unix epoch 1970-01-01, not counting leap seconds
	*	and not correcting for daylight saving time.
	*	- Unix timestamp is the same at the same point in time, no matter which timezone you are in.
	*	- When outputting this class ensure that the user see his local time.
	*	- When parsing a user provided time written in user locale the timestamp will be correct - assuming
	*	that the server timezone is correct.
	*	- The database server should know which timezone you provide data to it with. Therefore this class
	*	will inform the SERIA_DB class of changes to the timezone. This means that it is safe to update
	*	the database by setting a datetime value formatted with this function.
	*/
	class SERIA_DateTime
	{
		protected static $userDateTime = 'Y-m-d H:i:s';
		protected static $userDate = 'Y-m-d';
		protected static $userTime = 'H:i:s';
		protected static $userTimezone = NULL;

		protected $_unixTime;

		public function __construct($unixTime)
		{
			$this->_unixTime = $unixTime;
		}

		public function render($format)
		{
			return date($format, $this->_unixTime);
		}

		/**
		*	Provide a timezone according to http://no.php.net/manual/en/timezones.php
		*/
		public static function setTimezone($timezone)
		{
			if(date_default_timezone_set($timezone))
			{
				SERIA_Hooks::dispatch('SERIA_DateTime::setTimezone', $timezone, date('Z'));
			}
			else
				throw new SERIA_Exception('Unknown timezone "'.$timezone.'".');
		}

		public static function setDateFormat($string)
		{
			self::$userDate = $string;
		}

		public static function setDateTimeFormat($string)
		{
			self::$userDateTime = $string;
		}

		public static function setTimeFormat($string)
		{
			self::$userTime = $string;
		}

		/**
		*	Returns a string representing the date and time according to user locale
		*	@return string
		*/
		public function renderUserDateTime()
		{
			return date(self::$userDateTime, $this->_unixTime);
		}

		/**
		*	Returns a string representing the time according to user locale
		*	@return string
		*/
		public function renderUserTime()
		{
			return date(self::$userTime, $this->_unixTime);
		}

		/**
		*	Returns a string representing the date according to user locale
		*	@return string
		*/
		public function renderUserDate()
		{
			return date(self::$userDate, $this->_unixTime);
		}

		public function getTimestamp()
		{
			return $this->_unixTime;
		}

		/**
		 *
		 * Parse a user supplied string to DateTime
		 * @return SERIA_DateTime
		 */
		public static function parseUserDateTime($dateTime)
		{
			return self::parse($dateTime, self::$userDateTime);
		}
		public static function parseFromDb($dbTime)
		{
			return new SERIA_DateTime(strtotime($dbTime));
		}

		/**
		*	@TODO: COmplete this function and test it!
		*	Parse most dates built according to the formats supported by the php date function.
		*
		*	If the format does not contain time information
		*
		*	@param string $data	Date matching the specified date format
		*	@param string $format	Format according to php.net/date
		*	@return SERIA_DateTime
		*/
		public static function parse($date, $format)
		{
			$parsed = self::_parse($format, $date);
			$d = array(date('H'),date('i'),date('s'),0,0,0);
			$v = array();
			$map = array('G' => 0,'H' => 0,'i' => 1,'s' => 2,'n' => 3,'m' => 3,'j' => 4,'d' => 4,'Y' => 5,'y' => 5,);
			foreach($parsed as $dk => $dv)
			{
				if(isset($map[$dk]))
					$v[$map[$dk]] = $dv;
				else
					throw new SERIA_Exception('Unable to parse the "'.$dk.'" token in the date format.');
			}
			for($i = 0; $i < 6; $i++)
			{
				if(isset($v[$i]))
					$d[$i] = $v[$i];
				else
					break;
			}
			$ts = call_user_func_array('mktime', $d);

			return new SERIA_DateTime($ts);	
		}
		static function _parse($format, $value)
		{
			$result = array();
			$c = array(
				'd'=>2, // two digits
				'D'=>false, // ignore	
				'j'=>true,
				'l'=>false,
				'N'=>false,
				'S'=>false,
				'w'=>false,
				'z'=>false,
				'W'=>true,
				// translate to date('m')
				'F'=>array('m', 'January'=>'01','February'=>'02','March'=>'03','April'=>'04','May'=>'05','June'=>'06','July'=>'07','August'=>'08','September'=>'09','October'=>'10','November'=>'11','December'=>'12'),
				'm'=>2,
				'M'=>array('m', 'Jan'=>'01','Feb'=>'02','Mar'=>'03','Apr'=>'04','May'=>'05','Jun'=>'06','Jul'=>'07','Aug'=>'08','Sep'=>'09','Oct'=>'10','Nov'=>'11','Dec'=>'12'),
				'n'=>array('m', '2'=>'02','3'=>'03','4'=>'04','5'=>'05','6'=>'06','7'=>'07','8'=>'08','9'=>'09','10'=>'10','11'=>'11','12'=>'12', '1'=>'01'),
				't'=>false,
				'L'=>false,
				'o'=>'Y',
				'Y'=>4,
				'y'=>2,
				'a'=>2,
				'A'=>2,
				'B'=>3,
				'g'=>true,
				'G'=>true,
				'h'=>2,
				'H'=>2,
				'i'=>2,
				's'=>2,
				'u'=>true,
				'e'=>false,
				'I'=>1,
				'O'=>5,
				'P'=>6,
				'T'=>3,
				'Z'=>false,
				'c'=>false,
				'r'=>false,
				'U'=>true,
			);

			$ov = 0;
			for($of = 0; $of < strlen($format); $of++)
			{
				if(isset($c[$format[$of]]))
				{
					$key = $format[$of];

					if($c[$key] === false)
						throw new SERIA_Exception('Unable to parse dates formatted with "'.$key.'".');
					else if($c[$key] === true)
					{ // unknown number of digits
						$number = "";
						for(; $ov<strlen($value); $ov++)
						{
							if(strpos('0123456789', $value[$ov])!==false)
							{ // 
								$number .= $value[$ov];
							}
							else break;
						}
						$ov++;
					}
					else if(is_int($c[$key]))
					{ // known number of digits
						$number = substr($value, $ov, $c[$key]);
						$ov += $c[$key];
					}
					else if(is_array($c[$key]))
					{
						$key = $c[$key][0];
						$skipped = false;
						foreach($c[$key] as $tKey => $tValue)
						{
							if($skipped===false) $skipped = true;
							else if(substr($value, $ov, strlen($tKey))==$tKey)
							{
								$number = $tValue;
								$ov += strlen($tValue);
								break;
							}
						}
					}
					else throw new SERIA_Exception('Nothing happened');

					$result[$key] = intval($number);
				}
				else
				{ // ignore this character
					$ov++;
				}
			}
			return $result;
		}
	}
