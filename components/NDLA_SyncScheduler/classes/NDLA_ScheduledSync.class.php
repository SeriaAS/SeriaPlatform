<?php

class NDLA_ScheduledSync extends SERIA_MetaObject
{
	public static function Meta($instance=null)
	{
		return array(
			'table' => '{ndla_scheduled_sync}',
			'fields' => array(
				'syncDate' => array('datetime required', _t('Sync time')),
				'description' => array('text required', _t('Description'))
			)
		);
	}

	public static function addAction()
	{
		$fields = array(
			'syncDate',
			'description'
		);
		$obj = new self();
		$obj->set('syncDate', date('Y-m-d H:i:s'));
		$action = new SERIA_ActionForm('AddScheduledSync', $obj, $fields);
		if ($action->hasData()) {
			$obj->set('syncDate', $action->get('syncDate'));
			$obj->set('description', $action->get('description'));
			$action->errors = SERIA_Meta::validate($obj);
			if ($action->errors === false) {
				SERIA_Meta::save($obj);
				$action->success = true;
			}
		}
		return $action;
	}
	public function editAction()
	{
		$fields = array(
			'syncDate',
			'description'
		);
		$action = new SERIA_ActionForm('EditScheduledSync', $this, $fields);
		if ($action->hasData()) {
			$this->set('syncDate', $action->get('syncDate'));
			$this->set('description', $action->get('description'));
			$action->errors = SERIA_Meta::validate($this);
			if ($action->errors === false) {
				SERIA_Meta::save($this);
				$action->success = true;
			} else
				throw new SERIA_ValidationException('Validation errors', $action->errors);
		}
		return $action;
	}
}