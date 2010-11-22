<?php
	class CD extends SFluentObject {
		public static function Fluent($instance=NULL) {
			return array(
				'table' => 'cder',
				'fields' => array(
					'title' => array('name required unique', _t('Title')),
					'gender' => array('gender required', _t('Artist gender')),
					'createdBy' => 'createdBy',
					'createdDate' => 'createdDate',
				),
			);
		}
	}
