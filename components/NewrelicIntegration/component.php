<?php
/**
 *
 *
 * @author Jan-Espen Pettersen
 * @package NewrelicIntegration
 *
 */
class NewrelicIntegrationManifest
{
	const SERIAL = 1;
	const NAME = 'NewrelicIntegration';

	public static $classPaths = array(
		'classes/*.class.php',
	);
	public static $menu = array(
	);
}

function NewrelicIntegrationInit()
{
	if (extension_loaded('newrelic')) {
		SERIA_Hooks::listen(SERIA_MAINTAIN_HOOK, array('SERIA_NewrelicHooks', 'maintain'));
	}
}