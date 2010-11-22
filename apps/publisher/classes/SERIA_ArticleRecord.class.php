<?php
	class SERIA_ArticleRecord extends SERIA_ActiveRecord {
		public $tableName = '_articles';
		public $primaryKey = 'id';
		public $useGuid = true;
		public $guidKey = 'article';
	}
?>