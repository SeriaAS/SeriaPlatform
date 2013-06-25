<?php

	/**
	*
	*
	* Log format: "%site %h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-agent}i\" %I %O %CPUUsage"
	* NEW Log format (As rewritten by Jon-Eirik): "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-agent}i\"
	*/

	class SERIA_Logging {

		const HITS_NS = 'SERIA_Logging:hits';
		const BANDWIDTH_NS = 'SERIA_Logging:bw';

		/**
		*	Warning! This may be a heavy operation. Especially if there are many years of log files that must be processed.
		*/
		static function resetProcessedLogFiles()
		{
			$counter = new SERIA_Counter(self::HITS_NS);
			$counter->emptyCounters();
			$counter = new SERIA_Counter(self::BANDWIDTH_NS);
			$counter->emptyCounters();
			$counter = null;
			$dirs = glob(SERIA_PRIV_ROOT.'/SERIA_Logging/processed/*', GLOB_ONLYDIR); /* Gives an array of dates */
			foreach($dirs as $dir)
			{
				$files = glob($dir.'/*.gz'); /***/
				foreach($files as $file)
				{
					$if = gzopen($file, 'rb');
					$of = fopen(SERIA_PRIV_ROOT.'/SERIA_Logging/incoming/'.basename($dir).'-'.basename($file).'-'.mt_rand(0,999999999).'.log', 'wb');
					while("" != ($data = fread($if, 65536))) fwrite($of, $data);
					fclose($if);
					fclose($of);
					unlink($file);
				}
				@rmdir($dir);
			}
		}

		static function processLogFiles()
		{
			function getAndRemoveFirstQuotePair(&$string) {
				if(($p1 = strpos($string, "\"")) !== false) {
					$newString = substr($string, 0, $p1);
					$rest = substr($string, $p1+1);
					$result = substr($rest, 0, $p1 = strpos($rest, "\""));
					$newString .= "*HERE*".substr($rest, $p1+1);
					$string = $newString;
					return $result;
				} else {
					return NULL;
				}
			}


			$oldTz = date_default_timezone_get();
			date_default_timezone_set("UTC");

			// Initialize counters
			$bandwidthCounter = new SERIA_Counter(self::BANDWIDTH_NS);
			$hitCounter = new SERIA_Counter(self::HITS_NS);
			$filesInQueue = glob(SERIA_PRIV_ROOT.'/SERIA_Logging/incoming/*'); /**/
			foreach($filesInQueue as $fileToProcess) {
				/**
				* Read a logfile, structure the line and put it in an array. if the read is successful - use seria_counter
				*/
				if(strpos($fileToProcess, '.tmp') !== false) continue;

				$statistics = array();
				$fp = fopen($fileToProcess, 'r');
				if(!file_exists(SERIA_PRIV_ROOT.'/SERIA_Logging/processed/'.date('Y-m-d')))
					mkdir(SERIA_PRIV_ROOT.'/SERIA_Logging/processed/'.date('Y-m-d'), 0700, true);
				if(!file_exists(SERIA_PRIV_ROOT.'/SERIA_Logging/invalid/'))
					mkdir(SERIA_PRIV_ROOT.'/SERIA_Logging/invalid/', 0700, true);

				$fpz = gzopen(SERIA_PRIV_ROOT.'/SERIA_Logging/processed/'.date('Y-m-d').'/'.basename($fileToProcess).'.gz', 'w9');
				$fp_invalid = fopen(SERIA_PRIV_ROOT.'/SERIA_Logging/invalid/'.basename($fileToProcess).'.invalid', 'a+');
				while(!feof($fp)) {
					$lineToRecord = fgets($fp, 4096);
					gzwrite($fpz, $lineToRecord); 

					$lineArray[1] = getAndRemoveFirstQuotePair($lineToRecord); // REQUEST
					$lineArray[2] = getAndRemoveFirstQuotePair($lineToRecord); // REFERER
					$lineArray[3] = getAndRemoveFirstQuotePair($lineToRecord); // USER AGENT
					$lineArray[4] = getAndRemoveFirstQuotePair($lineToRecord);

					$lineInfo = explode(" ", $lineToRecord);
					$firstParam = $lineInfo[0];
					if(!trim($firstParam, "0123456789.")=="") {
						// Something came before the IP, maybe subdomain.domain.com?
						fwrite($fp_invalid, $lineToRecord);
						continue;
					}
					// Retrieve the timestamp of the line in question
					$ts = strtotime(substr($lineInfo[3]." ".$lineInfo[4],1,-1));
					if(!is_int($ts)) {
						fwrite($fp_invalid, $lineToRecord);
						continue;
					}

					// Count the bandwidth on the bandwidthCounter
					$usedBandwidth = intval($lineInfo[7]);
					if(!is_int($usedBandwidth)) {
						fwrite($fp_invalid, $lineToRecord);
						continue;
					}

					$bandwidthCounter->add(array(
						'Ym:'.date('Ym', $ts),
						'Ymd:'.date('Ymd', $ts),
						'YmdH:'.date('YmdH', $ts),
					), $usedBandwidth);

					$countLine = true;
					if(intval($lineInfo[6]) == 206) {
						if($lineArray[4] == "bytes=0-1") { // If code is 206 (segment download), only count first segment as hit
							$countLine = true;
						} else {
							$countLine = false;
						}
					}

					$path = substr($lineArray[1], 4, strpos($lineArray[1], " ", 4)-4);
					if(($p = strpos($path, '?')) !== false) {
						$path = substr($path, 0, $p);
					}
					if($countLine) {
						$hitCounter->add(array(
							'p:'.$path,
							'Ym/p:'.date('Ym', $ts).$path,
							'Ymd/p:'.date('Ymd', $ts).$path,
							'YmdH/p:'.date('YmdH', $ts).$path,
						));
					}
				}
				if(ftell($fp_invalid)>0)
				{
					fclose($fp_invalid);
				}
				else
				{
					fclose($fp_invalid);
					unlink(SERIA_PRIV_ROOT.'/SERIA_Logging/invalid/'.basename($fileToProcess).'.invalid');
				}

				fclose($fp);
				gzclose($fpz);
				unlink($fileToProcess);
			}
			date_default_timezone_set($oldTz);
			return true;
		}
	}

?>
