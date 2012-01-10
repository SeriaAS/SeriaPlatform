<?php
/**
 * Returns JSON property array for a user and namespace:
 * Parameters:
 *   user_id
 *   namespace
 * Returns JSON:
 *   array('error' => false/string, 'properties' => array(name => value))
 *
 * - J-E P
 */

require_once(dirname(__FILE__).'/../../../main.php');

SERIA_ProxyServer::noCache();

try {
	$user = SERIA_User::createObject($_REQUEST['user_id']);
	if (SERIA_Base::user() === false || $user->get('id') != SERIA_Base::user()->get('id'))
		throw new SERIA_Exception(_t('Access denied reading user properties!'));

	$storage = new SERIA_JsonUserPropertyStorageDriver($user);

	$data = $storage->getAll($_REQUEST['namespace']);

	SERIA_Lib::publishJSON(array(
		'error' => false,
		'properties' => $data
	));
} catch (Exception $e) {
	SERIA_Lib::publishJSON(array(
		'error' => $e->getMessage(),
		'properties' => array()
	));
}
