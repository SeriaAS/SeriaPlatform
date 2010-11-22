<?php
	class SERIA_SiteMenuRelation extends SERIA_ActiveRecord {
		public $tableName = '_sitemenu_relation';
		
		public $belongsTo = array(
			'SERIA_SiteMenu:Parent' => 'parent_id',
			'SERIA_SiteMenu:Child' => 'child_id',
		);
	}
?>