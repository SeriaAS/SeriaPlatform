<?php

class SERIA_MSPowerPointConverter implements SERIA_RPCServer
{
	public static function convertToFiles($fileobj, $extension='png', $progressCallback=null)
	{
		$tracking = new SERIA_MSPowerPointConverterStatus($fileobj->get('id'));
		$objects = array();
		$filename = $fileobj->get('localPath');
		if (!$filename) {
			/* localPath has stopped working */
			$filename = SERIA_UPLOAD_ROOT . '/' . $fileobj->get('filename');
		}
		$fpi = pathinfo($filename);
		$pp = new SERIA_PowerpointMSOfficeConverterLowlevel($filename);
		$slides = $pp->getNumberOfSlides();
		$fileobj->setMeta('powerpoint_num_slides', $slides);
		$tracking->lock();
		$tracking->set('powerpoint_num_slides', $slides);
		$tracking->save(true/*-unlock*/);
		for ($i = 1; $i <= $slides; $i++) {
			$tmpfile = tempnam(sys_get_temp_dir(), 'mspconv');
			$pp->exportSlide($i, $tmpfile, $extension);
			try {
				$sf = new SERIA_File($tmpfile, $fpi['filename'].$i.'.'.$extension, false, $fileobj->get('id'), 'ms_powerpoint_slide');
				$sf->increaseReferrers();
			} catch (Exception $e) {
				if (file_exists($tmpfile))
					unlink($tmpfile);
				throw $e;
			}
			$tracking->lock();
			$tracking->set('powerpoint_latest_converted_slide', $i);
			$tracking->set('powerpoint_convert_updated', time());
			$tracking->save(true/*-unlock*/);
			$fileobj->setMeta('powerpoint_latest_converted_slide', $i);
			$fileobj->setMeta('powerpoint_convert_updated', time());
			if ($progressCallback !== null)
				call_user_func($progressCallback, $fileobj, $i, $slides, $sf);
		}
		return $objects;
	}

	protected static function offlineProgress($fileobj, $current, $slides, $slideobj)
	{
		if ($current == 1) {
			/* first call */
			$fileobj->setMeta('powerpoint_num_slides', $slides);
		}
		$fileobj->setMeta('powerpoint_latest_converted_slide', $current);
		$fileobj->setMeta('powerpoint_convert_updated', time());
	}
	public static function rpc_convertToFiles($fileid, $extension)
	{
		SERIA_RPCHost::requireAuthentication();
		$tracking = new SERIA_MSPowerPointConverterStatus($fileid);
		set_time_limit(0);
		$fileobj = SERIA_File::createObject($fileid);
		$tracking->lock();
		$tracking->set('powerpoint_converted', false);
		$tracking->set('powerpoint_latest_converted_slide', 0);
		$tracking->set('powerpoint_exception', false);
		$tracking->save(true/*-unlock*/);
		$fileobj->setMeta('powerpoint_converted', false);
		$fileobj->setMeta('powerpoint_latest_converted_slide', 0);
		$fileobj->setMeta('powerpoint_exception', false);
		if (RPCHost::isAsynchronous()) {
			PowerpointConverterSystem::addToQueue($fileobj, $extension);
		} else {
			$tries = 2;
			while ($tries > 0) {
				$tries--;
				try {
					$objects = self::convertToFiles($fileobj, $extension);
					$tracking->lock();
					$tracking->set('powerpoint_converted', true);
					$tracking->save(true/*-unlock*/);
					$fileobj->setMeta('powerpoint_converted', true);
					$ids = array();
					foreach ($objects as $obj)
						$ids[] = $obj->get('id');
					return $ids;
				} catch (Exception $e) {
					try {
						$powerpnt_exe = ProcessManagementComponent::getComponent()->getWin32ProcessList()->getProcessesByName('POWERPNT.EXE');
						foreach ($powerpnt_exe as $proc)
							$proc->kill(true);
						sleep(5);
					} catch (Exception $e) {
					}
					/* Fallthrough ... (with $e) */
				}
			}
			$tracking->lock();
			$tracking->set('powerpoint_exception', $e->getMessage());
			$tracking->save(true/*-unlock*/);
			$fileobj->setMeta('powerpoint_exception', $e->getMessage());
			throw $e;
		}
	}
	public static function rpc_startConvertToFiles($fileid, $extension)
	{
		SERIA_RPCHost::requireAuthentication();
		$tracking = new SERIA_MSPowerPointConverterStatus($fileid);
		$tracking->lock();
		$tracking->set('powerpoint_converted', false);
		$tracking->set('powerpoint_latest_converted_slide', 0);
		$tracking->set('powerpoint_exception', false);
		$tracking->save(true/*-unlock*/);
		$fileobj = SERIA_File::createObject($fileid);
		$fileobj->setMeta('powerpoint_converted', false);
		$fileobj->setMeta('powerpoint_latest_converted_slide', 0);
		$fileobj->setMeta('powerpoint_exception', false);
		PowerpointConverterSystem::addToQueue($fileobj, $extension);
		return true;
	}
}