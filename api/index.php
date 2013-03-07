<?php

require(dirname(dirname(__FILE__)).'/main.php');

function SAPI_returnData($format, $result)
{
	switch ($format) {
		case 'jsonp':
			SERIA_Template::disable();
			header('Content-Type: text/javascript');
			/* Javascript: */
			?>
				<?php echo $_GET['jsonp']; ?>(<?php echo SERIA_Lib::toJSON($result); ?>);
			<?php
			die();
		case 'json':
		default:
			SERIA_Template::disable();
			header('Content-Type: application/json');
			echo SERIA_Lib::toJSON($result);
			die();
	}
}
function SAPI_error($httpErrorCode, $title, $message, $die, $extraHTML)
{
	$error = $message;
	if (($pos = strpos($error, 'ERROR:')) !== false)
		$error = substr($error, $pos);
	else
		$error = $title;
	if (($pos = strpos($error, ') in')) !== false)
		$error = substr($error, 0, $pos + 1);
	else
		$error = $title;
	echo SERIA_Lib::toJSON(array('error' => $error));
	if ($die)
		die();
}
SERIA_Hooks::listen(SERIA_Base::DISPLAY_ERROR_HOOK, 'SAPI_error');

$query = SERIA_Url::current()->getQuery(null);
$get = array();
SERIA_Url::parse_str($query, $get);
$post = $_POST;
$authUser = false;
if (SAPI::isAuthenticatedMessage($get)) {
	$sapiTokens = SERIA_Meta::all('SAPI_Token');
	$auth = false;
	foreach ($sapiTokens as $sapiToken) {
		if (($auth = SAPI::getAuthenticatedMessage($get, $sapiToken->get('secret'))) !== false) {
			if (sizeof($post)) {
				if (($post = SAPI::getAuthenticatedMessage($post, $sapiToken->get('secret'))) === false) {
					die(SERIA_Lib::toJSON(array('error' => 'Authentication failure')));
				}
			}
			break;
		}
	}
	if ($auth !== false) {
		$authUser = $sapiToken->get('user');
		$get = $auth;
	} else {
		die(SERIA_Lib::toJSON(array('error' => 'Authentication failure')));
	}
} else if (SAPI::isAuthenticatedMessage($post)) {
	$apiPath = $get['apiPath'];
	unset($get['apiPath']);
	if ($get) {
		die(SERIA_Lib::toJSON(array('error' => 'Authentication failure')));
	}
	$get = array('apiPath' => $apiPath);
	$sapiTokens = SERIA_Meta::all('SAPI_Token');
	$auth = false;
	foreach ($sapiTokens as $sapiToken) {
		if (($auth = SAPI::getAuthenticatedMessage($post, $sapiToken->get('secret'))) !== false)
			break;
	}
	if ($auth !== false) {
		$authUser = $sapiToken->get('user');
		$post = $auth;
	} else {
		die(SERIA_Lib::toJSON(array('error' => 'Authentication failure')));
	}
}

if ($authUser)
	SERIA_Base::user($authUser, true);

if (isset($get['apiPath']) && $get['apiPath']) {
	$path = explode('/', $get['apiPath']);
	unset($get['apiPath']);
	if (count($path) == 2) {
		$class = $path[0];
		$method = $path[1];
	} else
		throw new SERIA_Exception('API path must be class/method');
} else
	throw new SERIA_Exception('API requires path');
if (isset($get['apiReturn'])) {
	$returnFormat = $get['apiReturn'];
	unset($get['apiReturn']);
} else
	$returnFormat = 'json';
$GLOBALS['returnFormat'] = $returnFormat;

switch ($returnFormat) {
	case 'json':
		break; /* Ok */
	case 'jsonp':
		if (!isset($_GET['jsonp']) || !$_GET['jsonp']) {
			$GLOBALS['returnFormat'] = 'json';
			throw new SERIA_Exception('jsonp return-data format requires the jsonp-parameter with the callback name');
		}
		break; /* Ok */
	default:
		$GLOBALS['returnFormat'] = 'json';
		throw new SERIA_Exception('Unknown return-data format: '.$returnFormat);
}

switch ($_SERVER['REQUEST_METHOD']) {
	case 'POST':
		$result = SAPI::post($class, $method, $post, $get);
		break;
	case 'PUT':
		$result = SAPI::put($class, $method, $get);
		break;
	case 'DELETE':
		$result = SAPI::delete($class, $method, $get);
		break;
	case 'GET':
	case 'HEAD':
	default:
		$result = SAPI::get($class, $method, $get);
}

SAPI_returnData($returnFormat, $result);
