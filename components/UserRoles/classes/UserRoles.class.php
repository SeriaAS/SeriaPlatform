<?php

class UserRoles extends MetaCompatibleObject
{
	const HOOK = 'UserRoles::HOOK';
	const USER_META = 'UserRoles::USER_META';

	protected static $knownRoles = array();
	protected static $hookThrown = false;

	protected $user;
	protected $roleMembership = array();

	public static function getKnownRoles()
	{
		if (!self::$hookThrown) {
			self::$hookThrown = true;
			$hookres = SERIA_Hooks::dispatch(UserRoles::HOOK);
			foreach ($hookres as $roles) {
				foreach ($roles as $role)
					self::$knownRoles[$role['role']] = $role;
			}
			$roles = SERIA_Meta::all('AdditionalUserRole');
			foreach ($roles as $role) {
				$name = $role->get('role');
				$caption = $role->get('caption');
				if (!isset(self::$knownRoles[$name]))
					self::$knownRoles[$name] = array(
						'role' => $name,
						'caption' => $caption,
						'translatedCaption' => $caption
					);
			}
		}
		return self::$knownRoles;
	}
	public static function newRole($role, $caption)
	{
		$additionalRole = new AdditionalUserRole();
		$additionalRole->set('role', $role);
		$additionalRole->set('caption', $caption);
		SERIA_Meta::save($additionalRole);
		self::$knownRoles[$role] = array(
			'role' => $role,
			'caption' => $caption,
			'translatedCaption' => $caption
		);
	}

	public static function Meta()
	{
		if (!self::$hookThrown)
			self::getKnownRoles();

		$fields = array();
		foreach (self::$knownRoles as $name => $role)
			$fields[$name] = array('boolean', $role['translatedCaption']." (".$name.")");
		return array(
			'table' => false,
			'fields' => $fields
		);
	}

	public static function hookUserEditForm(&$form, &$user)
	{
		$userRoles = new self($user);
		$userRolesForm = new UserRolesForm($userRoles);
		$form->subForm('user_roles', $userRolesForm);
	}
	public static function boot()
	{
		SERIA_Hooks::listen('SERIA_UserEditForm', array('UserRoles', 'hookUserEditForm'));
	}

	public function __construct(SERIA_User &$user)
	{
		$this->user =& $user;
		if (($membership = $user->getMeta(UserRoles::USER_META)) !== false) {
			if (!self::$hookThrown)
				self::getKnownRoles();
			$this->roleMembership = unserialize($membership);
			foreach ($this->roleMembership as $role => $caption) {
				if (!isset(self::$knownRoles[$role]))
					self::newRole($role, $caption);
			}
		}
	}

	public function getUser()
	{
		return $this->user;
	}
	public function getRoles()
	{
		$mebership = array();
		foreach ($this->roleMembership as $role => $caption)
			$membership[$role] = self::$knownRoles[$role]['translatedCaption'];
		return $membership;
	}

	public function get($roleName)
	{
		return isset($this->roleMembership[$roleName]);
	}
	public function set($roleName, $value)
	{
		if ($value)
			$this->addRole($roleName);
		else
			$this->removeRole($roleName);
	}

	public function addRole($roleName)
	{
		if (isset($this->roleMembership[$roleName]))
			return true; /* Already member of */
		if (isset(self::$knownRoles[$roleName])) {
			$role = self::$knownRoles[$roleName];
			$this->roleMembership[$roleName] = $role['caption'];
			return true;
		}
		throw new SERIA_NotFoundException('The role does not exist: '.$roleName);
	}
	public function removeRole($roleName)
	{
		if (isset($this->roleMembership[$roleName]))
			unset($this->roleMembership[$roleName]);
		return true;
	}

	public function save()
	{
		$this->user->setMeta(UserRoles::USER_META, serialize($this->roleMembership));
	}
	public function delete()
	{
		throw new SERIA_Exception('Roles cannot be deleted!');
	}

	public function getCustomRoleAction()
	{
		$object = new AdditionalUserRole();
		$action = new SERIA_ActionForm('customRole', $object, array('role', 'caption'));
		if ($action->hasData()) {
			$object->set('role', $action->get('role'));
			$object->set('caption', $action->get('caption'));
			$action->errors = SERIA_Meta::validate($object);
			if ($action->errors === false) {
				self::newRole($action->get('role'), $action->get('caption'));
				$this->addRole($action->get('role'));
				$this->save();
				$action->success = true;
			}
		}
		return $action;
	}
}
