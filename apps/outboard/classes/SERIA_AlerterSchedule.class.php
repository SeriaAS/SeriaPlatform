<?php

class SERIA_AlerterSchedule extends SERIA_MetaObject
{
	protected $channels = null;
	protected $available = null;

	public static function Meta($instance = null)
	{
		return array(
			'table' => '{alerter_message_schedule}',
			'fields' => array(
				'message' => array('SERIA_AlerterMessage required', _t('Message')),
				'start' => array('datetime required', _t('Start')),
				'stop' => array('datetime required', _t('Stop')) 
			)
		);
	}
	protected function MetaBeforeDelete()
	{
		if (!parent::MetaBeforeDelete())
			return false;
		$scheduled = SERIA_Meta::all('SERIA_AlerterScheduleChannel')->where('scheduled = :scheduled', array('scheduled' => $this->get('id')));
		foreach ($scheduled as $sched)
			SERIA_Meta::delete($sched);
		return true;
	}

	public function editAction()
	{
		if (!$this->get('message'))
			throw new SERIA_Exception('The schedule must be attached to a message.');
		$action = new SERIA_ActionForm('SERIA_AlerterSchedule_edit', $this, array(
			'start',
			'stop'
		));
		if ($action->hasData()) {
			$this->set('start', $action->get('start'));
			$this->set('stop', $action->get('stop'));
			$action->errors = SERIA_Meta::validate($this);
			if ($action->errors === false) {
				SERIA_Meta::save($this);
				$action->success = true;
			}
		}
		$this->available = array();
		$channels = SERIA_Meta::all('SERIA_AlerterChannel');
		foreach ($channels as $channel) {
			$this->available[$channel->get('id')] = $channel;
			$action->addField(
				'channel'.$channel->get('id'),
				array(
					'caption' => $channel->get('name'),
					'fieldtype' => 'checkbox',
					'validator' => new SERIA_Validator(array())
				),
				$this->enabledOnChannel($channel->get('id'))
			);
			if ($action->hasData() && $action->errors === false) {
				if (($action->get('channel'.$channel->get('id')) ? true : false) != $this->enabledOnChannel($channel->get('id'))) {
					if ($action->get('channel'.$channel->get('id'))) {
						$chenable = new SERIA_AlerterScheduleChannel();
						$chenable->set('scheduled', $this);
						$chenable->set('channel', $channel);
						SERIA_Meta::save($chenable);
					} else {
						$chenable = SERIA_Meta::all('SERIA_AlerterScheduleChannel')->where('scheduled = :scheduled AND channel = :channel', array(
							'scheduled' => $this->get('id'),
							'channel' => $channel->get('id')
						));
						foreach ($chenable as $disc)
							SERIA_Meta::delete($disc);
					}
				}
			}
		}
		return $action;
	}

	public function availableChannels()
	{
		if ($this->available === null) {
			$this->available = array();
			$channels = SERIA_Meta::all('SERIA_AlerterChannel');
			foreach ($channels as $channel)
				$this->available[$channel->get('id')] = $channel;
		}
		return $this->available;
	}
	public function enabledChannels()
	{
		if ($this->channels === null) {
			$this->channels = array();
			$channels = SERIA_Meta::all('SERIA_AlerterScheduleChannel')->where('scheduled = :scheduled', array('scheduled' => $this->get('id')));
			foreach ($channels as $channel)
				$this->channels[] = $channel->get('channel')->get('id');
		}
		if ($this->available === null) {
			$this->available = array();
			$channels = SERIA_Meta::all('SERIA_AlerterChannel');
			foreach ($channels as $channel)
				$this->available[$channel->get('id')] = $channel;
		}
		$channels = array();
		foreach ($this->channels as $channelId)
			$channels[] = $this->available[$channelId];
		return $channels;
	}
	public function enabledOnChannel($channelId)
	{
		if ($this->channels === null) {
			$this->channels = array();
			$channels = SERIA_Meta::all('SERIA_AlerterScheduleChannel')->where('scheduled = :scheduled', array('scheduled' => $this->get('id')));
			foreach ($channels as $channel)
				$this->channels[] = $channel->get('channel')->get('id');
		}
		return in_array($channelId, $this->channels);
	}
}