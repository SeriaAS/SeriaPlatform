#!/usr/bin/php
<?php
	umask(0);
	recurse(dirname(dirname(__FILE__)));
	function recurse($root)
	{
		$dirs = glob($root.'/*', GLOB_ONLYDIR);
		foreach($dirs as $dir) if($dir!="." && $dir!="..")
		{
			chmod($dir, 0755);
			echo "chmod 755 ".$dir."\n";
			recurse($dir);
		}

		$files = glob($root.'/*');
		foreach($files as $file) if(!is_dir($file))
		{
			chmod($file, 0644);
			echo "chmod 644 ".$file."\n";
		}
	}
