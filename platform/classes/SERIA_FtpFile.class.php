<?php
	class SERIA_FtpFile extends SERIA_ActiveRecord {
		public $tableName = '_ftp_files';
		protected $usePrefix = true;
		public $primaryKey = 'id';
		
		public $belongsTo = array('SERIA_FTP:FtpServer' => 'ftp_server_id');
	}
?>