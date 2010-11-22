<?php

class DebugLogging
{
	public static function printMultiline($ml)
	{
		$ml = str_replace("\r\n", "\n", $ml);
		$ml = str_replace("\n\r", "\n", $ml);
		$ml = str_replace("\r", "\n", $ml);
		$lines = explode("\n", $ml);
		foreach ($lines as $ln)
			SERIA_Base::debug($ln);
	}
	public static function printBacktrace()
	{
		ob_start();
		debug_print_backtrace();
		$bt = ob_get_clean();
		self::printMultiline($bt);
	}
}