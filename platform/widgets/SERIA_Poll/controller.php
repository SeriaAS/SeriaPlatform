<?php

class SERIA_PollWidgetStorageRecord extends SERIA_ActiveRecord
{
	protected $tableName = '_widgets_poll';
	protected $usePrefix = true;
	protected $useGuid = true;
}

$SERIA_PollWidgetStorage_compat = SERIA_FluentActiveRecord::createCompatLayer('SERIA_PollWidgetStorageRecord', 'SERIA_PollWidgetStorage');

class SERIA_PollWidgetStorageForm extends SERIA_Form {
	public static function getFormSpec()
	{
		$spec = SERIA_Fluent::getFieldSpec('SERIA_PollWidgetStorage');
//		$spec = SERIA_PollWidgetStorage::getFi__REMOVED__eldSpec();
		unset($spec['widget_id']); /* hide */
		$spec['name']['caption'] = _t('Poll name:');
		$spec['options']['caption'] = _t('Options:', array(), 'poll-select-options');
		$spec['options']['fieldtype'] = 'textarea';
		return $spec;
	}
	public static function caption()
	{
		return _t('Seria Platform Poll Widget: Poll settings');
	}
	public function _handle($data)
	{
		$spec = self::getFormSpec();
		foreach ($spec as $nam => &$dont_touch) {
			if (isset($data[$nam]))
				$this->object->set($nam, $data[$nam]);
		}
		$this->object->save();
	}
}

class SERIA_PollWidget extends SERIA_Widget
{
	protected $attachedEvents = array('DELETE' => 'onDelete');
	protected $localVariables = array();
	public $errors = false;

	protected function onDelete($source) {
		$this->delete();
	}

	public function getGUID()
	{
		return $this->guid;
	}

	public function action_index() {
	}
	public function action_admin() {
	}

	public function getPoll($id)
	{
		$query = new SERIA_FluentQuery('SERIA_PollWidgetStorage', 'id = :id', array('id' => $id));
		return $query->limit(1)->next();
	}
	public function getCurrentPoll()
	{
		$poll_id = $this->get('current_poll');
		if ($poll_id)
			return $this->getPoll($poll_id);
		try {
			$poll = new SERIA_PollWidgetStorageRecord();
			$poll->widget_id = $this->getId();
			$poll->save();
			$this->set('current_poll', $poll->id);
		} catch (PDOException $e) {
			return false;
		}
		return new SERIA_PollWidgetStorage($poll); 
	}
}

?>
