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
			(function () {
				var checkXdmAclHost = function (host) {
					<?php
						if (defined('XDM_AJAX_ACL')) {
							$acl = XDM_AJAX_ACL;
							$acl = explode(',', $acl);
							foreach ($acl as &$allow)
								$allow = trim($allow);
							unset($allow);
						} else {
							$acl = array();
						}
						$acl[] = SERIA_Url::current()->getHost();
					?>
					var acls = <?php echo SERIA_Lib::toJSON($acl); ?>;
					var ok = false;
					for (i in acls) {
						if (acls.hasOwnProperty(i)) {
							var acl = acls[i];
							if (acl.substr(0, 1) == '.' && host.length > acl.length && host.substr(host.length - acl.length) == acl) {
								ok = true;
								break;
							} else if (host == acl) {
								ok = true;
								break;
							}
						}
					}
					return ok;
				}
				var getRequestingHost = function () {
					var url = window.location.href;
					if (url.indexOf('http://') == 0) {
						url = url.substr(7);
					} else if (url.indexOf('https://') == 0) {
						url = url.substr(8);
					} else
						return false;
					var i = url.indexOf('/');
					var host = '';
					if (i > 0)
						host = url.substr(0, i);
					else if (i == -1)
						host = url;
					return host;
				}
				var host = getRequestingHost();
			
				if (checkXdmAclHost(host))
					<?php echo $_GET['jsonp']; ?>(<?php echo SERIA_Lib::toJSON($result); ?>);
				else
					alert('Access denied to an XDM API (Add hostname '+host+' to XDM_AJAX_ACL in _config.php)!');
			})();
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
