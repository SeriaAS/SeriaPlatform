<?php
	class SERIA_Format {
		public static function filesize($size) {
			if ($size >= 1024 * 1024 * 1024) {
				return round($size / 1024 / 1024 / 1024, 2) . _t(' GB');
			} elseif ($size >= 1024 * 1024) {
				return round($size / 1024 / 1024, 2). _t(' MB');
			} elseif ($size >= 1024) {
				return round($size / 1024, 2) . _t(' kB');
			} else {
				return floor($size) . _t(' bytes');
			}
		}
		
		public static function html($html) {
			return htmlspecialchars($html);
		}
	}
?>