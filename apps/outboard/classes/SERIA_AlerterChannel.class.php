<?php

class SERIA_AlerterChannel extends SERIA_MetaObject
{
	public static function Meta($instance = null)
	{
		return array(
			'table' => '{alerter_channel}',
			'fields' => array(
				'name' => array('name required', _t('Channel name'))
			)
		);
	}
	protected function MetaBeforeDelete()
	{
		if (!parent::MetaBeforeDelete())
			return false;
		$scheduled = SERIA_Meta::all('SERIA_AlerterScheduleChannel')->where('channel = :channel', array('channel' => $this->get('id')));
		foreach ($scheduled as $sched)
			SERIA_Meta::delete($sched);
		return true;
	}

	public function editAction()
	{
		$action = new SERIA_ActionForm('SERIA_AlerterChannel_edit', $this, array(
			'name'
		));
		if ($action->hasData()) {
			$this->set('name', $action->get('name'));
			$action->errors = SERIA_Meta::validate($this);
			if ($action->errors === false) {
				SERIA_Meta::save($this);
				$action->success = true;
			}
		}
		return $action;
	}
}