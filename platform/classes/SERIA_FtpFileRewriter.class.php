<?php
	class SERIA_FtpFileRewriter {
		protected $protocolObject;
		protected $filename;
		
		protected $urlTemplate = 'unknown://{HOST}/{PATH}/{FILENAME}';
		
		public function __construct($ftpFileProtocolObject, $filename) {
			$this->protocolObject = $ftpFileProtocolObject;
			$this->filename = $filename;
		}
		
		public function getUrl() {
			$url = $this->urlTemplate;
			$ftpServer = $this->protocolObject->FtpServer;
			$url = str_replace(array('{FILENAME}', '{HOST}', '{PATH}'), array($this->filename, $ftpServer->request_host, $ftpServer->request_path), $url);
			
			return $url;
		}
	}
?>