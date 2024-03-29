<?php

/**
 *
 * Video visitor stats
 * @author kongjoakim
 *
 */
class SERIA_VideoVisitorStats extends SERIA_MetaObject implements SERIA_IApiAccess
{
	public static function Meta($instance=null)
	{
		return array(
			'table' => '{video_visitorstats}',
			'displayField' => 'video',
			'fields' => array(
				'video' => array('SERIA_Video required', _t('Video')),
				'objectKey' => array('integer', _t("ObjectKey")),
				'euid' => array('integer required', _t('External Unique Identifier')),
				'seenMap' => array('text required', _t('SeenMap')),
				'createdDate' => 'createdDate',
				'createdBy' => 'createdBy',
				'modifiedDate' => 'modifiedDate',
				'modifiedBy' => 'modifiedBy',
			)
		);
	}

	protected static $queryTime;

	public static function apiQuery($params)
	{
		if(date('Y-m-d')>'2012-11-15')
			self::$queryTime = microtime(true) + 14;
		else
			self::$queryTime = microtime(true) + 14;

		if(isset($params['objectKey']) && isset($params['videoId']))
			throw new SERIA_Exception('Can\'t query on both objectKey and videoId in same request', SERIA_Exception::INCORRECT_USAGE);
		if(isset($params['objectKey']) && strpos($params['objectKey'], ",")!==false) {
			$allStats = array();
			$keys = array_unique(explode(",", $params['objectKey']));
			foreach($keys as $objectKey) {
				$qArray = $params;
				$qArray['objectKey'] = $objectKey;
				$allStats[] = SERIA_VideoVisitorStats::getStatistics($qArray);
				if(microtime(true)>self::$queryTime) throw new SERIA_Exception("Query too slow, please optimize your query.");
			}
			return $allStats;
		} else if(isset($params['videoId']) && strpos($params['videoId'], ",")!==false) {
			$allStats = array();
			foreach(array_unique(explode(",", $params['videoId'])) as $videoId) {
				$qArray = $params;
				$qArray['videoId'] = $videoId;
				$allStats[] = SERIA_VideoVisitorStats::getStatistics($qArray);
				if(microtime(true)>self::$queryTime) throw new SERIA_Exception("Query too slow, please optimize your query.");
			}
			return $allStats;
		} else {
			return SERIA_VideoVisitorStats::getStatistics($params);
		}
	}

