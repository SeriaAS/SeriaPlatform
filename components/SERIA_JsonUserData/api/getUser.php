<?php
/**
 * Get the user data as JSON.
 * Parameters:
 *   user_id
 * Returns:
 *   array(name => value)
 */

require_once(dirname(__FILE__).'/../../../main.php');

try {
	$user = SERIA_User::createObject($_REQUEST['user_id']);
	if (SERIA_Base::user() === false || $user->get('id') != SERIA_Base::user()->get('id'))
		throw new SERIA_Exception(_t('Access denied reading user data!'));

	$values = array(
		'firstName',
		'lastName',
		'displayName',
		'email',
		'isAdministrator',
		'guestAccount'
	);

	$data = array();
	foreach ($values as $value)
		$data[$value] = $user->get($value);

	SERIA_Lib::publishJSON(array(
		'error' => false,
		'data' => $data
	));
} catch (Exception $e) {
	SERIA_Lib::publishJSON(array(
		'error' => $e->getMessage(),
		'data' => false
	));
}
