<?php

/**
 *
 *
 * @author Jan-Espen Pettersen
 * @package SAPI
 *
 */
class SAPIManifest
{
	const SERIAL = 1;
	const NAME = 'SAPI';

	public static $classPaths = array(
		'classes/*.class.php',
		'sapi/*.class.php'
	);
	public static $menu = array(
		'controlpanel/other/sapi' => array(
			'title' => 'SAPI Application keys',
			'description' => 'Browse SAPI application keys',
			'page' => 'SAPI/appkeys',
		),
	);
}

function SAPIInit()
{
	SERIA_Hooks::listen(SERIA_User::DELETE_HOOK, array('SAPI_Token', 'deleteAllTokensOwnedByUser'));
}
