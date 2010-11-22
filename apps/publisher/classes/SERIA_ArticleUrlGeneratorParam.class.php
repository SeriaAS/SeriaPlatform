<?php
	class SERIA_ArticleUrlGeneratorParam extends SERIA_ActiveRecord {
		public $tableName = '_article_url_params';
		public $usePrefix = true;
		
		public $belongsTo = array(
			'SERIA_ArticleUrlGenerator:UrlGenerator' => 'urlgenerator_id'
		);
	}
?>