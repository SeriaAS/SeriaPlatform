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

SERIA_Base::addClassPath(SERIA_ROOT.'/seria/components/UserRoles/classes/*.class.php');

UserRoles::boot();

SERIA_Components::addComponent($comp = new UserRolesComponent());

SERIA_Hooks::dispatch('UserRolesComponent', $comp);
