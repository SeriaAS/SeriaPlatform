<?php
	class SERIA_MemcacheServer extends SERIA_ActiveRecord {
		protected $tableName = '_memcaches';
		protected $usePrefix = true;
		public $primaryKey = 'id';
		
		public function temporaryDisable() {
			if ($this->disableduntil <= time()) {
				$this->disableduntil = time() + (5 * 60);
				$this->save();
			}
		}
	}
?>