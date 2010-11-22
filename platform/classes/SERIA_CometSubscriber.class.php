<?php
	class SERIA_CometSubscriber extends SERIA_ActiveRecord {
		public $tableName = '_comet_subscribers';
		public $usePrefix = true;
		
		public $belongsTo = array(
			'SERIA_CometChannel:Channel' => 'channel_id'
		);
		
		public function getUnsentMessages() {
			$criteriasSql = 'time <= ' . $this->lastupdate;
			$messages = SERIA_CometMessages::find_all_by_channel_id($this->channel_id, array('criteriasSql' => $criteriasSql));
			return $messages->toArray();
		}
		
		public function updateTime() {
			$this->lastupdate = time();
			$this->save();
		}
		
		public function validationRules() {
			$this->addRule('key', 'regex', 'Key error', '/^[a-z0-9_]*$/');
		}
	}
?>