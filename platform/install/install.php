<?php
/**
*	Whenever the CMS is in install mode, this file is included on every visit.
*/

	try
	{
		require_once(SERIA_ROOT."/seria/platform/install/base.php");
	}
	catch(PDOException $e)
	{
	}
