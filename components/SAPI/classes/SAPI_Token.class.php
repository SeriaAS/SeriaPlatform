<?php

class SAPI_Token extends SERIA_MetaObject
{
	public static function Meta($instance = null)
	{
		return array(
			'table' => '{sapi_token}',
			'fields' => array(
				'user' => array('SERIA_User required', _t('User')),
				'secret' => array('text required', _t('Shared secret')),
				'description' => array('text', _t('Description'))
			)
		);
	}

	public static function createAction()
	{
		$object = new self();
		$action = new SERIA_ActionForm('SAPI_Token', $object, array('description'));
		if ($action->hasData()) {
			$object->set('user', SERIA_Base::user());
			$object->set('secret', md5(mt_rand().mt_rand().mt_rand().mt_rand()));
			$object->set('description', $action->get('description'));
			$action->errors = SERIA_Meta::validate($object);
			if(!$action->errors)
				$action->success = SERIA_Meta::save($object);
		}
		return $action;
	}
	public function deleteAction($url)
	{
		$action = new SERIA_PostActionUrl('SAPI_Token_delete', $this);
		if ($action->invoked()) {
			$action->success = SERIA_Meta::delete($this);
			if (!$action->success)
				$action->error = _t('Unable to delete App-Key!');
			$action->actionReturn($url);
		}
		return $action;
	}
}