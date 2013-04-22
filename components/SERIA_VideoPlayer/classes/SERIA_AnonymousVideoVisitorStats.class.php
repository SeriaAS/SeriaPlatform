<?php

/**
 *
 * Anonymous Video visitor stats
 * All entries are only temporary and are pushed into SERIA_Counter
 * To retrieve 
 * @author kongjoakim
 *
 */
class SERIA_AnonymousVideoVisitorStats extends SERIA_MetaObject
{
	public static function Meta($instance=null)
	{
		return array(
			'table' => '{anonymous_video_visitorstats}',
			'displayField' => 'video',
			'fields' => array(
				'video' => array('SERIA_Video required', _t('Video')),
				'uid' => array('text required', _t('Temporary Unique Identifier')),
				'seenMap' => array('text required', _t('SeenMap')),
				'createdDate' => 'createdDate',
				'createdBy' => 'createdBy',
				'modifiedDate' => 'modifiedDate',
				'modifiedBy' => 'modifiedBy',
			)
		);
	}

	public static function maintainTemporaryVideoStats()
	{
		$allStats = SERIA_Meta::all('SERIA_AnonymousVideoVisitorStats');

		$counter = new SERIA_Counter('SeriaWebTVStatistics');

		foreach($allStats as $statLine) {
			if(strtotime($statLine->get("modifiedDate"))<(time()-60)) {
				// Push it into SERIA_Counter
				$seenMap = str_split($statLine->get("seenMap"));
				$sCount = 0;
				foreach($seenMap as $i => $j) if($j>0) $sCount++;

				$percentSeen = ($sCount / sizeof($seenMap))*100;

				if($percentSeen<=20) {
					$percentSeen = "0-20";
				} else if($percentSeen>20 && $percentSeen<=40) {
					$percentSeen = "20-40";
				} else if($percentSeen>40 && $percentSeen<=60) {
					$percentSeen = "40-60";
				} else if($percentSeen>60 && $percentSeen<=60) {
					$percentSeen = "60-80";
				} else if($percentSeen>80) {
					$percentSeen = "80-100";
				}

				$counter->add(array(
					'PS-Ym/i:'.date('Ym', time()).'/'.$statLine->get("video")->get("id").":".$percentSeen,
					'PS-Ymd/i:'.date('Ymd', time()).'/'.$statLine->get("video")->get("id").":".$percentSeen,
					'PS-YmdH/i:'.date('YmdH', time()).'/'.$statLine->get("video")->get("id").":".$percentSeen,
				));

				SERIA_Meta::delete($statLine);
			}
		}
	}
}
