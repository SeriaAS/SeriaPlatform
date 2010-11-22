<?php

class SERIA_AlerterScheduleChannel extends SERIA_MetaObject
{
	public static function Meta($instance = null)
	{
		return array(
			'table' => '{alerter_message_schedule_channel}',
			'fields' => array(
				'scheduled' => array('SERIA_AlerterSchedule required', _t('Scheduled')),
				'channel' => array('SERIA_AlerterChannel required', _t('Channel'))
			)
		);
	}
}