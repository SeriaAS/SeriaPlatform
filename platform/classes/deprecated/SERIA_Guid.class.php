<?php
	class SERIA_Guid extends SERIA_ActiveRecord {
		public $tableName = '_guids';
		public $usePrefix = true;
		public $primaryKey = 'guid';
		
		public static function getObjectFromGuid($guid) {
			if (is_numeric($guid)) {
				$guidObject = SERIA_Guids::find_first_by_guid($guid);
				$guid = $guidObject->key;
			}
			if (!$guid) {
				throw new SERIA_Exception('Cannot find object from empty GUID');
			}
			
			list($key, $id) = explode(':', $guid);
			if (!$key) {
				throw new SERIA_Exception('Cannot fond object from empty GUID key');
			}
			
			switch ($key) {
				case 'article':
					return SERIA_Article::createObjectFromId($id);
					break;
				case 'category':
					return SERIA_ArticleCategory::createObject($id);
					break;
			}
		}
	}
?>