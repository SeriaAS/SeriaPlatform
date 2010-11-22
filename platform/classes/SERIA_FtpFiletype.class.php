<?php
	class SERIA_FtpFiletype extends SERIA_ActiveRecord {
		public $tableName = '_ftp_server_filetypes';
		protected $usePrefix = true;
		public $primaryKey = 'id';
		
		public $belongsTo = array(
			'SERIA_FTP:FtpServer' => 'ftp_server_id'
		);
		
		public function validationRules() {
			$this->addRule('pattern', 'required', _t('A filename pattern is required'));
			$this->addRule('pattern', 'unique', _t('This FTP server allready have this file pattern defined'), 'ftp_server_id');
			$this->addRule('type', 'required', _t('You have to choose between include or exclude'));
			$this->addRule('type', 'inset', _t('Invalid type: Must contain exclude or include'), array('include', 'exclude'));
		}
	}
?>