<?php
/**
 * UserRoles manages user-roles (surprise!). A hook is thrown to retrieve roles on the local system,
 * the handlers should return an array of roles:
 * array(
 *     array(
 *         'role' => 'moderator',
 *         'caption' => 'Moderator',
 *         'translatedCaption' => _t('Moderator')
 *     ),
 *     ...
 * )
 */


/**
 *
 *
 * @author Frode Boerli
 * @package SERIA_Outboard
 *
 */
class UserRolesManifest
{
	const SERIAL = 1;
	const NAME = 'UserRoles';

	public static $classPaths = array(
			'classes/*.class.php',
	);
	public static $dependencies = array(
			'SERIA_Mvc_Compat'
	);
}

function UserRolesInit() {
	UserRoles::boot();
	SERIA_Components::addComponent($comp = new UserRolesComponent());
	SERIA_Hooks::dispatch('UserRolesComponent', $comp);
}
