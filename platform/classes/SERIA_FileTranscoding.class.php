<?php
	class SERIA_FileTranscoding extends SERIA_ActiveRecord {
		protected $tableName = '_file_transcode_queue';
		public $primaryKey = 'id';
		protected $usePrefix = true;
	}
?>