<?php
	class SERIA_FtpServerLimit extends SERIA_ActiveRecord {
		public $tableName = '_ftp_server_limits';
		public $usePrefix = true;
		public $primaryKey = 'id';
		
		public $belongsTo = array(
			'SERIA_FTP:FtpServer' => 'ftp_server_id'
		);
		
		public function validationRules() {
			$this->addRule('ftp_server_id', 'required', 'Ftp server ID is required');
			$this->addRule('ftp_server_id', 'numeric', 'Ftp server ID must be numeric');
			$this->addRule('maxstorageusage', 'required', _t('Maximum storage usage is required'));
			$this->addRule('maxfilesize', 'required', _t('Maximum file size is required'));
			$this->addRule('maxfilecount', 'required', _t('Maximum file count is required'));
			
			$this->addRule('maxstorageusage', 'numeric', _t('Maximum storage usage must be numeric'));
			$this->addRule('maxfilesize', 'numeric', _t('Maximum file size must be numeric'));
			$this->addRule('maxfilecount', 'numeric', _t('Maximum file count must be numeric'));
		}
	}
?>