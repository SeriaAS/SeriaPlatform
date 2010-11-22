<?php

class UserRolesForm extends SERIA_Form
{
	protected $userRoles;

	public function __construct(UserRoles &$object)
	{
		parent::__construct(new FluentMetaObject($object));
		$this->userRoles =& $object;
	}

	public static function caption()
	{
		return _t('User roles');
	}

	public static function getFormSpec()
	{
		return array_merge(
			FluentMetaObject::getFormSpec('UserRoles'),
			array(
				'addCustomRole' => array(
					'fieldtype' => 'addCustomRole',
					'caption' => '',
					'weight' => 0,
				)
			)
		);
	}
	public function getFieldSpec()
	{
		return FluentMetaObject::getFieldSpec('UserRoles');
	}

	public function _handle($data)
	{
		$roles = array_keys(UserRoles::getKnownRoles());
		foreach ($roles as $role)
			$this->object->set($role, isset($data[$role]) && $data[$role]);
		$this->object->save();
	}

	public function _delete()
	{
		return $this->object->delete();
	}

	public function isDeletable()
	{
		if(!$this->object)
			return false;

		return SERIA_Base::isAdministrator();
	}

	public static function getPage($pageName)
	{
		if (file_exists(SERIA_ROOT.'/pages/UserRoles/'.$pageName.'.php'))
			return SERIA_HTTP_ROOT.'/pages/UserRoles/'.$pageName.'.php';
		else
			return SERIA_HTTP_ROOT.'/seria/components/UserRoles/pages/'.$pageName.'.php';
	}

	public function addCustomRole($name, $attributes = array())
	{
		return '<button style="width:130px" '.SERIA_Form::renderAttributes($attributes, array(
			'type' => 'button',
			'id' => $name,
			'onclick' => 'location.href = '.SERIA_Lib::toJSON(self::getPage('customRole').'?user='.urlencode($this->userRoles->getUser()->get('id')).'&from='.urlencode(SERIA_Url::current())).';',
		)).'>'._t('Create role').'</button>';
	}
}
