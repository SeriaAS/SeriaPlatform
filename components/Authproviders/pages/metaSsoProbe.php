<?php

if (!isset($_GET['id']) || !isset($_GET['class']))
	throw new SERIA_Exception('No id nor class');

if (!SERIA_Base::user()) {
	SERIA_Authproviders::loadProviders($_GET['class']);

	$provider =& SERIA_Authproviders::getProvider($_GET['id']);

	$provider->authenticate(false, false, true);
}

die('DONE');