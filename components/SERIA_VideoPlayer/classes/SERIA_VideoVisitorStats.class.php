<?php

/**
 *
 * Video visitor stats
 * @author kongjoakim
 *
 */
class SERIA_VideoVisitorStats extends SERIA_MetaObject
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
}
