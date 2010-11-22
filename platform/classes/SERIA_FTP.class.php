<?php
	class SERIA_FtpFileListItem {
		public $filename;
		public $size;
		
		public function __tostring() {
			return $this->filename;
		}
	}

	class SERIA_FTP extends SERIA_ActiveRecord {
		public $tableName = '_ftp_servers';
		private $fileListCache = array();
		
		public $hasMany = array(
			'SERIA_FtpFile:FtpFiles' => 'ftp_server_id',
			'SERIA_FtpFileProtocol:FileProtocols' => 'ftp_server_id',
			'SERIA_FtpFiletype:Filetypes' => 'ftp_server_id'
		);
		
		public $hasOne = array(
			'SERIA_FtpServerLimit:ServerLimit' => 'ftp_server_id'
		);
		
		private $ftpHandler;
		
		public function validationRules() {
			$this->addRule('host', 'required', _t('A hostname is required'));
			$this->addRule('port', 'required', _t('A port number is required'));
			$this->addRule('username', 'required', _t('A username is required'));
			$this->addRule('request_host', 'required', _t('A request hostname is required'));
			$this->addRule('host', 'hostname', _t('A valid hostname is required'));
			$this->addRule('port', 'numeric', _t('A valid port number is required'));
			$this->addRule('port', 'valueRange', _t('A valid port number is required'), array(1, 65535));
			$this->addRule('request_host', 'hostname', _t('A valid request hostname is required'));
			$this->addRule('delay', 'required', _t('A delay is required'));
			$this->addRule('delay', 'numeric', _t('Delay must be numeric'));
		}
		
		// Following methods is deprecated. Use native Active Record calls.
		public static function getAll() {
			return SERIA_FTPs::find_all();
		}
		
		public static function getOne($where = null) {
			return array_shift(self::getMany($where));
		}
		
		public static function getMany($where = null) {
			$db = SERIA_Base::db();
			
			if ($where === null) {
				$where = array();
			} elseif (is_numeric($where)) {
				$where = array('id' => $where);
			} elseif (!is_array($where)) {
				throw new SERIA_Exception('Error in where argument: Unknown type');
			}
			
			return SERIA_FTPs::find_all(array('criterias' => $where));
		}
		// End deprecated methods
		
		public function checkFilenameSupport($filename) {
			static $fileMatchCache = null;
			if (!$fileMatchCache) {
				$fileMatchCache = array();
			}
			
			$supportedFiletypes = $this->Filetypes;
			if (!is_array($supportedFiletypes)) {
				return false;
			}
			
			$match = false;
			
			foreach ($supportedFiletypes as $fileType) {
				if (($fromCache = $fileMatchCache[$fileType->pattern]) !== null) {
					$matchedPattern = $fromCache;
				} else {
					$matchedPattern = fnmatch($fileType->pattern, $filename);
				}
				
				if ($matchedPattern) {
					if ($fileType->type == 'include') {
						// Continue search for exclude if found in an include list
						$match = true;
					} else {
						// If found in exclude list, this file is not going to be uploaded
						return false;
					}
				}
			}
			
			return $match;
		}
		
		public function validateFileForUpload($filePath) {
			try {
				$filesize = filesize($filePath);
			} catch (Exception $null) {
				return false;
			}
			
			try {
				$limits = $this->ServerLimit;
				if (!$limits || !$limits->id) {
					$limits = false;
				}
			} catch (Exception $null) {
				$limits = false;
			}
			
			try {
				if (!$limits) {
					$limits = new SERIA_FtpServerLimit();
					$limits->ftp_server_id = $this->id;
					$limits->save();
				}
			} catch (Exception $exception) {
				SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, _t('Unable to read FTP server limits from server %SERVER%. Will not upload file to this server.', array('SERVER' => $this->host . '/' . $this->username)));
			}
			
			if ($limits->maxstorageusage != 0) {
				if (round($this->storageusage + ($filesize / 1024)) > ($limits->maxstorageusage * 1024)) {
					SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, _t('File upload to server %SERVER% failed: Server storage limit exceeded', array('SERVER' => $this->host . '/' . $this->username)));
					return false;
				}
			}
			if ($limits->maxfilesize != 0) {
				if ($limits->maxfilesize < round($filesize / 1024)) {
					return false;
				}
			}
			
			if ($limits->maxfilecount != 0) {
				if ($limits->maxfilecount < $this->filecount + 1) {
					SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, _t('File upload to server %SERVER% failed: File count limit exceeded', array('SERVER' => $this->host . '/' . $this->username)));
					return false;
				}
			}
			
			return true;
		}
		
		public function recalculateStorageUsage() {
			$limit = 500;
			$offset = 0;
			$usage = 0;
			$usagebytes = 0;
			$count = 0;
			
			$query = 'SELECT COUNT(files.id) as filecount, SUM(files.filesize / 1024) FROM ' . SERIA_PREFIX . '_files as files, ' . SERIA_PREFIX . '_ftp_files as ftpfiles WHERE ftpfiles.file_id = files.id AND ftpfiles.ftp_server_id = ' . $this->id;
			$queryResult = SERIA_Base::db()->query($query);
			if (sizeof($rows = $queryResult->fetchAll(PDO::FETCH_NUM))) {
				list($row) = $rows;
				list($fileCount, $storageUsage) = $row;
			} else {
				$fileCount = 0;
				$storageUsage = 0;
			}
			
			$this->storageusage = floor($storageUsage);
			$this->filecount = floor($fileCount);
			$this->storageupdate = time();
			$this->save();
		}
 		
		public function connect($force = false) {
			if (!$force && $this->ftpHandler) return true;
			
			static $connectionCache = false;
			if ($connectionCache === false) {
				$connectionCache = array();
			}
			
			$key = $this->host . '_' . $this->port . '_' . $this->username . '_' . md5($this->file_root);
			if ($connectionCache[$key] && !$force) {
				$this->ftpHandler =& $connectionCache[$key];
				if ($this->ftpHandler) {
					return true;
				}
			}
			
			// Port defaults to 21
			$port = 21;
			($this->port) && $port = $this->port;
			
			$this->ftpHandler = ftp_connect($this->host, $port, 10);
			$ftp =& $this->ftpHandler;
			$connectionCache[$key] =& $ftp;
			if ($ftp) {
				try {
					if (ftp_login($ftp, $this->username, $this->password)) {
						ftp_pasv($ftp, (bool) $this->pasv);
						
						$this->flushFileListCache();
						return true;
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
			throw new Exception('Unable to connect to FTP Server: ' . $this->host);
			return false;
		}
		
		public function downloadFile($remoteFile, $localFile) {
			$this->connect();
			$this->flushFileListCache();
			try {
				return ftp_get($this->ftpHandler, $localFile, $remoteFile, FTP_BINARY);
			} catch (Exception $null) {
				$this->connect(true);
				return ftp_get($this->ftpHandler, $localFile, $remoteFile, FTP_BINARY);
			}
		}
		public function uploadFile($localFile, $remoteFile) {
			$this->connect();
			$this->flushFileListCache();
			$result = false;
			try {
				$result = ftp_put($this->ftpHandler, $remoteFile, $localFile, FTP_BINARY);
			} catch (Exception $null) {
				$this->connect(true);
				$result = ftp_put($this->ftpHandler, $remoteFile, $localFile, FTP_BINARY);
			}
			
			return $result;
		}
		
		public function uploadFileToFileRoot($localFile, $filename) {
			if ($this->uploadFile($localFile, '/' . $this->file_root . '/' . $filename)) {
				$this->addFileToCache('/' . $this->file_root, $filename);
				return true;
			}
		}
		
		private function _deleteFile($filename) {
			$filename = str_replace(array("\n", "\r", "\t"), array('','',''), $filename);
			$response = ftp_raw($this->ftpHandler, 'DELE ' . $filename);
			list($code) = explode(' ', $response[0], 2);
			if ($code == 250) {
				return true;
			}
			throw new SERIA_Exception('Delete failed: ' . $response[0]);
		}
		
		public function deleteFile($filename) {
			$this->connect();
			
			try {
				return $this->_deleteFile($filename);
			} catch (Exception $null) {
				$this->connect(true);
				
				return $this->_deleteFile($filename);
			}
		}
		
		public function deleteFileFromFileRoot($filename) {
			if ($this->deleteFile('/' . $this->file_root . '/' . $filename)) {
				$this->removeFileFromCache('/' . $this->file_root, $filename);
				return true;
			}
		}
		
		public function fileExists($filename)
		{
			$this->connect();
			$files = $this->getFileList(dirname($filename));
			foreach ($files as &$file) {
				$file = basename($file);
			}
			return in_array(basename($filename), $files);
		}
		
		public function getFileList($path, $retryOnError = true) {
			$objects = $this->getFileListAsObjects($path, $retryOnError);
			$files = array();
			foreach ($objects as $id => $object) {
				$files[$id] = $object->filename;
			}
			
			return $files;
		}
		
		public function getFileListAsObjects($path, $retryOnError = true) {
			$this->connect();
			
			try {
				$files = array();
				$rawlist = ftp_rawlist($this->ftpHandler, $path);
				if (!is_array($rawlist)) {
					return array();
				}
				
				foreach ($rawlist as $item) {
					$item = preg_split("/[\s]+/", $item, 9);
					$mode = $item[0];
					$filename = $item[8];
					$size = $item[4];
					if ($mode[0] == '-') {
						$item = new SERIA_FtpFileListItem();
						$item->size = $size;
						$item->filename = $filename;
						$files[] = $item;
					}
				}
				
				return $files;
			} catch (Exception $exception) {
				if ($retryOnError) {
					return $this->getFileList($path, false);
				} else {
					throw new $exception;
				}
			}
		}
		
		public function getFileRootFileList() {
			$objects = $this->getFileRootFileListAsObjects();
			$files = array();
			foreach ($objects as $id => $object) {
				$files[$id] = $object->filename;
			}
			return $files;
		}
		
		public function getFileRootFileListAsObjects() {
			$path = '/' . $this->file_root;
			$files = $this->getFileListFromCache($path);
			if (!$files) {
				$files = $this->getFileListAsObjects($path);
				$this->saveFileListToCache($path, $files);
			}
			return $files;
		}
		
		public function flushFileListCache() {
			$this->fileListCache = array();
		}
		
		private $cache;
		private function cache() {
			if (!$this->cache) {
				$this->cache = new SERIA_Cache('filelist2_' . $this->id);
			}
			
			return $this->cache;
		}
		
		private $fileList = array();
		private function &getFileListFromCache($path) {
			if ($this->fileList[$path]) {
				return $this->fileList[$path];
			}
			
			if ($time = $this->cache()->get('&&__TIME__' . md5($path))) {
				if (is_numeric($time)) {
					if ($time > (time() - (3600 * 2))) {
						if ($fromCache = $this->cache()->get(md5($path))) {
							$this->fileList[$path] =& $fromCache;
							return $fromCache;
						}
					}
				}
			}
			
			$this->cache()->set(md5($path), null, 1);
			
			$null = null;
			return $null;
		}
		
		private function saveFileListToCache($path, $list) {
			$this->fileList[$path] = $list;
			$this->cache()->set(md5($path), $list, 3600 * 2);
			$this->cache()->set('&&TIME' . md5($path), time(), 3600 * 2);
		}
		
		private function removeFileFromCache($path, $filename) {
			$list =& $this->getFileListFromCache($path);
			if (is_array($list)) {
				foreach ($list as $key => $value) {
					if ($value == $filename) {
						unset($list[$key]);
					}
				}
			}
		}
		private function addFileToCache($path, $filename) {
			$list =& $this->getFileListFromCache($path);
			$list[] = $filename;
		}
		
		private function saveFileListCache() {
			foreach ($this->fileList as $path => $list) {
				SERIA_Base::debug('Saving file list for path ' . $path);
				$this->cache()->set(md5($path), $list, 3600 * 2);
			}
		}
		
		public function __destruct() {
			$this->saveFileListCache();
		}
		
		public function disconnect() {
			return ftp_close($this->ftpHandler);
		}
	}
?>
