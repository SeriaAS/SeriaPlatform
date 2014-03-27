<?php

class NDLA_SyncLog extends SERIA_MetaObject
{
	public static function Meta($instance=null)
	{
		return array(
			'table' => '{ndla_sync_log}',
			'fields' => array(
				'executedAt' => array('datetime required', _t('Time of sync')),
				'description' => array('text required', _t('Description')),
				'syncType' => array('text', _t('Sync type')),
				'partialSync' => array('text', _t('Partial sync')),
			)
		);
	}

	/**
	 *
	 * Write a log entry in the sync log with current time.
	 * @param string $description Probably 'Polled: Did not sync'
	 */
	public static function writeSyncLogEntry($description)
	{
		$log = new self();
		$log->set('executedAt', date('Y-m-d H:i:s'));
		$log->set('description', $description);
		SERIA_Meta::save($log);
	}

	/**
	 *
	 * Loads the sync2 script and returns the available sync modes.
	 */
	public static function loadSync2()
	{
		static $avail = null;

		if (!defined('NDLA_SYNC_SCRIPT_2'))
			return null;
		if ($avail !== null)
			return $avail;
		$avail = require(NDLA_SYNC_SCRIPT_2);
		return $avail;
	}

	public static function startSync($description, $syncType, $partial=NULL)
	{
		$log = new self();
		$log->set('executedAt', date('Y-m-d H:i:s'));
		$log->set('description', $description);
		$log->set('syncType', $syncType);
		if ($partial !== NULL)
			$log->set('partial', 'Partial:'.implode(',', $partial));
		SERIA_Meta::save($log);
		$syncAvail = self::loadSync2();
		if (isset($syncAvail[$syncType])) {
			list($caption, $callable) = $syncAvail[$syncType];
			call_user_func($callable, $partial);
		} else
			throw new SERIA_Exception('Sync type not avail: '.$syncType);
	}

	/**
	 *
	 * Do a sync now.
	 * @param string $description A description of type 'Manual sync', or 'Automatic sync'.
	 */
	public static function doSync($description, $syncTypes=null)
	{
		if (defined('NDLA_SYNC_SCRIPT_2')) {
			foreach ($syncTypes as $syncType)
				self::startSync($description, $syncType);
			return;
		}
		$log = new self();
		$log->set('executedAt', date('Y-m-d H:i:s'));
		$log->set('description', $description);
		SERIA_Meta::save($log);
		/* Do the sync!: */
		if ($syncTypes !== null)
			throw new SERIA_Exception('This sync component is not configured for sync2.');
		require(NDLA_SYNC_SCRIPT);
	}

	/**
	 *
	 * Typically called from a maintain hook to check whether any syncs are scheduled now.
	 */
	public static function pollAutomaticSyncs()
	{
		/* Get latest sync */
		$lastSync = SERIA_Meta::all('NDLA_SyncLog')->order('-executedAt')->limit(0, 1)->current();
		if ($lastSync)
			$fromTime = strtotime($lastSync->get('executedAt'));
		else {
			/* Create a starting-point */
			self::writeSyncLogEntry('Polled: Did not sync. (Started sync-scheduler from here)');
			return;
		}
		$toTime = time();
		/* Get hours */
		$weekdays = array(
			'Sunday',
			'Monday',
			'Tuesday',
			'Wednesday',
			'Thursday',
			'Friday',
			'Saturday'
		);
		$fromHour = intval(date('H', $fromTime));
		if (intval(date('i', $fromTime)) != 0 || intval(date('s', $fromTime)) != 0)
			$fromHour++;
		$fromDay = intval(date('j', $fromTime));
		$fromMonth = intval(date('m', $fromTime));
		$fromYear = intval(date('Y', $fromTime));
		$point = mktime($fromHour, 0, 0, $fromMonth, $fromDay, $fromYear);
		$rotationMap = array();
		for ($i = 0; $i < 6; $i++)
			for ($j = 0; $j < 24; $j++)
				$rotationMap[$i][$j] = 0; /* Not visited */
		$hours = array();
		$doSync = false;
		while ($point < $toTime) {
			$hour = intval(date('H', $point));
			$wday = intval(date('w', $point));
			SERIA_Base::debug('Checking '.date('Y-m-d H:i:s', $point).' wday '.$wday.' hour '.$hour);
			if ($rotationMap[$wday][$hour])
				break; /* Did go through the whole weekly schedule without sync */
			$rotationMap[$wday][$hour] = 1;
			$wdayName = $weekdays[$wday];
			if (!isset($hours[$hour]))
				$hours[$hour] = SERIA_Meta::all('NDLA_WeeklySyncSchedule')->where('hour = :hour', array('hour' => $hour))->current();
			if ($hours[$hour]) {
				if ($hours[$hour]->get($wdayName)) {
					SERIA_Base::debug('Syncing because scheduled at '.$wdayName.' '.$hour.' o\'clock.');
					$doSync = true;
				}
			}
			if ($doSync)
				break;
			$fromHour = intval(date('H', $point)) + 1;
			$fromDay = intval(date('j', $point));
			$fromMonth = intval(date('m', $point));
			$fromYear = intval(date('Y', $point));
			$point = mktime($fromHour, 0, 0, $fromMonth, $fromDay, $fromYear);
		}
		if ($doSync) {
			if (!self::syncPause())
				self::doSync('Scheduled sync');
			else
				self::writeSyncLogEntry('Not syncing: Paused');
			return;
		}
		/* Check schedules */
		$fromDateTime = new SERIA_DateTimeMetaField($fromTime);
		$toDateTime = new SERIA_DateTimeMetaField($toTime);
		$syncs = SERIA_Meta::all('NDLA_ScheduledSync')->where('syncDate > :fromTime AND syncDate <= :toTime', array('fromTime' => $fromDateTime->toDbFieldValue(), 'toTime' => $toDateTime->toDbFieldValue()))->count();
		if ($syncs) {
			if (!self::syncPause())
				self::doSync('Scheduled sync');
			else
				self::writeSyncLogEntry('Not syncing: Paused');
		} else if (($toTime - $fromTime) > 2592000) { /* 30 days since last sync or logging */
			$minute = intval(date('i', $toTime));
			if ($minute < 45) {
				$fromDateTime = new SERIA_DateTimeMetaField($toTime);
				$toDateTime = new SERIA_DateTimeMetaField($toTime + 36000); /* 10 hrs */
				$nearFutureSyncs = SERIA_Meta::all('NDLA_ScheduledSync')->where('syncDate > :fromTime AND syncDate <= :toTime', array('fromTime' => $fromDateTime->toDbFieldValue(), 'toTime' => $toDateTime->toDbFieldValue()))->count();
				if (!$nearFutureSyncs)
					self::writeSyncLogEntry('Polled: Not syncing');
			}
		}
	}

