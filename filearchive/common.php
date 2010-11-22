<?php
	define('FROM_POPUP', true);
	
	function getIconFromFilename($filename) {
		// Get list of icons from icon dir
		static $icons = null;
		if (!$icons) {
			$icons = array();
			if ($directory = opendir(dirname(__FILE__) . '/icons')) {
				while ($file = readdir($directory)) {
					if ($file[0] != '.') {
						$icons[$extension = array_shift(explode('.', $file))] = $extension;
					}
				}
			}
		}
		
		$extension = substr(strrchr($filename, '.'), 1, strlen($filename));
	
		if ($icons[$extension]) {
			$icon = $icons[$extension];
		} else {
			$icon = 'default';
		}
		
		$icon = SERIA_HTTP_ROOT . '/seria/filearchive/icons/' . $icon . '.png';
		return $icon;
	}
?>