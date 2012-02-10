<?php

if (!function_exists('disable_discovery')) {
	function disable_discovery()
	{
		SERIA_Authproviders::disableAutomaticDiscovery();
	}

	$seria_options = array(
		'hooks' => array(
			'Authproviders::inited' => array(
				'callback' => 'disable_discovery',
				'weight' => 0
			)
		)
	);
}

require_once(dirname(__FILE__).'/../../main.php');

SERIA_Template::title('Invoking Simplesaml system');

$pathcomp = explode('/', substr($_SERVER['PATH_INFO'], 1));

$cleanGET = $_GET;

if (!$pathcomp || !($provider = array_shift($pathcomp)) || !($providerId = array_shift($pathcomp)) || !($stateId = array_shift($pathcomp)))
	throw new SERIA_Exception('Expected a provider-class, provider-id and state-id as part of the path.');
try {
	$state = new SERIA_AuthenticationState($stateId);
	$_SERVER['X_SERIA_PLATFORM_STATE_ID'] = $state->get('id');
} catch (SERIA_Exception $e) {
}

call_user_func(array($provider, 'loadProviders'));

$providerObject =& SERIA_Authproviders::getProvider($providerId);
if ($providerObject === null)
	throw new SERIA_Exception('Provider object not found: '.$providerId.' ('.$provider.')');
$providerObject->configSimplesaml();
unset($providerObject);

$filepath = LOAD_SIMPLESAMLPHP_PATH.'/www';
$virtpath = $_SERVER['SCRIPT_NAME'].'/'.$provider.'/'.$providerId.'/'.$stateId;

while ($pathcomp) {
	$try_next = array_shift($pathcomp);
	if (!file_exists($filepath.'/'.$try_next)) {
		SERIA_Base::debug($filepath.'/'.$try_next.' does not exist, using '.$filepath.'.');
		array_unshift($pathcomp, $try_next);
		break;
	}
	$filepath .= '/'.$try_next;
	$virtpath .= '/'.$try_next;
}

$filename = $filepath;

$_SERVER['SCRIPT_FILENAME'] = $filename;
$_SERVER['SCRIPT_NAME'] = $virtpath;
if ($pathcomp)
	$_SERVER['PATH_INFO'] = '/'.implode('/', $pathcomp);
else
	unset($_SERVER['PATH_INFO']);
if (isset($_SERVER['PATH_INFO']))
	$_SERVER['PATH_TRANSLATED'] = SERIA_ROOT.$_SERVER['PATH_INFO'];
else
	unset($_SERVER['PATH_TRANSLATED']);

if (is_dir($filename)) {
	if (substr($filename, -1) != '/')
		$filename .= '/';
	if (file_exists($filename.'index.php'))
		$filename .= 'index.php';
}

SERIA_Base::debug('Passing control to script: '.$filename);

foreach ($_SESSION as $name => $value) {
	SERIA_Base::debug('SESSION: '.$name.' => '.serialize($value));
}

$pi = pathinfo($filename);

switch ($pi['extension']) {
	case 'php':
		SimplesamlSystem::autosaveSession();
		$_GET = $cleanGET; /* Remove authentication state params */
		require($filename);
		break;
	case 'css':
		SERIA_Template::override('text/css', file_get_contents($filename));
		break;
	case 'js':
		SERIA_Template::override('text/javascript', file_get_contents($filename));
		break;
	case 'txt':
		SERIA_Template::override('text/plain', file_get_contents($filename));
		break;
	default:
		throw new Exception('Unrecognized filetype: '.$pi['extension']);
}

