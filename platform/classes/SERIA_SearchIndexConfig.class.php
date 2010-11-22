<?php
	class SERIA_SearchIndexConfig extends SERIA_ActiveRecord {
		public $primaryKey = 'id';
		protected $tableName = '_searchindexconfig';
		protected $usePrefix = true;
		
		protected $hasMany = array(
			'SERIA_SearchIndexConfigColumn:Columns' => 'config_id'
		);
		
		public function getIndexTableName() {
			return SERIA_PREFIX . '_searchindex__' . $this->searchtablename;
		}
		public function getSortTableName() {
			return $this->getIndexTableName() . '_sort';
		}
		public function getWordlistTableName() {
			return $this->getIndexTableName() . '_wordlist';
		}
	}
?>