	public static function getStatistics($params) {
		if(isset($params['help'])) return array(
			'info' => 'Fetch user-based videostatistics from Seria VideoPlayer. With no parameters, you will receive an error. Either a videoId, its objectKey or an external unique identifier has to be set.',
			'params' => array(
				'videoId' => 'Fetch information given a single video',
				'objectKey' => 'Fetch information given by object key',
				'euid' => 'External User Identificator, is the unique key set by the embedder from which the staistics is assosciated with',
				'start' => 'All videos starting at offset [start]',
				'length' => 'Return at most [length] videos. Maximum value is 1000',
				'order' => 'One of "publishedDate", "publishedDateDesc", "createdDate" or "createdDateDesc"',
			),
		);


		if(!isset($params['euid']) && !isset($params['videoId']) && !isset($params['objectKey']))
			throw new SERIA_Exception("EUID Required");

		if(isset($params['videoId'])) {
			$videostats = SERIA_Meta::all('SERIA_VideoVisitorStats');
			$videostats->where('video=:vid', array('vid' => $params['videoId']));
		} else if(isset($params['objectKey'])) {
			$videostats = SERIA_Meta::all('SERIA_VideoVisitorStats');
			try {
				$obj = SERIA_NamedObjects::getInstanceByPublicId($params['objectKey'], 'SERIA_Video');
			} catch (SERIA_Exception $e) {
				$e = new SERIA_Exception("Object key ".$params['objectKey']." not found", 404);
				$e->extra = array(
					'objectKey' => $params['objectKey'],
				);
				$result = array();
				$result[] = array('status' => 'error', 'code' => 'Object not found', 'data' => $params['objectKey']);
				return $result;
				throw $e;
			}
			$videostats->where('video=:vid', array('vid' => $obj->get("id")));
		} else {
			$videostats = SERIA_Meta::all('SERIA_VideoVisitorStats');
		}

		if(!isset($params['start'])) $params['start'] = 0;
		if(!isset($params['length'])) $params['length'] = 10;
		if($params['length']>1000) $params['length'] = 1000;

		$videostats->limit($params['start'], $params['length']);

		if(!(strpos($params['euid'], ",") === false)) {
			$result = array();

			$euidArray = explode(",", $params['euid']);
			foreach($videostats as $videostat) {
				if(in_array($videostat->get("euid"), $euidArray)) {
					$seenMap = $videostat->get("seenMap");
					$aggSeenMap = $seenMap;

					$b = "";
					if(strpos($seenMap, ",") !== false) {
						$p = explode(",", $seenMap); // 
						foreach($p as $i => $t) {
							if($_SERVER["REMOTE_ADDR"] == "178.255.151.46") {
//								var_dump($p);die();
							}
							if($t>0) {
								$b.="1";
							} else {
								$b.="0";
							}
						}
						$seenMap = $b;
					}
					$seenMap[0] = 1; // Hack, all seenmaps begin with 0
					$strc = substr_count($seenMap, 1);
					$percFloat = ($strc/strlen($seenMap));
					$percentSeen = round($percFloat*100);
					if($percentSeen>100)
						$percentSeen = 100;

					$objectKey = SERIA_NamedObjects::getPublicId($videostat->get("video"));

					$counter = new SERIA_Counter('SeriaWebTVStatistics');
					$viewCount = array_shift($counter->get(array('ObjectKey:'.$objectKey.',EUID:'.$videostat->get("euid"))));

					$result[] = array(
						'videoId' => $videostat->get("video")->get("id"),
						'objectKey' => $objectKey,
						'title' => $videostat->get("video")->get("title"),
						'euid' => $videostat->get("euid"),
						'seenMap' => $b,
						'aggSeenMap' => $aggSeenMap,
						'timesLoaded' => $viewCount,
						'percentSeen' => $percentSeen,
						'proportionSeen' => round($percFloat, 4),
						'createdDate' => $videostat->get("createdDate"),
						'modifiedDate' => $videostat->get("modifiedDate")
					);
				}
			}
		} else {
			if(isset($params['euid']))
				$videostats->where('euid=:e', array('e' => $params['euid']));

			$result = array();
			foreach($videostats as $videostat) {
				$seenMap = $videostat->get("seenMap");
				$aggSeenMap = $seenMap;

				$b = "";
				if(strpos($seenMap, ",") !== false) {
					$p = explode(",", $seenMap); // 
					foreach($p as $i => $t) {
						if($_SERVER["REMOTE_ADDR"] == "178.255.151.46") {
//							var_dump($p);die();
						}
						if($t>0) {
							$b.="1";
						} else {
							$b.="0";
						}
					}
					$seenMap = $b;
				}

				$seenMap[0] = 1; // Hack, all seenmaps begin with 0
				$strc = substr_count($seenMap, 1);
				$percFloat = ($strc/strlen($seenMap));
				$percentSeen = round($percFloat*100);
				if($percentSeen>100)
					$percentSeen = 100;


				$objectKey = SERIA_NamedObjects::getPublicId($videostat->get("video"));

				$counter = new SERIA_Counter('SeriaWebTVStatistics');
				$viewCount = array_shift($counter->get(array('ObjectKey:'.$objectKey.',EUID:'.$videostat->get("euid"))));

				$result[] = array(
					'videoId' => $videostat->get("video")->get("id"),
					'objectKey' => $objectKey,
					'title' => $videostat->get("video")->get("title"),
					'euid' => $videostat->get("euid"),
					'seenMap' => $b,
					'aggSeenMap' => $aggSeenMap,
					'timesLoaded' => $viewCount,
					'percentSeen' => $percentSeen,
					'proportionSeen' => round($percFloat, 4),
					'createdDate' => $videostat->get("createdDate"),
					'modifiedDate' => $videostat->get("modifiedDate")
				);
			}
		}

		return $result;
	}
}
