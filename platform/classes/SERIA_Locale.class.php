<?php
	class SERIA_Locale
	{
		private static $dateLocale = false;

		private $emuMode = false;

		/**
		 * Enter description here...
		 *
		 * @param unknown_type $dateLocaleObject
		 */
		public static function setLocale($dateLocaleObject)
		{
			self::$dateLocale = $dateLocaleObject;
		}
		
		/**
		 * @return SERIA_Locale
		 */
		public static function getLocale()
		{
			if (self::$dateLocale === false)
				self::$dateLocale = new SERIA_Locale(SERIA_LOCALE_PLATFORMTIME);
			return self::$dateLocale;
		}

		function __construct($platformTimecode=false)
		{
			if (!$platformTimecode)
				$this->emuMode = !function_exists("strptime");
			else
				$this->emuMode = true;
		}

		/**
		 * Converts from locale time string $str to a UNIX-timestamp which is returned.
		 *
		 * $type can be one of:
		 *  'date'     => to/from date (YYYY-MM-DD / whatever..)
		 *  'datetime' => to/from date and time (YYYY-MM-DD HH:II:SS / whatever..)
		 *  'time'     => to/from time (HH:II:SS / whatever..)
		 * 		 *
		 * @param string $str
		 * @param string $type
		 * @param int $refTS
		 * @return int UNIX timestamp
		 */
		public function stringToTime($str, $type=false, $refTS=false)
		{
			if (!$type || $type === 'date')
				$typeTime = '%x';
			else if ($type === 'datetime')
				$typeTime = '%x %H:%M';
			else if ($type === 'time') {
				$typeTime = '%X';
				if ($refTS === false)
					$refTS = time();
			} else if ($type === 'datetimenosec') {
				$typeTime = '%x %H:%M';
			} else if ($type === 'timenosec') {
				$typeTime = '%H:%M';
				if ($refTS === false)
					$refTS = time();
			} else
				return false;
			if(!$this->emuMode)
				$parsed = strptime($str, $typeTime);
			else /* Ja, da ønsker vi velkommen til Microsoft Window..uh*BANG* *silence* */
				$parsed = self::emu_strptime($str, $typeTime);
			if (parsed !== false) {
				if ($type === 'time' || $type === 'timenosec') {
					$parsed['tm_year'] = intval(date('Y', $refTS)) - 1900;
					$parsed['tm_mon'] = intval(date('m', $refTS)) - 1;
					$parsed['tm_mday'] = intval(date('d', $refTS));
				}
				return mktime($parsed["tm_hour"], $parsed["tm_min"], $parsed["tm_sec"], $parsed["tm_mon"] + 1, $parsed["tm_mday"], $parsed["tm_year"] + 1900);
			} else
				return $parsed;
		}

		/**
		 * Converts from UNIX-timestamp to locale date string.
		 *
		 * $type can be one of:
		 *  'date'     => to/from date (YYYY-MM-DD / whatever..)
		 *  'datetime' => to/from date and time (YYYY-MM-DD HH:II:SS / whatever..)
		 *  'time'     => to/from time (HH:II:SS / whatever..)
		 * 		 *
		 * @param int $tm
		 * @param string $type
		 * @return string
		 */
		public function timeToString($tm, $type=false)
		{
			if (!$type || $type === 'date')
				$typeTime = '%x';
			else if ($type === 'datetime')
				$typeTime = '%x %X';
			else if ($type === 'time')
				$typeTime = '%X';
			else if ($type === 'datetimenosec')
				$typeTime = '%x %H:%M';
			else if ($type === 'timenosec')
				$typeTime = '%H:%M';
			else
				return false;
			if(!$this->emuMode)
				return strftime($typeTime, $tm);
			else
				return self::emu_strftime($typeTime, $tm);
		}

		/**
		 * Converts from UNIX-timestamp to ISO format which will work in SQL.
		 *
		 * @param int $ts
		 * @return string
		 */
		public function timeToSQL($ts)
		{
			return date('Y-m-d H:i:s', $ts);
		}

		/**
		 * Converts from ISO (SQL format) to UNIX-timestamp.
		 *
		 * @param string $strtm
		 * @return int
		 */
		public function SQLToTime($strtm)
		{
			return $this->strtotime($strtm);
		}
		
		/**
		 * Converts from SQL format to nice string representing difference from
		 * current time.
		 * 
		 * @param string $strtm String time from Sql
		 * @return string
		 */
		public function sqlToTimeDiff($strtm) {
			$time = $this->sqlToTime($strtm);
			
			if ($time <= time()) {
				if ($time > time() - 45) {
					return _t('%SECONDS% seconds ago', array('SECONDS' => time() - $time));
				} elseif ($time > time() - 90) {
					return _t('About a minute ago');
				} elseif ($time > time() - (60 * 45)) {
					return _t('%MINUTES% minutes ago', array('MINUTES' => round((time() - $time) / 60)));
				} elseif ($time > time() - (60 * 90)) {
					return _t('About an hour ago');
				} elseif ($time > time() - (60 * 60 * 18)) {
					return _t('%HOURS% hours ago', array('HOURS' => round((time() - $time) / 60 / 60)));
				} elseif ($time > time() - (60 * 60 * 36)) {
					return _t('About a day ago');
				} elseif ($time > time() - (60 * 60 * 24 * 5)) {
					return _t('About %DAYS% days ago', array('DAYS' => round((time() - $time) / 60 / 60 / 24)));
				} elseif ($time > time() - (60 * 60 * 24 * 9)) {
					return _t('About a week ago');
				}
				
			}
			
			return $this->timeToString($time);
		}
		
		/**
		 * Converts from locale date string to ISO format
		 */
		public function stringToSQL($string)
		{
			return $this->timeToSQL($this->stringToTime($string, "datetime"));
		}
		
		/**
		 * Converts from ISO to locale
		 */
		public function sqlToString($isotime)
		{
			return $this->timeToString($this->sqlToTime($isotime));
		}

		/*
		 * TODO: Implement locales in windows (if you wish)...
		 */
		private function emu_strftime_x($tm, $timeofday=false)
		{
			if (!$timeofday)
				return date("d.m.Y", $tm);
			else
				return date("d.m.Y H:i:s", $tm);
		}
		private function emu_strftime_uX($tm)
		{
			return date("H:i:s", $tm);
		}
		private function emu_strftime_uH($tm)
		{
			return date('H', $tm);
		}
		private function emu_strftime_uM($tm)
		{
			return date('i', $tm);
		}
		private function parse_number($str)
		{
			$i = 0;
			$num = 0;
			$len = strlen($str);
			while ($i < $len && ("".intval($str[$i])) == $str[$i]) {
				$num *= 10;
				$num += intval($str[$i]);
				$i++;
			}
			$unparsed_characters = $len - $i;
			$unparsed = substr($str, $i, $unparsed_characters);
			return array("number" => $num, "unparsed" => $unparsed, "consumed" => $i);
		}
		private function valid_number($str, $max_len=false)
		{
			if ($str === false || $str == "")
				return false;
			if ($max_len !== false && strlen($str) > $max_len)
				return false;
			$parsed = self::parse_number($str);
			return ($parsed["consumed"] == strlen($str));
		}
		private function emu_strptime_revx(&$str, &$decode, $timeofday=false)
		{
			if ($timeofday) {
				list($date, $time) = explode(" ", $str, 2);
				$time = ltrim($time, " ");
			} else
				$date = $str;
			list($day, $month, $year) = explode(".", $date, 3);
			if (!self::valid_number($day, 2) || !self::valid_number($month, 2) || $year === false || $year == "")
				return false;
			$day = intval($day);
			$month = intval($month);
			$parsed = self::parse_number($year);
			$unparsed = $parsed["unparsed"];
			$fourDigitYear = $parsed["consumed"] > 2;
			$year = $parsed["number"];
			if ($timeofday) {
				if ($unparsed != "")
					return false;
				list($hour, $min, $sec) = explode(":", $time);
				if (!self::valid_number($hour, 2) || !self::valid_number($min, 2))
					return false;
				$parsed = self::parse_number($sec);
				if ($parsed["consumed"] <= 2) {
					$sec = $parsed["number"];
					$unparsed = $parsed["unparsed"];
				} else {
					$sec = substr($sec, 0, 2);
					$parsed = self::parse_number($sec);
					if ($parsed["consumed"] != 2)
						throw new Exception(_t("Number parsing of second revealed some unexpected condition (BUG)"));
					$sec = $parsed["number"];
					$unparsed = substr($sec, 2);
				}
				$hour = intval($hour);
				$min = intval($min);
				$sec = intval($sec);
				if ($hour < 0 || $min < 0 || $sec < 0 || $hour > 23 || $min > 59 || $sec > 60)
					return false;
			}
			if (!$fourDigitYear) {
				/* Examples (code following):
				 *  1: $year = 99 in 2008
				 *  2: $year = 98 in 1999
				 *  3: $year = 07 in 1999
				 *  4: $year = 08 in 2008
				 */
				/* 1: $thy = 2008
				 * 2: $thy = 1999
				 * 3: $thy = 1999
				 * 4: $thy = 2008
				 */
				$thy = intval(date('Y'));
				/* 1: $tdy = 8
				 * 2: $tdy = 99
				 * 3: $tdy = 99
				 * 4: $tdy = 8
				 */
				$tdy = $thy % 100;
				/* 1: $cent = 2000
				 * 2: $cent = 1900
				 * 3: $cent = 1900
				 * 4: $cent = 2000
				 */
				$cent = $thy - $tdy;
				/* 1: $tby = 38
				 * 2: $tby = 29
				 * 3: $tby = 29
				 * 4: $tby = 38
				 */
				$tby = ($tdy + 30) % 100; /* boundary for two to four digit conv */
				/* 1: true
				 * 2: false
				 * 3: false
				 * 4: true
				 */
				if ($tby > $tdy) {
					/* 1: false
					 * 4: true
					 */
					if ($year <= $tby)
						$year += $cent; /* 4: $year = 2008 */
					else
						$year += $cent - 100; /* 1: $year = 1999 */
				} else {
					/* 2: false
					 * 3: true
					 */
					if ($year <= $tby)
						$year += $cent + 100; /* 3: $year = 2007 */
					else
						$year += $cent; /* 2: $year = 1998 */
				}
			}
			$year = intval($year);
			
			if ($month < 1 || $month > 12 || $day < 1 || $day > 31)
				return false;

			$day = substr("0".$day, -2);
			$month = substr("0".$month, -2);
			if ($timeofday) {
				$hour = substr("0".$hour, -2);
				$min = substr("0".$min, -2);
				$sec = substr("0".$sec, -2);
				$ts = strtotime("$year-$month-$day $hour:$min:$sec");
			} else {
				$ts = strtotime("$year-$month-$day");
			}

			if (intval(date('Y', $ts)) != $year ||
			    intval(date('m', $ts)) != $month ||
			    intval(date('d', $ts)) != $day)
				return false;

			/* Can't find anything wrong with the date */
			$str = $unparsed;
			$decode["tm_year"] = $year - 1900;
			$decode["tm_mon"] = $month - 1;
			$decode["tm_mday"] = $day;
			$decode["tm_wday"] = date('w', $tm);
			$decode["tm_yday"] = date('z', $tm);
			if ($timeofday) {
				$decode["tm_hour"] = $hour;
				$decode["tm_min"] = $min;
				$decode["tm_sec"] = $sec;
			}
			return true;
		}
		private function emu_strptime_revuX(&$str, &$decode)
		{
			list($hour, $min, $sec) = explode(':', $str, 3);
			if (!self::valid_number($hour, 2) || !self::valid_number($min, 2))
				return false;
			$parsed = self::parse_number($sec);
			if ($parsed["consumed"] <= 2) {
				$sec = $parsed["number"];
				$unparsed = $parsed["unparsed"];
			} else {
				$sec = substr($sec, 0, 2);
				$parsed = self::parse_number($sec);
				if ($parsed["consumed"] != 2)
					throw new Exception(_t("Number parsing of second revealed some unexpected condition (BUG)"));
				$sec = $parsed["number"];
				$unparsed = substr($sec, 2);
			}
			$hour = intval($hour);
			$min = intval($min);
			$sec = intval($sec);
			if ($hour < 0 || $min < 0 || $sec < 0 || $hour > 23 || $min > 59 || $sec > 60)
				return false;
			$decode["tm_hour"] = $hour;
			$decode["tm_min"] = $min;
			$decode["tm_sec"] = $sec;
			$str = $unparsed;
			return true;
		}
		private function emu_strptime_revuH(&$str, &$decode)
		{
			$parsed = self::parse_number($str);
			if ($parsed["consumed"] <= 2) {
				$hour = $parsed["number"];
				$unparsed = $parsed["unparsed"];
			} else {
				$hour = substr($str, 0, 2);
				$parsed = self::parse_number($hour);
				if ($parsed["consumed"] != 2)
					throw new Exception(_t("Number parsing of second revealed some unexpected condition (BUG)"));
				$hour = $parsed["number"];
				$unparsed = substr($str, 2);
			}
			$hour = intval($hour);
			if ($hour < 0 || $hour > 23)
				return false;
			$decode["tm_hour"] = $hour;
			$str = $unparsed;
			return true;
		}
		private function emu_strptime_revuM(&$str, &$decode)
		{
			$parsed = self::parse_number($str);
			if ($parsed["consumed"] <= 2) {
				$min = $parsed["number"];
				$unparsed = $parsed["unparsed"];
			} else {
				$min = substr($str, 0, 2);
				$parsed = self::parse_number($min);
				if ($parsed["consumed"] != 2)
					throw new Exception(_t("Number parsing of second revealed some unexpected condition (BUG)"));
				$min = $parsed["number"];
				$unparsed = substr($str, 2);
			}
			$min = intval($min);
			if ($min < 0 || $min > 59)
				return false;
			$decode["tm_min"] = $min;
			$str = $unparsed;
			return true;
		}
		
		public function emu_strptime($str, $fmt)
		{
			$decode = array(
				'tm_sec'   => 0,
				'tm_min'   => 0,
				'tm_hour'  => 0,
				'tm_mday'  => 1,
				'tm_mon'   => 0,
				'tm_year'  => 0,
				'tm_wday'  => 0,
				'tm_yday'  => 0,
				'unparsed' => $str
        		);
        		$trailing = "";
			$ruins = explode("%", $fmt);
			$dblperc = false;
			foreach ($ruins as $wreck) {
				if (strlen($wreck) == 0 && !$dblperc) {
					$dblperc = true;
					continue;
				} else if (strlen($wreck) == 0)
					$wreck = '%';
				$dblperc = false;
				$ch = substr($wreck, 0, 1);
				$wreck = substr($wreck, 1);
				switch ($ch) {
					case 'x':
						if (!self::emu_strptime_revx($str, $decode)) {
							$decode["unparsed"] = $str;
							return $decode;
						}
						break;
					case 'c':
						if (!self::emu_strptime_revx($str, $decode, true)) {
							$decode["unparsed"] = $str;
							return $decode;
						}
						break;
					case 'X':
						if (!self::emu_strptime_revuX($str, $decode)) {
							$decode["unparsed"] = $str;
							return $decode;
						}
						break;
					case 'H':
						if (!self::emu_strptime_revuH($str, $decode)) {
							$decode["unparsed"] = $str;
							return $decode;
						}
						break;
					case 'M':
						if (!self::emu_strptime_revuM($str, $decode)) {
							$decode["unparsed"] = $str;
							return $decode;
						}
						break;
					case '%':
						$wreck = '%';
						break;
				}
				$part = substr($str, 0, strlen($wreck));
				if ($part != $wreck) {
					$decode["unparsed"] = $str;
					return $decode;
				}
				$str = substr($str, strlen($wreck));
			}
			$decode["unparsed"] = $str;
			return $decode;
		}
		public function emu_strftime($fmt, $tm)
		{
			$output = false;
			$ruins = explode("%", $fmt);
			$dblperc = false;
			foreach ($ruins as $wreck) {
				if (strlen($wreck) == 0 && !$dblperc) {
					$dblperc = true;
					continue;
				} else if (strlen($wreck) == 0)
					$wreck = '%';
				$dblperc = false;
				$ch = substr($wreck, 0, 1);
				$wreck = substr($wreck, 1);
				switch ($ch) {
					case 'x':
						$output .= self::emu_strftime_x($tm);
						break;
					case 'c':
						$output .= self::emu_strftime_x($tm, true);
						break;
					case 'X':
						$output .= self::emu_strftime_uX($tm);
						break;
					case 'H':
						$output .= self::emu_strftime_uH($tm);
						break;
					case 'M':
						$output .= self::emu_strftime_uM($tm);
						break;
					case '%':
						$output .= '%';
						break;
				}
				$output .= $wreck;
			}
			return $output;
		}

		public function stringToDate($str)
		{
			return $this->stringToTime($str);
		}
		public function dateToString($tm)
		{
			return $this->timeToString($tm);
		}
		public function dateToStringMDY($month, $day, $year)
		{
			$tm = mktime(0, 0, 1, $month, $day, $year);
			return self::dateToString($tm);
		}

		public function strtotime($strtime)
		{
			return strtotime($strtime);
		}
	}
?>