	public static function getSyncType($key)
	{
		$avail = self::loadSync2();
		foreach ($avail as $k => $data) {
			list($caption, $callable) = $data;
			$fkey = 'mode_'.sha1(serialize($callable));
			if ($fkey == $key)
				return $k;
		}
		return NULL;
	}

	/**
	 *
	 * Manual sync actions with mode selection
	 */
	public static function multimodeSyncAction()
	{
		$action = new SERIA_ActionForm('ManualMultimodeSync');
		$spec = array();
		$map = array();
		$avail = self::loadSync2();
		if ($avail !== null) {
			foreach ($avail as $key => $data) {
				list($caption, $callable) = $data;
				$fkey = 'mode_'.sha1(serialize($callable));
				$map[$fkey] = $key;
				$spec[$fkey] = array(
					'fieldtype' => 'checkbox',
					'caption' => $caption,
					'validator' => new SERIA_Validator(array()),
					'value' => '0'
				);
			}
		}
		foreach ($spec as $name => $fspec)
			$action->addField($name, $fspec, $fspec['value']);
		if ($action->hasData()) {
			if ($avail !== null) {
				$sync = array();
				$captions = array();
				foreach ($map as $fkey => $key) {
					if ($action->get($fkey)) {
						$sync[] = $key;
						$captions[] = $spec[$fkey]['caption'];
					}
				}
				if (!$sync) {
					$action->errors = array(_t('No sync started.'));
					return $action;
				}
				self::doSync('Manual sync: '.implode(', ', $captions), $sync);
			} else
				self::doSync('Manual sync');
			$action->success = true;
		}
		return $action;
	}
	/**
	 *
	 * The multimode form has dynamic fields. There is no way to get the fieldnames from an action-form.
	 * Therefore this method returns the fieldnames.
	 * @return array
	 */
	public static function getMultimodeFields()
	{
		$avail = self::loadSync2();
		if ($avail !== null) {
			$keys = array();
			foreach ($avail as $key => $data) {
				list($caption, $callable) = $data;
				$fkey = 'mode_'.sha1(serialize($callable));
				$keys[] = $fkey;
			}
			return $keys;
		} else
			return null;
	}

	protected static function syncPause($set = null)
	{
		if ($set === null) {
			if (SERIA_Base::getParam('NDLA_SYNC_pausesched'))
				return true;
			else
				return false;
		}

		SERIA_Base::setParam('NDLA_SYNC_pausesched', $set);
	}

	/**
	 *
	 * Pause the sync schedules. Returns null if already stopped.
	 * @return SERIA_ActionUrl
	 */
	public static function stopSyncingAction()
	{
		if (!self::syncPause()) {
			$action = new SERIA_ActionUrl('SyncPause');
			if ($action->invoked()) {
				self::syncPause(true);
				$action->success = true;
			}
			return $action;
		} else
			return null;
	}
	/**
	 *
	 * Resume the sync schedules. Returns null if already started.
	 */
	public static function startSyncingAction()
	{
		if (self::syncPause()) {
			$action = new SERIA_ActionUrl('SyncResume');
			if ($action->invoked()) {
				self::syncPause(false);
				$action->success = true;
			}
			return $action;
		} else
			return null;
	}
}
