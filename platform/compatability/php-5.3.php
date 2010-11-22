<?php
/**
 * This file is always included when PHP-version is less than 5.3. It is NOT included if php version is exactly 5.3 or higher.
 */

	if(!function_exists("fnmatch"))
	{
		function fnmatch($pattern, $string) {
			if ($pattern == '*') {
				return true;
			}
		
			$pattern = "#^".strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.'))."\z#i";
			return preg_match($pattern, $string);
		}
	}
?>
