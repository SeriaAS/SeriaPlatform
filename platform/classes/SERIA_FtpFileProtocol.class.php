<?php
	class SERIA_FtpFileProtocol extends SERIA_ActiveRecord {
		public $tableName = '_ftp_fileprotocols';
		protected $usePrefix = true;
		public $primaryKey = 'id';
		
		protected $belongsTo = array('SERIA_FTP:FtpServer' => 'ftp_server_id');
		
		public static $availableProtocols = array('http' => 'HTTP',
		                                          'https' => 'HTTPS',
		                                          'rtmp' => 'RTMP/Flash stream');
		
		public function validationRules() {
			$this->addRule('name', 'required', _t('A protocol is required'));
			$this->addRule('name', 'inset', _t('A valid protocol is required'), array_keys(self::$availableProtocols));
			$this->addRule('name', 'unique', _t('This FTP server allready have this protocol defined'), 'ftp_server_id');
		}
		
		public function getUrl($filename) {
			$name = $this->name;
			$name = strtolower($name);
			$name[0] = strtoupper($name[0]);
			$className = 'SERIA_FtpFile' . $name . 'Rewriter';
			if (!class_exists($className)) {
				throw new Exception('Unable to find URL Rewriter module for protocol ' . $name . ' (' . $className . ')');
			}
			$urlRewriter = new $className($this, $filename);
			return $urlRewriter->getUrl();
		}
	}
?>