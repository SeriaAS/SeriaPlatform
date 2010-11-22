<?php

class PowerpointConverterSystem
{
	public static function addToQueue($file, $ext)
	{
		$q = SERIA_Queue::createObject('PowerpointConverterSystem', 'PowerpointConverterSystem Taskqueue', 'This taskqueue sequentially converts powerpoints received.');
		$t = new SERIA_QueueTask('ConvertPPTtoPNG', serialize(array(
			'id' => $file->get('id'),
			'ext' => $ext 
		)));
		$q->add($t);
	}
	public static function fromMaintain()
	{
		/*
		 * Recovery time after a crash will be around 10 minutes..
		 */
		if (!SERIA_Base::setParamIfNotExists('powerpoint_converter_system_running', time())) {
			if (!SERIA_Base::setParamIfEqualTo('powerpoint_converter_system_running', time(), 0)) {
				$ts = SERIA_Base::getParam('powerpoint_converter_system_running', true);
				if (($ts + 600) > time()) {
					SERIA_Base::debug('Powerpoint system is already running.');
					return; /* Not timed out, state is 'running' */
				}
				/*
				 * Clear state if we get there first
				 */
				SERIA_Base::debug('Powerpoint system seems to have crashed. Resetting...');
				SERIA_Base::setParamIfEqualTo('powerpoint_converter_system_running', time(), $ts); /* Steal the 'running' state */
			}
		}
		$mytime = SERIA_Base::getParam('powerpoint_converter_system_running', true);
		SERIA_Base::debug('Starting one powerpoint convert job if there is one.');
		try {
			$q = SERIA_Queue::createObject('PowerpointConverterSystem', 'PowerpointConverterSystem Taskqueue', 'This taskqueue sequentially converts powerpoints received.');
			while (($t = $q->fetch(700)) !== false) {
				SERIA_Base::debug('Starting one powerpoint job...');
				/* Convert now */
				SERIA_Base::addFramework('powerpoint');
				$data = unserialize($t->get('data'));
				try {
					try {
						$fileobj = SERIA_File::createObject($data['id']);
					} catch (Exception $e) {
						$fileobj = null;
						throw $e;
					}
					$objects = SERIA_MSPowerPointConverter::convertToFiles($fileobj, $data['ext']);
					SERIA_Base::debug('Job succeeded.');
					$tracking = new SERIA_MSPowerPointConverterStatus($fileobj->get('id'));
					$tracking->lock();
					$tracking->set('powerpoint_converted', true);
					$tracking->save(true/*-unlock*/);
					$fileobj->setMeta('powerpoint_converted', true);
					$t->success();
				} catch (Exception $e) {
					SERIA_Base::debug('Job failed: '.$e->getMessage());
					if ($fileobj !== null)
						$fileobj->setMeta('powerpoint_exception', $e->getMessage());
					$t->failed($e->getMessage());
					if (SERIA_DEBUG) {
						$trace = $e->getTraceAsString();
						if (strpos($trace, "\n") === false && strpos($trace, "\r") !== false)
							$trace = str_replace("\r", "\n", $trace);
						$trace = str_replace("\r", '', $trace);
						$trace = explode("\n", $trace);
						foreach ($trace as $ln) {
							if ($ln)
								SERIA_Base::debug($ln);
						}
					}
				}
				SERIA_Base::debug('Looking for another job..');
			}
			SERIA_Base::debug('Possibly completed one powerpoint convert job.');
			/* Clear the running state */
			SERIA_Base::setParamIfEqualTo('powerpoint_converter_system_running', 0, $mytime);
		} catch (Exception $e) {
			SERIA_Base::setParamIfEqualTo('powerpoint_converter_system_running', 0, $mytime);
			throw $e;
		}
	}
}
