<?php
	class SERIA_SiteMenuUrl extends SERIA_ActiveRecord {
		public $tableName = '_sitemenu_url';
		
		public $belongsTo = array(
			'SERIA_SiteMenu:SiteMenu' => 'sitemenu_id'
		);
	}
?>