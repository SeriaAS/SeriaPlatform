<?php

/**
 *
 *
 * @author Jan-Espen Pettersen
 * @package SAPI
 *
 */
class AuthprovidersManifest
{
	const SERIAL = 1;
	const NAME = 'Authproviders';

	public static $classPaths = array(
		'classes/*.class.php',
	);
	public static $dependencies = array(
		'SERIA_Html', /* meta-templates */
		'MultiSession',
		'SAPI'
	);
}

function AuthprovidersInit()
{
	$component = new SERIA_AuthprovidersComponent();
	SERIA_Components::addComponent($component);
	$component->embed();
	SERIA_Hooks::listen(SERIA_User::DELETE_HOOK, array('SERIA_UserAuthenticationProvider', 'deletingUser'));
	SERIA_Hooks::dispatch('Authproviders::inited', $component);
}
