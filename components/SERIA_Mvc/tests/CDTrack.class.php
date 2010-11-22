<?php
	class CDTrack extends SERIA_MetaObject
	{
		public static function Meta($instance = NULL)
		{
			return array(
				'table' => 'cd_tracks',
				'primaryKey' => 'trackId',
				'fields' => array(
					'cd' => array('CD required', _t('CD')),
					'name' => array('name required unique(cd)', _t('Name')),
				),
			);
		}
	}
