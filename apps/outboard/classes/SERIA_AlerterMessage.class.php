<?php

class SERIA_AlerterMessage extends SERIA_MetaObject
{
	public static function Meta($instance = null)
	{
		return array(
			'table' => '{alerter_message}',
			'fields' => array(
				'title' => array('name required', _t('Title')),
				'message' => array('text required', _t('Text'))
			)
		);
	}
	protected function MetaBeforeDelete()
	{
		if (!parent::MetaBeforeDelete())
			return false;
		$scheduled = SERIA_Meta::all('SERIA_AlerterSchedule')->where('message = :message', array('message' => $this->get('id')));
		foreach ($scheduled as $sched)
			SERIA_Meta::delete($sched);
		return true;
	}

	public function editAction()
	{
		$action = new SERIA_ActionForm('SERIA_AlerterMessage_edit', $this, array(
			'title', 'message'
		));
		if ($action->hasData()) {
			$this->set('title', $action->get('title'));
			$this->set('message', $action->get('message'));
			$action->errors = SERIA_Meta::validate($this);
			if ($action->errors === false) {
				SERIA_Meta::save($this);
				$action->success = true;
			}
		}
		return $action;
	}
}