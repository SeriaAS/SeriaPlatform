<?php
	class SERIA_Sanitize
	{
		/**
		*	Will translate a string into a valid url component, suitable for nice urls. Spaces becomes dashes,
		*	non-ascii characters become ascii characters etc.
		*/
		function slug($string) {
	                return strtolower(trim(preg_replace(array('~[^0-9a-z]~i', '~-+~'), '-', preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'))), '-'));
	        }

		function filename($filename, $sourceEncoding="UTF-8")
		{
			$filename = mb_convert_encoding($filename, 'ISO-8859-1', $sourceEncoding);
			// legal characters
			$legalChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-.';
			// replace with -
			$replaceChars = "\t\n _,";
			// everything else will be stripped
			$l = strlen($filename);
			$newFilename = "";
			for($i = 0; $i < $l; $i++)
			{
				if(strpos($legalChars, $filename[$i])!==false) // legal?
					$newFilename .= $filename[$i];
				else if(strpos($replaceChars, $filename[$i])!==false) // replaceable?
					$newFilename .= '-';
				// other chars is ignored
			}
			if(trim($newFilename)=='') 
				throw new SERIA_Exception('Filename illegal');

			$newFilename = mb_convert_encoding($newFilename, $sourceEncoding, 'ISO-8859-1');
			return $newFilename;
		}
		function reverseFilename($filename, $toEncoding="UTF-8")
		{
			$filename = SERIA_7Bit::reverseWord($filename);
			$filename = str_replace("_", " ", $filename);
			return mb_convert_encoding($filename, $toEncoding, "UTF-8");
		}

		function html($html)
		{
			return nl2br(strip_tags($html));
		}
	}
