<?php
	class SERIA_CometMessage extends SERIA_ActiveRecord {
		public $tableName = '_comet_messages';
		public $usePrefix = true;
		
		public $belongsTo = array(
			'SERIA_CometChannel:Channel' => 'channel_id'
		);
		
		protected function beforeSave() {
			$this->time = time();
		}
	}
?>