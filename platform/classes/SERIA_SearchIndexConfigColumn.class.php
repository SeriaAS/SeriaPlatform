<?php
	class SERIA_SearchIndexConfigColumn extends SERIA_ActiveRecord {
		protected $tableName = '_searchindexconfig_column';
		public $primaryKey = 'id';
		protected $usePrefix = true;
		
		protected $belongsTo = array(
			'SERIA_SearchIndexConfig:Config' => 'config_id'
		);
	}
?>