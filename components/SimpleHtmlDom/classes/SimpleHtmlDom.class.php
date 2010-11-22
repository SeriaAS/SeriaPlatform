<?php
	require_once(SERIA_ROOT.'/seria/components/SimpleHtmlDom/lib/simple_html_dom.php');

	class SimpleHtmlDom {
		public static function parse($html)
		{
			return file_get_html($html);
		}

		public static function fetch($url, $lowercase=true)
		{
			return str_get_html($url, $lowercase);
		}
	}
