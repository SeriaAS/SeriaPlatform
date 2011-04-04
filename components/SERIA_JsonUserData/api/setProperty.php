<?php
/**
 * Set a user property.
 * Parameters:
 *   user_id,
 *   namespace,
 *   name,
 *   [value]: Abscense indicates that the value should be deleted.
 * Returns JSON:
 *   array(error => false/..)
 */

require_once(dirname(__FILE__).'/../../../main.php');

try {
	$user = SERIA_User::createObject($_REQUEST['user_id']);
	if (SERIA_Base::user() === false || $user->get('id') != SERIA_Base::user()->get('id'))
		throw new SERIA_Exception(_t('Access denied modifying user properties!'));

	$storage = new SERIA_JsonUserPropertyStorageDriver($user);

	if (isset($_REQUEST['name'])) {
		if (isset($_REQUEST['value']))
			$storage->set($_REQUEST['namespace'], $_REQUEST['name'], $_REQUEST['value']);
		else
			$storage->delete($_REQUEST['namespace'], $_REQUEST['name']);
	} else if (isset($_REQUEST['batch'])) {
		$batch = SERIA_Lib::fromJSON($_REQUEST['batch']);
		foreach ($batch as $name => $value) {
			$storage->set($_REQUEST['namespace'], $name, $value);
		}
	} else
		throw new SERIA_Exception('setProperty: Invalid argument!');

	SERIA_Lib::publishJSON(array(
		'error' => false
	));
} catch (Exception $e) {
	SERIA_Lib::publishJSON(array(
		'error' => $e->getMessage()
	));
}
