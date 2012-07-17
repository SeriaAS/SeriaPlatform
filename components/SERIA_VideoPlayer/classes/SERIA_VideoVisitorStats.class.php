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
				'euid' => array('integer required', _t('External Unique Identifier')),
				'seenMap' => array('text required', _t('SeenMap')),
				'createdDate' => 'createdDate',
				'createdBy' => 'createdBy',
				'modifiedDate' => 'modifiedDate',
				'modifiedBy' => 'modifiedBy',
			)
		);
	}

	public static function apiQuery($params)
	{
		if(isset($params['help'])) return array(
			'info' => 'Fetch user-based videostatistics from Seria VideoPlayer. With no parameters, you will receive an error. Either the objectKey or an external unique identifier has to be set.',
			'params' => array(
				'videoid' => 'Fetch information given a single video',
				'objectKey' => 'Fetch information given by object key',
				'euid' => 'External User Identificator, is the unique key set by the embedder from which the staistics is assosciated with',
				'cat' => 'All videos in a given category id (/api/SERIA_VideoCategory)',
				'start' => 'All videos starting at offset [start]',
				'length' => 'Return at most [length] videos. Maximum value is 1000',
				'order' => 'One of "publishedDate", "publishedDateDesc", "createdDate" or "createdDateDesc"',
			),
		);

		$videostats = SERIA_Meta::all('SERIA_VideoVisitorStats');

		if(!isset($params['start'])) $params['start'] = 0;
		if(!isset($params['length'])) $params['length'] = 10;
		if($params['length']>1000) $params['length'] = 1000;

		$videostats->limit($params['start'], $params['length']);

		if(isset($params['videoid'])) {
			$videostats->where('video=:vid', array('vid' => $params['videoid']));
		} else if(isset($params['objectKey'])) {
			$obj = SERIA_NamedObjects::getInstanceByPublicId($params['objectKey'], 'SERIA_Video');
			$videostats->where('video=:vid', array('vid' => $obj->get("id")));
		}
		if(!isset($params['euid']) && !isset($params['videoid']))
			throw new SERIA_Exception("EUID Required");

		if(isset($params['euid']))
			$videostats->where('euid=:e', array('e' => $params['euid']));

		$result = array();
		foreach($videostats as $videostat) {
			$strc = substr_count($videostat->get("seenMap"), 1);
			$percentSeen = intval(($strc/strlen($videostat->get("seenMap")))*100);
			if($percentSeen>100)
				$percentSeen = 100;
			$result[] = array(
				'videoid' => $videostat->get("video")->get("id"),
				'objectKey' => SERIA_NamedObjects::getPublicId($videostat->get("video")),
				'title' => $videostat->get("video")->get("title"),
				'euid' => $videostat->get("euid"),
				'seenMap' => $videostat->get("seenMap"),
				'percentSeen' => $percentSeen,
			);
		}
		return $result;
	}
}
