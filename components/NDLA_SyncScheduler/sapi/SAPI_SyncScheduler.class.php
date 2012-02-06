<?php

class SAPI_SyncScheduler extends SAPI
{
	public static function available()
	{
		if (SERIA_Base::isAdministrator()) {
			$sync2 = NDLA_SyncLog::loadSync2();
			if ($sync2 !== null) {
				$avail = array();
				foreach ($sync2 as $sync) {
					$avail[$sync[1]] = $sync[0];
				}
				return array(
					'available' => $avail
				);
			} else
				return array(
					'available' => array(),
					'error' => 'Sync2 is not available.'
				);
		} else
			return array(
				'available' => array(),
				'error' => 'Access denied!'
			);
	}
	public static function syncNow($syncTypes)
	{
		if (SERIA_Base::isAdministrator()) {
			$sync2 = NDLA_SyncLog::loadSync2();
			if ($sync2 !== null) {
				$avail = array();
				foreach ($sync2 as $key => $sync) {
					$avail[$sync[1]] = array($key, $sync[0]);
				}
			} else
				return array(
					'error' => 'Sync2 is not available.'
				);
			$syncs = explode(',', $syncTypes);
			$notfound = array();
			foreach ($syncs as &$sync) {
				if (isset($avail[$sync]))
					$sync = $avail[$sync][0];
				else
					$notfound[] = $sync;
			}
			unset($sync);
			if ($notfound)
				return array(
					'error' => 'Sync type(s) not found: '.implode(', ', $notfound)
				);
			NDLA_SyncLog::doSync('Started by API', $syncs);
			return array(
				'status' => 'ok'
			);
		} else
			return array(
				'error' => 'Access denied!'
			);
	}
	public static function get_syncNow()
	{
		throw new SERIA_Exception('Sync-now is not available as a GET-request!');
	}
	public static function put_syncNow()
	{
		throw new SERIA_Exception('Sync-now is not available as a PUT-request!');
	}
	public static function delete_syncNow()
	{
		throw new SERIA_Exception('Sync-now is not available as a DELETE-request!');
	}
}
