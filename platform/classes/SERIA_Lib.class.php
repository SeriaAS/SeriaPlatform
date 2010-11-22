<?php

class SERIA_Lib
{
	static function getMaxUploadSize()
	{
		return min(SERIA_Lib::let_to_num(ini_get("upload_max_filesize")), SERIA_Lib::let_to_num(ini_get("post_max_size")));
	}

	static function xmlspecialchars($string)
	{
		return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
	}

	static function publishJSON($source)
	{
		header('Content-Type: text/javascript');
		if(isset($_GET["callback"]))
			die($_GET["callback"]."(".SERIA_Lib::toJSON($source).");");
		else
			die(SERIA_Lib::toJSON($source));
	}

	static function toJSON($source, $level=0)
	{
		return json_encode($source);
	}
	
	static function fromJSON($source) {
		return json_decode($source, true);
	}

	static function getDBTable($tableName, $condition=false)
	{
		if($condition)
		return SERIA_Base::db()->query("SELECT * FROM $tableName WHERE $condition")->fetchAll(PDO::FETCH_ASSOC);
		else
		return SERIA_Base::db()->query("SELECT * FROM $tableName")->fetchAll(PDO::FETCH_ASSOC);
	}

	static function array_merge_recursive_unique($array1, $array2)
	{
		foreach($array2 AS $k => $v)
		{
			// CHECK IF VALUE EXISTS IN $array1
			if(!empty($array1[$k]))
			{
				// IF VALUE EXISTS CHECK IF IT'S AN ARRAY OR A STRING
				if(!is_array($array2[$k]))
				{
					// OVERWRITE IF IT'S A STRING
					$array1[$k]=$array2[$k];
				}
				else
				{
					// RECURSE IF IT'S AN ARRAY
					$array1[$k] = self::array_merge_recursive_unique($array1[$k], $array2[$k]);
				}
			}
			else
			{
				// IF VALUE DOESN'T EXIST IN $array1 USE $array2 VALUE
				$array1[$k]=$v;
			}
		}
		return $array1;
	}

	function getContentType($filename)
	{
		$ext = mb_strtolower(substr($filename, strrpos($filename,".")+1), "UTF-8");
		$map = array(
				"f4v"=>"video/mp4",
				"f4p"=>"video/mp4",
				"f4a"=>"audio/mp4",
				"f4b"=>"audio/mp4",
				"m4v"=>"video/mp4",
				"flv"=>"video/x-flv",
				"3gp"=>"video/3gpp",
				"3gpp"=>"video/3gpp",
				"rm"=>"video/x-pn-realvideo",
				"wmv"=>"video/x-ms-wmv",
				"264"=>"video/x-mpeg-avc",
				"m4a"=>"audio/x-m4a",
				"wma"=>"audio/x-ms-wma",
				"mkv"=>"video/x-matroska",
				"mka"=>"audio/x-matroska",
				"ogg"=>"audio/x-ogg",
				"mp2"=>"audio/mpeg",
				"mp4"=>"video/mp4",
				"mpeg4"=>"video/mp4",
				"mp3"=>"audio/mpeg",
				"html"=>"text/html", 
				"pgm"=>"image/x-portable-graymap", 
				"xml"=>"text/xml", 
				"htm"=>"text/html", 
				"jpg"=>"image/jpeg", 
				"zip"=>"application/zip", 
				"dvi"=>"application/x-dvi", 
				"mpe"=>"video/mpeg", 
				"ppm"=>"image/x-portable-pixmap", 
				"doc"=>"application/msword", 
				"qt"=>"video/quicktime", 
				"sgm"=>"text/sgml", 
				"css"=>"text/css", 
				"xbm"=>"image/x-xbitmap", 
				"ps"=>"application/postscript", 
				"mpg"=>"video/mpeg", 
				"pnm"=>"image/x-portable-anymap", 
				"ras"=>"image/x-cmu-raster", 
				"gif"=>"image/gif", 
				"rtf"=>"application/rtf", 
				"mpeg"=>"video/mpeg", 
				"rb"=>"text/plain", 
				"txt"=>"text/plain", 
				"xwd"=>"image/x-xwindowdump", 
				"pbm"=>"image/x-portable-bitmap", 
				"bmp"=>"image/bmp", 
				"asc"=>"text/plain", 
				"tif"=>"image/tiff", 
				"rd"=>"text/plain", 
				"pdf"=>"application/pdf", 
				"sgml"=>"text/sgml", 
				"jpeg"=>"image/jpeg", 
				"eps"=>"application/postscript", 
				"ai"=>"application/postscript", 
				"avi"=>"video/x-msvideo", 
				"mov"=>"video/quicktime", 
				"etx"=>"text/x-setext", 
				"png"=>"image/png", 
				"jpe"=>"image/jpeg", 
				"ppt"=>"application/vnd.ms-powerpoint", 
				"xls"=>"application/vnd.ms-excel", 
				"xpm"=>"image/x-xpixmap", 
				"tiff"=>"image/tiff", 
		);
		if(isset($map[$ext])) return $map[$ext];
		else return "application/octet-stream";
	}

	/**
	 * Converts php-ini data sizes such as 256M to bytes.
	 *
	 * @param string $string
	 * @return int
	 */
	function let_to_num($v)
	{
		$l = substr($v, -1);
		$ret = substr($v, 0, -1);
		switch(strtoupper($l)){
			case 'P':
				$ret *= 1024;
			case 'T':
				$ret *= 1024;
			case 'G':
				$ret *= 1024;
			case 'M':
				$ret *= 1024;
			case 'K':
				$ret *= 1024;
				break;
		}
		return $ret;
	}
}
