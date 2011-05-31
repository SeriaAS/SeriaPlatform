<?php

	/**
	* nordea.seriatv.com 195.159.161.226 - - [18/Apr/2011:12:09:35 +0200] "GET /sites/n/nordea.seriatv.com/files/seriawebtv/239/alt/thumb.jpg HTTP/1.1" 200 60692 "-" "MobileSafari/6533.18.5 CFNetwork/485.12.7 Darwin/10.4.0" 267 60952 677
	* "%site %h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-agent}i\" %I %O"
	*
	*/

	class SERIA_Logging {

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
			//$regExpPattern = '/^(\D*)?\s?(\d*.\d*.\d*.\d*.)?\s?(\S*)?\s?(\S*)?\s?(\S*\s\S*)?\s?([^\s"]+|"[^"]*")?\s?(\d*)?\s?(\d*)?\s?(\S*)?\s?([^\s"]+|"[^"]*")?\s?(\d*)?\s?(\d*)?\s?(\d*)?$/';
			$filesInQueue = glob(SERIA_PRIV_ROOT.'/SERIA_Logging/incoming/stream.*'); /**/
			$counter = new SERIA_Counter('bandwidthusage');
			foreach($filesInQueue as $fileToProcess) {
				/**
				* Read a logfile, structure the line and put it in an array. if the read is successful - use seria_counter
				*/
				if(strpos($fileToProcess, '.tmp') !== false) continue;

				$statistics = array();
				$fp = fopen($fileToProcess, 'r');
				if(!file_exists(SERIA_PRIV_ROOT.'/SERIA_Logging/processed/'.date('Y-m-d')))
					mkdir(SERIA_PRIV_ROOT.'/SERIA_Logging/processed/'.date('Y-m-d'), 0700, true);

				$fpz = gzopen(SERIA_PRIV_ROOT.'/SERIA_Logging/processed/'.date('Y-m-d').'/'.basename($fileToProcess).'.gz', 'w9');
				while(!feof($fp)) {
					$lineToRecord = fgets($fp, 4096);
					gzwrite($fpz, $lineToRecord);
	// HACK HACK HACK

	$firstpart = substr($lineToRecord, strpos($lineToRecord, '/files/seriawebtv/')+18);

	if(strpos($lineToRecord, 'LNX 9,0,124,2')!==false) {
		$objectId = substr($firstpart, 0, $nextPos = strpos($firstpart, '-'));
		$nextPart = substr($firstpart, $nextPos+1);
		$quality = substr($firstpart, $nextPos+1, strpos($nextPart, '-'));
	} else {
		$objectId = substr($firstpart, 0, strpos($firstpart, '/'));
		$quality = substr($firstpart, strpos($firstpart, 'alt/')+4, strpos($firstpart, '.mp4')-8);
	}

					$lineArray[1] = getAndRemoveFirstQuotePair($lineToRecord);
					$lineArray[2] = getAndRemoveFirstQuotePair($lineToRecord);
					$lineArray[3] = getAndRemoveFirstQuotePair($lineToRecord);
					$lineArray[4] = getAndRemoveFirstQuotePair($lineToRecord);
					$lineArray[5] = getAndRemoveFirstQuotePair($lineToRecord);

					$lineInfo = explode(" ", $lineToRecord);
					$firstParam = $lineInfo[0];
					if(trim($firstParam, "0123456789.")=="") {
						// First param is an IP adress
						$offset = 0;
					} else {
						// Something came before the IP, maybe subdomain.domain.com
						$offset = 1;
					}

					//preg_match_all($regExpPattern, $lineToRecord, $logInfo);
					$ts = strtotime(substr($lineInfo[3+$offset]." ".$lineInfo[4+$offset],1,-1));
					if(isset($lineInfo[10+$offset]) && isset($lineInfo[11+$offset])) {
						// APACHE
						$usedBandwidth = intval($lineInfo[10+$offset]) + intval($lineInfo[11+$offset]);
					} else {
						// WOWZA LOGFILE
						$usedBandwidth = intval($lineInfo[6+$offset]);
					}
					if(!$ts) continue;
					$counter->add(array(
						'b-Y:'.date('Y', $ts),
						'b-Ym:'.date('Y-m', $ts),
						'b-Ymd:'.date('Y-m-d', $ts),
						'b-YmdH:'.date('Y-m-d H', $ts),
					), $usedBandwidth);
/*
					$counter->add(array(
						'c-Y:'.date('Y', $ts),
						'c-Ym:'.date('Y-m', $ts),
						'c-Ymd:'.date('Y-m-d', $ts),
						'c-YmdH:'.date('Y-m-d H', $ts),
					), intval($logInfo[12+$offset]));
					$counter->add(array(
						'h-Y:'.date('Y', $ts),
						'h-Ym:'.date('Y-m', $ts),
						'h-Ymd:'.date('Y-m-d', $ts),
						'h-YmdH:'.date('Y-m-d H', $ts),
					));
*/
					if(!$quality || !$objectId) continue;
					$videoCounter = new SERIA_Counter('videostatistics');
					$videoCounter->add(array(
						'h-'.$objectId.'-'.$quality.'-Y:'.date('Y', $ts),
						'h-'.$objectId.'-'.$quality.'-Ym:'.date('Y-m', $ts),
						'h-'.$objectId.'-'.$quality.'-Ymd:'.date('Y-m-d', $ts),
						'h-'.$objectId.'-'.$quality.'-YmdH:'.date('Y-m-d H', $ts),
					), 1);
/*
					$statline = array(
						'site' => $logInfo[1][0],
						'remote_ip' => $logInfo[2][0],
						'remote_logname' => $logInfo[3][0],
						'remote_user' => $logInfo[4][0],
						'time' => strtotime(substr($logInfo[5][0],1,-1)),
						'req_line' => $logInfo[6][0],
						'status' => $logInfo[7][0],
						'resp_bytes' => $logInfo[8][0],
						'referrer' => $logInfo[9][0],
						'useragent' => $logInfo[10][0],
						'bytes_received' => $logInfo[11][0],
						'bytes_sent' => $logInfo[12][0],
						'cpu_time' => $logInfo[13][0]
					);
					$statistics[] = $statline;
*/
				}
				fclose($fp);
				gzclose($fpz);
				unlink($fileToProcess);
				// Move the file to .processed
			}
			date_default_timezone_set($oldTz);
			return true;
		}
	}

?>
