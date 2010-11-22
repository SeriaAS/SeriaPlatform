<?php
	// adding of directory SERIA_DYNAMICCLASS_ROOT was left out of install.php when added to platform.

	$path = SERIA_DYNAMICCLASS_ROOT;
	
	if(!file_exists($path))
	{
		if(!mkdir($path, 0777, true))
			throw new SERIA_Exception('Unable to create directory '.$path.' for SERIA Dynamicclasses');
	}

