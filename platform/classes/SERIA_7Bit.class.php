<?php

	class SERIA_7Bit
	{
		private static $SEPARATORS = "\n\r\t// ?~^¨<>.,:()'\"\]\[{}%&\$#£!§=+\-";

		/**
		*	Prepares a string to be stored in a fulltext field in the database using 7bit encoding
		*/
		static function dbPrepare($sentence, $prefix=false)
		{
			$parts = SERIA_7Bit::tokenize($sentence);

			if($prefix!==false)
			{
				foreach($parts as $key => $part)
					$parts[$key] = SERIA_7Bit::word($prefix.$part);
				return implode(" ", $parts);
			}
			else
			{
				foreach($parts as $key => $part)
					$parts[$key] = SERIA_7Bit::word($part);
				return implode(" ", $parts);
			}
		}

		static function tokenize($string)
		{
			return preg_split("|[".(self::$SEPARATORS)."]|", str_replace(array("*","|"), array(" "," "), $string), -1, PREG_SPLIT_NO_EMPTY);
		}

		static function queryPrepare($sentence)
		{
			//TODO: Should support quotes
			$query = array();
			$parts = explode(" ", $sentence);
			foreach($parts as $part)
			{
				if($part[0]=="-" || $part[0]=="+")
				{ // keep prefix
					$prefix = $part[0];
					$query[] = $prefix.SERIA_7Bit::word(substr($part,1));
				}
				else
					$query[] = SERIA_7Bit::word($part);
			}
			return implode(" ", $query);
		}

		static function word($wordToFix)
	        {
	                $wordToFix = mb_strtolower($wordToFix, "UTF-8");
	                $res = "";
	                $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
	                $translator = array(
	                        "æ" => "ae",
	                        "ø" => "oe",
	                        "å" => "aa",
	                        "ö" => "oe",
	                        "ü" => "ue",
	                        "ä" => "ae",
	                );
	                $l = mb_strlen($wordToFix, "UTF-8");
	                for($i = 0; $i < $l; $i++)
	                {
	                        $c = mb_substr($wordToFix, $i, 1, "UTF-8");
	                        $isoC = mb_convert_encoding($c, "ISO-8859-1", "UTF-8");

	                        if(strpos($chars, $c)!==false)
	                        {
	                                $res .= $c;
	                        }
	                        else if(isset($translator[$isoC]))
	                        {
	                                $res .= $translator[$isoC];
	                        }
	                        else
	                        {
	                                $res .= "_".str_pad(bin2hex($c),2,"0",STR_PAD_LEFT);
	                        }
	                }
	                return $res;
	        }
		static function reverseWord($wordToReverse)
		{
			$rv = '';
			$translator = array(
				/*"æ" => "ae",
				"ø" => "oe",
				"å" => "aa",
				"ö" => "oe",
				"ü" => "ue",
				"ä" => "ae",*/
			);
			$i = 0;
			$l = mb_strlen($wordToReverse, "UTF-8");
			while ($i < $l) {
				if (($i + 2) < $l) {
					$threeNext = mb_substr($wordToReverse, $i, 3, "UTF-8");
					$twoNext = mb_substr($threeNext, 0, 2, "UTF-8"); 
				} else if (($i + 1) < $l) {
					$threeNext = false;
					$twoNext = mb_substr($wordToReverse, $i, 2, "UTF-8");
				} else {
					$threeNext = false;
					$twoNext = false;
				}
				$next = mb_substr($wordToReverse, $i, 1, "UTF-8");
				if ($threeNext !== false && $next == '_') {
					$hex = mb_substr($threeNext, 1, 2, "UTF-8");
					if (strlen($hex) != 2) {
						/*
						 * UTF-8 encoded, this is certainly invalid hex
						 */
						return $wordToReverse; /* Invalid 7Bit::word */
					}
					$hex = strtolower($hex);
					$hexcodes = array(
						'0',
						'1',
						'2',
						'3',
						'4',
						'5',
						'6',
						'7',
						'8',
						'9',
						'a',
						'b',
						'c',
						'd',
						'e',
						'f'
					);
					if (!in_array(substr($hex, 0, 1), $hexcodes) || !in_array(substr($hex, 1, 1), $hexcodes)) {
						/*
						 * Invalid hex..
						 */
						return $wordToReverse; /* Invalid 7Bit::word */
					}
					$rv .= pack('H*', $hex);
					$i += 3; /* consume three bytes */
				} else if ($twoNext !== false && ($replace = array_search($twoNext, $translator)) !== false) {
					$rv .= $replace;
					$i += 2;
				} else {
					$rv .= $next;
					$i += 1;
				}
			}
			return $rv;
		}
	}
