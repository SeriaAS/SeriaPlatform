<?php
	class SERIA_IncomingFtpServer extends SERIA_ActiveRecord {
		public $tableName = '_incoming_ftp_servers';
		public $usePrefix = true;
		public $primaryKey = 'id';
		
		private $ftpHandler;
		
		public function connect() {
			if ($this->ftpHandler) return true;
			
			// Port defaults to 21
			$port = 21;
			($this->port) && $port = $this->port;
			
			$this->ftpHandler = $ftp = ftp_connect(trim($this->hostname), (int) $port, 10);
			if (is_resource($ftp)) {
				try {
					if (ftp_login($ftp, $this->username, $this->password)) {
						try {
							ftp_pasv($ftp, true);
						} catch (Exception $null) {}
						
						return true;
					} else {
						throw new SERIA_Exception('Login failed');
					}
				} catch (Exception $exception) {
					/*
					 * WARNING: Throwing this exception may expose hostname, username and/or password for FTP server in
					 *          backtrace.
					 */
					
					if (SERIA_DEBUG) {
						throw $exception;
					}
				}
			}
			throw new SERIA_Exception('Unable to connect to FTP Server ' . $this->hostname . ':' . $port);
		}
		
		public function downloadFile($remoteFile, $localFile) {
			$this->connect();
			return ftp_get($this->ftpHandler, $localFile, $this->root . '/' . $remoteFile, FTP_BINARY);
		}
		public function uploadFile($localFile, $remoteFile) {
			$this->connect();
			return ftp_put($this->ftpHandler, $this->root . '/' . $remoteFile, $localFile, FTP_BINARY);
		}
		public function deleteFile($filename) {
			$this->connect();
			
			if($this->fileExists($filename))
				return ftp_delete($this->ftpHandler, $filename);
			else
				throw new SERIA_Exception("File $filename does not exist on FTP-server.");
		}
		public function fileExists($filename)
		{
			$this->connect();
			
			list($files, $directories) = $this->getFileList(dirname($filename));
			foreach ($files as &$file) {
				$file = basename($file);
			}
			foreach ($directories as &$file) {
				$file = basename($file);
			}
			
			if (trim($filename, '/') == trim(str_replace('\\', '/', dirname($filename)), '/')) {
				return is_array($files) && is_array($directories);
			}
			
			return in_array(basename($filename), $files) || in_array(basename($filename), $directories);
		}
		
		public function getFileList($path) {
			$list = $this->getFileListWithFileSize($path);
			
			$files = array();
			foreach ($list[1] as $data) {
				list($file, $filesize) = $data;
				$files[] = $file;
			}
			
			return array($list[0], $files);
		}
		
		public function getFileListWithFileSize($path) {
			$this->connect();
			
			$files = array();
			$directories = array();
			$rawlist = ftp_rawlist($this->ftpHandler, $this->root . '/' . $path);
			if (!is_array($rawlist)) {
				throw new SERIA_Exception('Directory not found');
			}
			
			foreach ($rawlist as $item) {
				$item = preg_split("/[\s]+/", $item, 9);
				$mode = $item[0];
				$size = $item[4];
				$filename = $item[8];
				if ($mode[0] == '-') {
					$files[] = array($filename, $size);
				} elseif ($mode[0] == 'd') {
					$directories[] = $filename;
				}
			}
			
			return array($directories, $files);
		}
	}
?>