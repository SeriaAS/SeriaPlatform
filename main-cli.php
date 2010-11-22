<?php
	require(dirname(__FILE__)."/main.php");

	if(empty($argc))
	{
		SERIA_Base::displayErrorPage(_t("Run this script from the shell"), _t("This script was designed to be run from the shell/commandline."));
	}
	SERIA_Template::disable();
	SERIA_Template::setLanguage('en-GB');
	set_time_limit(0);
	while(@ob_end_clean());
	ob_implicit_flush(true);
