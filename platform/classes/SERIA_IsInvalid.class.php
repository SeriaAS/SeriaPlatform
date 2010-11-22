<?php
	class SERIA_IsInvalid
	{
		function isoDate($value, $required=false)
		{
			$value = trim($value);
			if(!$required && $value=="")
				return false;

			$legalFormats = array(
				"o-m-d",
				"o-n-d",
				"o-m-j",
				"o-n-j",
				"o-m-d H:i",
				"o-n-d H:i",
				"o-m-j H:i",
				"o-n-j H:i",
				"o-m-d H:i:s",
				"o-n-d H:i:s",
				"o-m-j H:i:s",
				"o-n-j H:i:s",
			);

			$d = strtotime($value);

			foreach($legalFormats as $format)
				if(date($format, $d) == $value)
					return false;
			return _t("Wrong date or invalid format");
		}
		function norwegianDate($value, $required=false)
		{
			SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, "Using deprecated function SERIA_IsInvalid::norwegianDate.");
			
			$value = trim($value);
			if ($value=="") {
				if (!$required)
					return false;
				return _t("Date is required");
			}
			$tok = explode(".", $value);
			if (count($tok) != 3)
				return _t("Invalid date format");
			foreach ($tok as $t) {
				if (("".intval($t)) != ("".$t))
					return _t("Invalid date format");
			}
			$d = mktime(0, 0, 1, $tok[1], $tok[0], $tok[2]);
			if (intval(date('Y', $d)) != intval($tok[2]) ||
			    intval(date('m', $d)) != intval($tok[1]) ||
			    intval(date('d', $d)) != intval($tok[0]))
				return _t("Wrong date or invalid format");
			return false;
		}
		function localDate($value, $required=false, $datelocale=false)
		{
			if (!$required && !$value) return false;
			if ($datelocale === false)
				$dateloc = SERIA_Locale::getLocale();
			else
				$dateloc = $datelocale;
			$value = trim($value);
			if ($value=="" && $required)
				return _t("Date is required");

			$tval = $dateloc->stringToTime($value);
			if ($dateloc->timeToString($tval) != $value)
				return _t("Wrong date or invalid format");
			return false;
		}
		/**
		 * Checks whether the local-time-string is valid.
		 *
		 * @param $value
		 * @param $required Whether an empty $value can be accepted
		 * @param $datelocale A locale object
		 * @param $type Either 'time', 'datetime' or 'date'.
		 * @param $refTS A reference time (date). Unless you validate time of day on the current date this should be supplied to get the correct behaviour according to daylight savings.
		 * @return unknown_type
		 */
		static function localTime($value, $required=false, $datelocale=false, $type='time', $refTS=false)
		{
			if (!$required && !$value) return false;
			if ($datelocale === false)
				$dateloc = SERIA_Locale::getLocale();
			else
				$dateloc = $datelocale;
			$timeEx = $dateloc->timeToString(0, $type);
			$value = trim($value);
			if ($value=="" && $required)
				return _t("Time is required");
			$tval = $dateloc->stringToTime($value, $type, $refTS);
			if ($dateloc->timeToString($tval, $type) != $value)
				return _t("Wrong time or invalid format (Example: ".$timeEx.')');
			return false;
		}

		function oneOf($value, $values)
		{
			$oneOf = array();
			if ($values)
				foreach($values as $v)
				{
					if($v==$value)
						return false;
					$oneOf[] = "'".$value."'";
				}
			return _t("Must be one of: %TYPES%", array("%TYPES%" => implode(", ", $oneOf)));
		}

		function username($name, $required=false)
		{
			if($required && trim($name)=="")
				return _t("Required");

			return false;
		}

		function name($name, $required=false)
		{
			if($required && trim($name)=="")
				return _t("Required");

			return false;
		}

		function password($password)
		{
			if(trim($password)=="")
				return _t("Required");

			$requiredChars = array(
				"0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ",
			);

			$points = 0;
			foreach($requiredChars as $req)
			{
				$i = 0;
				for($i = 0; $i < strlen($req); $i++)
				{
					if(strpos($password, $req[$i])!==false)
					{
						$points++;
						break;
					}
				}
			}
			if($points<sizeof($requiredChars))
				return _t("Must use both numbers and English alphabet letters");

			if(strlen($password)<6)
				return _t("Password must be at least 6 characters long");

			return false;
		}

		function eMail($eMail, $required=false)
		{
			if($required && trim($eMail)=="")
				return _t("Required");
			else if(!$required && trim($eMail)=="")
				return false;

			if (preg_match("/[\\000-\\037]/",$eMail)) {
				return _t("Invalid characters in e-mail address");
   			}

			// checks proper syntax
  			if( !preg_match( "/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD", $eMail))
  			{
				return _t("Please enter a valid e-mail address");
			}
			// gets domain name
			list($username,$domain)=explode('@',$eMail);
			// checks for if MX records in the DNS
			if (defined('SERIA_VALIDATE_EMAIL_HOST') && SERIA_VALIDATE_EMAIL_HOST) {
				$mxhosts = array();
				if(!getmxrr($domain, $mxhosts))
				{
					// no mx records, ok to check domain
					if (!fsockopen($domain,25,$errno,$errstr,30))
					{
						return _t("Could not find e-mail server");
					}
					else
					{
						return false;
					}
				}
				else
				{
					// mx records found
					foreach ($mxhosts as $host)
					{
						if (fsockopen($host,25,$errno,$errstr,30))
						{
							return false;
						}
					}
					return _t("Could not find e-mail server");
				}
				return _t("Could not find e-mail server");
			}
			return false;
		}

		function number($number, $required=false, $min=false, $max=false, $onlyInteger = false)
		{
			if($required && trim($number)=="")
				return _t("Required");
			else if(!$required && trim($number)=="")
				return false;
			if($onlyInteger && intval($number)!=$number)
				return _t("Number must be an integer");
			$null = trim($number, '0123456789.+-');
			if(!empty($null))
				return _t("Invalid characters. Allowed characters are '0123456789.+-'.");
			if(!is_numeric($number) || ($min!==false && $number<$min) || ($max!==false && $number>$max))
				return _t("Enter a numeric value between %MIN% and %MAX%", array("MIN" => $min, "MAX" => $max));
			return false;
		}

		function integer($number, $required=false, $min=false, $max=false)
		{
			return SERIA_IsInvalid::number($number, $required, $min, $max, true);
		}

		function real($number, $required=false, $min=false, $max=false)
		{
			return SERIA_IsInvalid::number($number, $required, $min, $max, false);
		}
		
		function flashStreamUrl($url, $required = false) {
			if($required && trim($url)=="")
				return _t("Required");
			else if(!$required && trim($url)=="")
				return false;

			$restURL = $url;

			// check for spaces and line shifts

			if($restURL != trim($restURL))
				return _t("No spaces allowed before or after");

			// check schema

			if(strpos($url, "rtmp://")===0) $restURL = substr($url, 7);
			else if(strpos($url, "rtmps://")===0) $restURL = substr($url, 8);
			else return _t("Must start with either rtmp:// or rtmps://");

			// check domain and/or password

			if(trim($restURL)=="")
				return _t("Incomplete URL");
			
			return self::relativeUrl($restUrl, true);
		}

		function url($url, $required=false)
		{
			if($required && trim($url)=="")
				return _t("Required");
			else if(!$required && trim($url)=="")
				return false;

			$restURL = $url;

			// check for spaces and line shifts

			if($restURL != trim($restURL))
				return _t("No spaces allowed before or after");

			// check schema

			if(strpos($url, "http://")===0) $restURL = substr($url, 7);
			else if(strpos($url, "https://")===0) $restURL = substr($url, 8);
			else if(strpos($url, "ftp://")===0) $restURL = substr($url, 6);
			else return _t("Must start with either http://, https:// or ftp://");

			// check domain and/or password

			if(trim($restURL)=="")
				return _t("Incomplete URL");
			
			return self::relativeUrl($restUrl, true);
		}

		function relativeURL($url, $required=false)
		{
			$restURL = $url;
			if(($o = strpos($restURL, "/"))!==false)
			{
				$domain = substr($restURL, 0, $o);
				$restURL = substr($restURL, $o);
			}
			else
			{
				$domain = $restURL;
				$restURL = "";
			}

			$domainParts = explode("@",$domain);

			if(sizeof($domainParts)==1)
			{
				$credentials = "";
			}
			else if(sizeof($domainParts)==2)
			{
				$credentials = $domainParts[0];
				$domain = $domainParts[1];
			}
			else 
				return _t("Invalid domain");

			if($restURL!=="")
			{
				$path = $restURL;
			}

			if($credentials!="" && (sizeof(explode(":",$credentials))!=2))
				return _t("Invalid credentials");

			return false;
		}
		
		public static function hostname($hostname, $required = false) {
			if (!trim($hostname) && $required) {
				return _t('Required');
			}
			
			if (!filter_var('http://' . $hostname . '/', FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
				return _t('Invalid hostname');
			}
			
			return false;
		}

		function phone($phone, $required=false)
		{
			if(!$required && trim($phone)=="")
				return false;
			else if($required && trim($phone)!="")
				return ("Required");

			$numbers = "0123456789";
			$phone = str_replace(" ", "", $phone);
			if($phone[0]=="+") $i = 1;
			else $i = 0;

			for(; $i < strlen($phone); $i++)
				if(strpos($numbers, $phone[$i])===false)
					return _t("Phone numbers can only contain spaces and digits, and may start with \"+\".");

			return false;
		}

		function timeZone($timeZone, $required=false)
		{
			//TODO:Validate timezones according to some standard
			return _t("Invalid timezone");
		}

		function latitude($latitude, $required=false)
		{
			return SERIA_IsInvalid::real($latitude, $required, -180, 180);
		}

		function longitude($longitude, $required=false)
		{
			return SERIA_IsInvalid::real($latitude, $required, -90, 90);
		}

		function uploadedImage($file, $required=false, $maxFileSize="41943040")
		{
			$uploadedFileCheck = SERIA_IsInvalid::uploadedFile($file, $required, $maxFileSize, array("jpg", "jpeg", 'gif', 'png'));
			if ($uploadedFileCheck === false) {
				// If open_basedir is set, trying to access file may create warnings and result is not usable
				if (ini_get('open_basedir')) {
					return false;
				} else {
					list($width, $height, $type) = getimagesize($file['tmp_name']);
					switch ($type) {
						case IMAGETYPE_JPEG:
						case IMAGETYPE_PNG:
						case IMAGETYPE_GIF:
							return false;
						default:
							return _t('File is not an image file');
							break;
					}
				}
			} else {
				return $uploadedFileCheck;
			}
		}

		function uploadedFile($file, $required=false, $maxFileSize="41943040", $filetypes="")
		{

			if (!$required && $file == "")
				return false;
			else if ($required && !$file["name"])
				return _t("Required");
			else if ($file["size"] == "0" || $file["error"] == 4)
				return _t("No file was uploaded");
			else if ($maxFileSize && $file["size"] > $maxFileSize)
				return _t("The file is to big. Max file size is ".$maxFileSize." bytes");
			else if ($file["error"] == 1)
				return _t("The file is to big. Max file size is ".ini_get("upload_max_filesize")."bytes");
			else if ($file["error"] == 2)
				return _t("The file is to big. Max file size is ".$_FORM["MAX_FILE_SIZE"]." bytes");
			else if ($file["error"] == 3)
				return _t("The file was broken during uploading. Please try again.");
			else if ($file["error"] == 6)
				return _t("Server error. Missing a temporary folder.");
			else if ($file["error"] == 7)
				return _t("Server error. Failed to write file to disk.");
			else if ($file["error"] == 8)
				return _t("File upload stopped by extension");
			else if (!is_uploaded_file($file["tmp_name"]))
				return _t("Error. Possible file upload attack.");

			$curFileType = substr($file["name"], strrpos($file["name"], ".") + 1);
			$ok = false;

			if ($filetypes) {
				foreach ($filetypes as $filetype)
					if (strtolower($filetype) == strtolower($curFileType)) $ok = true;
			} else {
				$ok = true;
			}


			if (!$ok || strrpos($file["name"], ".") === false) 
				return _t("Wrong filetype");

			return false;
		}


	}
?>
