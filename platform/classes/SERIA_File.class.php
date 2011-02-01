<?php
	/**
	*	Interface for working with files used troughout the system.
	*
	*	All files must be referenced trough this object
	*/
	class SERIA_File implements SERIA_IMetaField
	{
		private $id, $url, $filename, $row, $filesize, $contentType, $updatedAt, $thumbnails, $createdAt, $fileArticleId;

		private $protocolHandlers = array();
		
		protected $meta = null;
		
		private $thumbnailCache = array();
		protected static $_imageThumbnailCache;
		
		protected $ftpServerCache = null;
		
		private function saveToCache() {
			$cache = new SERIA_Cache('fileobject');
			$cache->set($this->get('id'), $this, 1800);
		}
		
		private function clearFromCache() {
			$cache = new SERIA_Cache('fileobject');
			$cache->set($this->get('id'), 0, 1);
			$cache = new SERIA_Cache('filethumbrelation');
			$cache->set($this->get('id'), 0, 1);
			$this->ftpServerCache->set($this->get('id'), 0, 1);
		}
		
		private static function getFromCache($id) {
			$cache = new SERIA_Cache('fileobject');
			return $cache->get($id);
		}
		
		public static function getObjects($ids) {
			return self::createObjects($ids);
		} 
		
		public static function getAll() {
			$db = SERIA_Base::db();
			
			$query = 'SELECT id FROM ' . SERIA_PREFIX . '_files';
			$ids = array();
			$result = $db->query($query)->fetchAll(PDO::FETCH_NUM);
			foreach ($result as $file) {
				list($id) = $file;
				$ids[] = $id;
			}
			
			return self::createObjects($ids);
		}
		public static function getByUrl($url)
		{
			$db = SERIA_Base::db();
			
			$pinfo = pathinfo($url);
			$basename = $pinfo['basename'];
			$sql = 'SELECT id FROM '.SERIA_PREFIX.'_files WHERE filename = '.$db->quote($basename);
			$rows = $db->query($sql)->fetchAll();
			foreach ($rows as $row) {
				$id = $row['id'];
				$file = new SERIA_File($id);
				if ($file->get('url') == $url)
					return $file;
			}
			return null;
		}
		
		public function getLastUploadedIds($limit = 20) {
			$query = 'SELECT id FROM ' . SERIA_PREFIX . '_files WHERE parent_file = 0 ORDER BY created_date DESC LIMIT ' . (int) $limit;
			$rows = SERIA_Base::db()->query($query)->fetchAll(PDO::FETCH_NUM);
			$ids = array();
			foreach ($rows as $row) {
				list($id) = $row;
				$ids[] = $id;
			}
			
			return $ids;
		}
		
		public function getLastUploaded($limit = 20) {
			$ids = self::getLastUploadedIds($limit);
			return self::createObjects($ids);
		}
		
		static function createObject($id)
		{
			$id = intval($id);
			if ($id <= 0) throw new SERIA_Exception("Unknown ID");
			
			if (is_object($file = self::getFromCache($id))) {
				return $file;
			}
			
			$object = new SERIA_File($id);

			$object->saveToCache();
			
			return $object;
		}
		
		public static function createObjects($ids, $disableMetaRead = false, $disableFtpRead = false) {
			$files = array();
			$fileById = array();
			foreach ($ids as &$id) {
				$id = (int) $id;
			}
			unset($id);
			
			if (!sizeof($ids)) {
				return array();
			} else {
				// Check for objects in cache
				foreach ($ids as $key => $id) {
					if (is_object($object = self::getFromCache($id))) {
						$files[] = $object;
						unset($ids[$key]);
					}
				}
			}
			
			if (sizeof($ids)) {
				$ids = implode(',', $ids);
				
				$query = 'SELECT * FROM ' . SERIA_PREFIX . '_files WHERE id IN (' . $ids . ')';
				$rows = SERIA_Base::db()->query($query)->fetchAll(PDO::FETCH_ASSOC);
				
				foreach ($rows as $file) {
					$files[] = $object = new SERIA_File($file);
					if (!$disableMetaRead && !$disableFtpRead) {
						$object->saveToCache();
					}
					$fileById[$object->get('id')] = $object;
				}
				
				$ids = array();
				foreach ($files as $file) {
					$ids[] = $file->get('id');
				}
				
				if (!$disableMetaRead) {
					$filemeta = SERIA_FileMetas::find_all_by_file_id($ids)->toArray();
				}
				if (!$disableFtpRead) {
					$ftpFiles = SERIA_FtpFiles::find_all_by_file_id($ids, array('include' => array('FtpServer', 'FtpServer.FileProtocols')));
				}
				
				if (!$disableFtpRead) {
					$fileProtocolsByServerId = array();
					foreach ($ftpFiles as $ftpFile) {
						if (!($fileProtocols = $fileProtocolsByServerId[$ftpFile->ftp_server_id])) {
							$fileProtocols = $ftpFile->FtpServer->FileProtocols;
						}
						
						$fileById[$ftpFile->file_id]->protocolHandlers = $fileProtocols;
					}
				}
				
				if (!$disableMetaRead) {
					foreach ($filemeta as $metaobject) {
						$fileById[$metaobject->file_id]->meta[] = $metaobject;
					}
				}
			}
			
			return $files;
		}
		
		public static function fetchThumbnailsForObjects(array $files) {
			$ids = array();
			foreach ($files as $file) {
				$id = $file->get('id');
				if ($id && $id !== 0)
					$ids[] = $id;
			}
			
			if (!sizeof($ids)) {
				return;
			}	
			
			$relatedFileIds = array();
			$relatedFileRelation = array();
			
			$cache = new SERIA_Cache('filethumbrelation');
			$ids2 = array();
			foreach ($ids as $key => $id) {
				if (is_array($list = $cache->get($id))) {
					foreach ($list as $item) {
						list($relationId, $relationKey) = $item; 
						$relatedFileRelation[$relationId] = $relationKey;
						$relatedFileIds[] = $relationId;
					}
				} else {
					$ids2[] = $id;
				}
			}
			$ids = $ids2;
			
			if (sizeof($ids)) {
				$query = 'SELECT id, relation FROM ' . SERIA_PREFIX . '_files WHERE parent_file IN(' . implode(',', $ids) . ') AND relation LIKE \'scaled_%\'';
				$fileRows = SERIA_Base::db()->query($query)->fetchAll(PDO::FETCH_NUM);
				foreach ($fileRows as $fileRow) {
					list($id, $relation) = $fileRow;
					$relatedFileRelation[$id] = $relation;
					$relatedFileIds[] = $id;
				}
			}
			
			$cacheableList = array();
			foreach ($ids as $id) {
				$cacheableList[$id] = array();
			}
			
			$thumbnailFiles = SERIA_File::createObjects($relatedFileIds);
			foreach ($thumbnailFiles as $file) {
				$relation = $relatedFileRelation[$file->get('id')];
				$file2 = null;
				foreach ($files as $file3) {
					if ($file3->get('id') == $file->get('parent_file')) {
						$file2 = $file3;
					}
				}
				if ($file2) {
					$cacheableList[$file->get('parent_file')][] = array($file->get('id'), $relation);
					$file2->addThumbnailToCache($relation, $file);
				}
			}
			if (sizeof($cacheableList)) {
				foreach ($cacheableList as $id => $list) {
					$cache->set($id, $list, 1800);
				}
			}
		}
		
		public function addThumbnailToCache($key, $file) {
			$this->thumbnailCache[$key] = $file;
		}
		
		public function addFtpServerRecord($ftpServer) {
			$this->deleteFtpServerRecord($ftpServer);
			$ftpServerRecord = new SERIA_FtpFile();
			$ftpServerRecord->file_id = $this->get('id');
			$ftpServerRecord->ftp_server_id = $ftpServer->id;
			$ftpServerRecord->available = $ftpServer->delay + time();
			$ftpServerRecord->save();
			
			$ftpServer->filecount++;
			$ftpServer->storageusage += round($this->filesize / 1024);
			$ftpServer->save();
			$this->clearFromCache();
		}
		
		public function deleteFtpServerRecord($ftpServer) {
			$ftpServerRecords = SERIA_FtpFiles::find_all_by_file_id($this->get('id'), array('criterias' => array('ftp_server_id' => $ftpServer->id)));
			if (sizeof($ftpServerRecords)) {
				foreach ($ftpServerRecords as $ftpServerRecord) {
					$ftpServerRecord->delete();
					$ftpServer->filecount--;
					$ftpServer->storageusage -= round($this->get('filesize') / 1024);
					$ftpServer->save();
				}
			}
			$this->clearFromCache();
		}
		
		public function checkFtpServerRecords() {
			$ftpServerRecords = $this->getFtpServerRecords();
			foreach ($ftpServerRecords as $ftpServerRecord) {
				$ftpServer = $ftpServerRecord->FtpServer;
				if (!$ftpServer) {
					$ftpServerRecord->delete();
				}
				
				if (!$ftpServer->fileExists($ftpServer->file_root . '/' . $this->get('filename'))) {
					SERIA_Base::debug('File ' . $this->get('filename') . ' does not exist on FTP server. Deleting record.');
					$ftpServerRecord->delete();
				}
			}
		}
		
		public function getFtpServerRecords() {
			
			if (is_array($fromCache = $this->ftpServerCache->get($this->get('id')))) {
				return $fromCache;
			}
			
			$ftpServerRecords = SERIA_FtpFiles::find_all_by_file_id($this->get('id'), array('include' => array('FtpServer', 'FtpServer.FileProtocols')))->toArray();
			$this->ftpServerCache->set($this->get('id'), $ftpServerRecords, 1800);
			return $ftpServerRecords;
		}
		
		public function getRelatedFiles($filter = null) {
			$db = SERIA_Base::db();
			
			$query = 'SELECT * FROM ' . SERIA_PREFIX . '_files WHERE parent_file=' . $this->get('id').' ORDER BY created_date';
			$result = $db->query($query);
			$rows = $result->fetchAll(PDO::FETCH_ASSOC);
			$ids = array();
			foreach ($rows as $row) {
				$id = $row['id'];
				$relation = $row['relation'];
				
				if (($filter === null) || ($filter == $relation)) {
					$ids[] = $id;
				}
			}
			
			return SERIA_File::createObjects($ids);
		}
		
		/**
		 * Returns a single file object, or an array of file objects or true if not ready. Returns false if the file
		 * can not be converted.
		 * 
		 * @param unknown_type $converterName
		 * @return unknown_type
		 */
		public function convertTo($converterName) {
			$result = SERIA_Hooks::dispatchToFirst('SERIA_File::convertTo::'.$converterName, $this);
			if ($result === NULL)
				return false; /* Can't convert */
			if ($result === true)
				return true;
			return $result;
		}
		
		public function transcodeTo($type) {
			$transcoder = false;
			$type = strtolower($type);
			switch ($type) {
				case 'flv':
					$transcoder = SERIA_FileTranscoder::getTranscoder('Flv');
					break;
				case 'ziptoimages':
					$transcoder = SERIA_FileTranscoder::getTranscoder('Ziptoimages');
					break;
				case 'powerpoint_slides':
					$transcoder = SERIA_FileTranscoder::getTranscoder('PowerpointSlides');
					break;
			}
			if (!$transcoder) {
				throw new SERIA_Exception('No transcoder found for ' . $type);
			}
			
			$this->clearFromCache();
			$success = $transcoder->transcode($this);
			if ($success === true) {
				$status = $transcoder->getStatus($this);
				switch ($status) {
					case SERIA_FileTranscoder::STATUS_COMPLETED:
					case SERIA_FileTranscoder::STATUS_QUEUED:
					case SERIA_FileTranscoder::STATUS_TRANSCODING:
						return true;
					case SERIA_FileTranscoder::STATUS_RESTART:
						return $transcoder->reset($this);
					case SERIA_FileTranscoder::STATUS_FAILED:
						$transcoder->setStatus(SERIA_FileTranscoder::STATUS_RESTART, $this);
						/*
						 * Commit the status update!
						 */
						SERIA_Base::db()->commit();
						SERIA_Base::db()->beginTransaction();
						/*
						 * Throw an exception which should not roll back the status change above.
						 */
						throw new SERIA_Exception('Transcoding of the file failed.');
				}
			}
			return $success;
		}
		
		private function getUrl($protocols = null, $filetype = null) {
			if (!is_array($protocols)) {
				$protocols = array('http', 'https');
				if (isset($_SERVER['HTTPS'])) {
					$protocols = array('https', 'http');
				}
			}
			
			$protocols2 = array();
			foreach ($protocols as $protocol) {
				if ($protocol == 'http(s)') {
					if (isset($_SERVER['HTTPS'])) {
						$protocols2[] = 'https';
						$protocols2[] = 'http';
					} else {
						$protocols2[] = 'http';
						$protocols2[] = 'https';
					}
				} else {
					$protocols2[] = strtolower($protocol);
				}
			}
			$protocols = $protocols2;
			unset($protocols2);
			
			$file = false;
			if (!($filetype = strtolower($filetype))) {
				$file = $this;
			} else {
				if (strtolower($this->getExtension()) == $filetype) {
					$file = $this;
				} else {
					$relatedFiles = $this->getRelatedFiles();
					foreach ($relatedFiles as $relatedFile) {
						if ($relatedFile->relation == 'transcoded_' . $filetype) {
							$file = $relatedFile;
							break;
						}
					}
				}
			}
			
			if ($file) {
				$cache = new SERIA_Cache('ftp');
				
				if (!$this->protocolHandlers) {
					$this->populateProtocolHandlers();
				}
				
				$protocolToUse = null;
				foreach ($protocols as $protocol) {
					foreach ($file->protocolHandlers as $protocolHandler) {
						if ($protocolHandler->name == $protocol) {
							$available = false;
							
							if (($fromCache = $cache->get($cacheKey = 'avail' . $file->get('id') . '_' . $protocolHandler->name)) !== null) {
								$available = $fromCache;
							} else {
								if (!$ftpRecords) {
									if (!$ftpRecords = $cache->get($recordCacheKey = 'rec' . $file->get('id'))) {
										$ftpRecords = $file->getFtpServerRecords();
									}
									if (!$ftpRecords) {
										$ftpRecords = array();
									}
									$cache->set($recordCacheKey, $ftpRecords, 120);
								}
								
								foreach ($ftpRecords as $record) {
									if ($record->ftp_server_id == $protocolHandler->ftp_server_id) {
										if ($record->available <= time()) {
											$available = true;
											break;
										}
									}
								}
								
								$cache->set($cacheKey, $available, 60);
							}
							
							if ($available) {
								$protocolToUse = $protocolHandler;
								break 2;
							}
						}
					}
				}
				
				$localStorageProtocol = 'http';
				if (isset($_SERVER['HTTPS'])) {
					$localStorageProtocol = 'https';
				}
				
				// Check if local storage protocol is preferred over available FTP servers
				foreach ($protocols as $protocol) {
					if ($protocolToUse && ($protocolToUse->name == $protocol)) {
						break;
					}
					if ($protocol == $localStorageProtocol) {
						$protocolToUse = $file;
						break;
					}
				}
				
				if (!$protocolToUse) {
					SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::ERROR, 'File ' . $this->get('filename') . ' was requested for protocols ' . implode(', ', $protocols) . ' but is not available using any requested protocols.');
					throw new Exception('Cannot find any of the requested protocol handlers for file');
				}
				
				array_unshift($protocols, $preferredProtocol = array_shift($protocols));
				
				if (get_class($protocolToUse) == 'SERIA_File') {
					// Use local storage

					if ($this->getMeta('quarantine')) {
						/*
						 * Disable cache..
						 */
						SERIA_Base::preventCaching();

						/*
						 * Create the file key.
						 */
						$key = sha1($_SERVER['REMOTE_ADDR'].$file->get('filename'));

						/*
						 * URL to file-serving script..
						 */
						$url = SERIA_HTTP_ROOT.'/seria/files/quarantine.php?fileid='.htmlspecialchars($file->get('id')).'&key='.$key;
					} else if ($file->url) { // $this->url is deprecated, but use if existent
						$url = $file->url;
					} else {
						$url = SERIA_UPLOAD_HTTP_ROOT . '/' . $file->filename;
					}
					list($protocol) = explode('://', strtolower($url), 2);
					if ($protocol != $preferredProtocol) {
						SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, 'File ' . $this->get('filename') . ' is not using preferred protocol (' . $preferredProtocol . '), but using ' . $protocol);
					}
					
					return $url;
				}
				
				// Use FTP server
				$url = $protocolToUse->getUrl($file->get('filename'));
				if (($protocol = strtolower($protocolToUse->name)) != $preferredProtocol) {
					SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, 'File ' . $this->get('filename') . ' is not using preferred protocol (' . $preferredProtocol . '), but using ' . $protocol);
				}
				return $url;
			} else {
				SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::ERROR, 'File ' . $this->get('filename') . ' is not found in requested format: ' . $filetype . ' as requested');
				throw new Exception('File not found or file not available in requested format');
			}
		}
		
		function get($field, $arg1 = null, $arg2 = null) {
			switch($field) {
				case "id":
					return $this->id;
					break;
				case "url": // should always return an absolute url
					return $this->getUrl($arg1, $arg2);
					break;
				case "filename": // allways the filename
					return $this->filename;
					break;
				case "contentType": // MIME content type of the file
					return $this->contentType;
					break;
				case "filesize": // file size (for use when the file is uploaded to a server)
					return $this->filesize;
					break;
				case "updatedAt":
					return $this->updatedAt;
					break;
				case "createdAt":
					return $this->createdAt;
					break;
				case "localPath":
					return SERIA_UPLOAD_ROOT."/".$this->filename;
				case 'parent_file':
					return $this->parent_file;
					break;
				case 'file_article_id':
					return $this->fileArticleId;
					break;
				default:
					throw new SERIA_Exception("Unknown field '$field'.");
			}
		}
		
		function __wakeup() {
		}
		
		protected function loadFromQueryRow($file) {
			$this->row = $file;
			$this->id = $file["id"];
			$this->url = $file["url"];
			$this->filename = $file["filename"];
			$this->contentType = $file["content_type"];
			$this->filesize = $file["filesize"];
			$this->updatedAt = strtotime($file['updated_at']);
			$this->createdAt = strtotime($file['created_date']);
			$this->fileArticleId = $file["file_article_id"];
			
			// Sub file data
			$this->parent_file = $file['parent_file'];
			$this->relation = $file['relation'];
		}
		
		public function populateProtocolHandlers($ftpServerRecords = null) {
			if (!$ftpServerRecords) {
				 $ftpServerRecords = $this->getFtpServerRecords();
			}
			
			foreach ($ftpServerRecords as $ftpServerRecord) {
				if ($ftpServerRecord->file_id == $this->get('id')) {
					$ftpServer = $ftpServerRecord->FtpServer;
					if ($ftpServer) {
						$this->protocolHandlers = array_merge($this->protocolHandlers, $ftpServer->FileProtocols);
					}
				}
			}
		}
		
		public static function copyFileToObject($path) {
			$file = new self(null);
			$file->populateObjectFromFilePath($path, basename($path), false);
			return $file;
		}
		
		public function populateObjectFromFilePath($filePath, $targetFileName, $overwrite, $copyMethod='copy') {
			$db = SERIA_Base::db();

			// this is a new file to be added to SERIA Publisher, check that the parameter did not come from the query string (extra security)
			if(stripos($_SERVER["QUERY_STRING"], $filePath)!==false)
				throw new SERIA_Exception("Filename passed trough URL, access denied.");

			// move file to SERIA_UPLOAD_ROOT-folder
			if(!is_dir(SERIA_UPLOAD_ROOT))
				throw new Exception("Folder ".SERIA_UPLOAD_ROOT." (SERIA_UPLOAD_ROOT) does not exist.");

			$pi = pathinfo(($targetFileName ? $targetFileName : $filePath));
			$pi["basename"] = $this->filename = SERIA_Sanitize::filename($pi["filename"]).".".SERIA_Sanitize::filename($pi["extension"]);
			$targetFile = SERIA_UPLOAD_ROOT."/".$this->filename;

			/*
			 * Check the file extension against a whitelist in a database table. This should disallow harmful extensions like .php/...
			 */
			$rows = SERIA_Base::db()->query('SELECT * FROM '.SERIA_PREFIX.'_filetypes WHERE extension = '.SERIA_Base::db()->quote($pi['extension']))->fetchAll();
			if (count($rows) <= 0)
				throw new Exception('File extension '.$pi['extension'].' is not allowed in file uploads.');
			foreach ($rows as $row) {
				if ($row['restricted_upload'] != 0 && SERIA_Base::viewMode() != 'admin')
					throw new Exception('File extension '.$pi['extension'].' is restricted in file uploads. Upload denied.');
			}
			
			if(!$overwrite)
			{ // should never overwrite files, so we need to check if the target file exists
				$i = 1;
				while(file_exists($targetFile))
				{ // try another filename
					$pi["basename"] = $this->filename = $pi["filename"]."-".(++$i).".".$pi["extension"];
					$targetFile = SERIA_UPLOAD_ROOT."/".$this->filename;
				}
				$pi["filename"] = $pi["filename"]."-".$i;
			} else {
				if (file_exists($targetFile)) {
					unlink($targetFile);
				}
			}
			
			$status = false;
			$status = $copyMethod($filePath, $targetFile);

			if($status)
			{ // file was successfully moved to uploads folder. Create row in database.
				$currentMask = umask(0);
				chmod($targetFile, 0644);
				umask($currentMask);
				
				$this->url = NULL; 
				$this->filesize = filesize($targetFile);
				$this->contentType = SERIA_Lib::getContentType($targetFile);

				$this->updatedAt = time();

				$errorCounter = 0;
				while(true)
				{
					$this->id = SERIA_Base::guid('file');
					try
					{
						$db->exec("INSERT INTO ".SERIA_PREFIX."_files (
							id, filename, created_date, filesize, content_type, updated_at, parent_file, relation) VALUES (
							".$db->quote($this->id).", 
							".$db->quote($this->filename).", 
							NOW(), 
							".$db->quote($this->filesize).",
							".$db->quote($this->contentType).",
							NOW(),
							" . $db->quote($this->parent_file) . ",
							" . $db->quote($this->relation) . ")");

						return $this->id;
						break;
					} catch (PDOException $e) {
						// On 10th exception, throw it again as a permanent error to prevent infinite loop
						if ($errorCounter++ > 10) {
							$this->id = null;
							throw $e;
						}
					}
				}
			}
			else
				throw new SERIA_Exception("Error moving file to upload directory (".$targetFile.')');
		}
		public function setFileRelation(SERIA_File $newParentFile, $relation)
		{
			if (!$this->id)
				throw new SERIA_Exception('You can only put a new relation on a file that has a stored row in the database. Populate the file with the relation set from the start instead.');
			$this->increaseReferrers();
			try {
				SERIA_Base::db()->update('{files}', array('id' => $this->id), array('parent_file', 'relation'), array(
					'parent_file' => $newParentFile->get('id'),
					'relation' => $relation
				));
			} catch (Exception $e) {
				$this->decreaseReferrers();
				throw $e;
			}
		}

		function getArticle() {
			if ($this->fileArticleId) {
				return SERIA_Article::createObjectFromId($this->fileArticleId);
			} else {
				return false;
			}
		}

		function createArticle($title=false) {
			if ($this->id) {
				if (!SERIA_Base::isElevated() && !SERIA_Base::isAdministrator() && isset($_SESSION[SERIA_PREFIX."_FILES_NAMESPACE"]) && $_SESSION[SERIA_PREFIX."_FILES_NAMESPACE"]) {
					/*
					 * This is a NUPA special .. we can assume access to file upload / create article
					 * Tempo admin grant neccesary for nonadmins..
					 */
					$elevatePriv = true;
				} else
					$elevatePriv = false;
				if ($elevatePriv)
					$fa = SERIA_Base::elevateUser("SERIA_Article::createObject", "SERIA_File");
				else
					$fa = SERIA_Article::createObject("SERIA_File");
				$fa->set("title", $title !== false ? $title : $this->filename);
				$fa->set("file_id", $this->id);
				$fa->set('is_published', 1);
				$fa->set('pending_publish', 0);
				$fa->save();
				$this->fileArticleId = $fa->get("id");
				$db = SERIA_Base::db();
				$db->exec("UPDATE ".SERIA_PREFIX."_files SET file_article_id=:fileArticleId".$db->quote($this->fileArticleId)." WHERE id=:id", array('fileArticleId' => $this->fileArticleId, 'id' => $this->id));
			} else {
				throw new SERIA_Exception("Cant create SERIA_FileArticle before SERIA_File is created");
			}
		}

		function __construct($filePath=null, $targetFileName=false, $overwrite=false, $subFileOf = null, $subFileRelation = null, $moveUploadedFile=true)
		{
			$this->protocolhandlers = array();
			
			$this->ftpServerCache = new SERIA_Cache('ftpserverrecord');
			
			$db = SERIA_Base::db();
			if (is_array($filePath)) {
				$this->loadFromQueryRow($filePath);
			} elseif(is_numeric($filePath) && intval($filePath) != 0)
			{ // this is a numeric file ID
				$file = $db->query("SELECT * FROM ".SERIA_PREFIX."_files WHERE id=:id", array('id' => $filePath))->fetch(PDO::FETCH_ASSOC);
				if (!$file)
					throw new SERIA_NotFoundException('File not found!');
				$this->loadFromQueryRow($file);
			}
			elseif(file_exists($filePath))
			{
				$this->parent_file = 0;
				$this->relation = '';
				
				if ($subFileOf) {
					$this->parent_file = (int) $subFileOf;
					$this->relation = (string) $subFileRelation;
					
					$this->populateObjectFromFilePath($filePath, $targetFileName, $overwrite, 'rename');
				} else {
					if($moveUploadedfile)
						$this->populateObjectFromFilePath($filePath, $targetFileName, $overwrite, 'move_uploaded_file');
					else
						$this->populateObjectFromFilePath($filePath, $targetFileName, $overwrite, 'rename');
				}
			}
			elseif ($filePath === null) {
				return;
			} else {
				throw new SERIA_Exception("File not found or unknown file ID");
			}
		}

		/**
		*	Increase the counter for referring articles for this file.
		*/
		function increaseReferrers()
		{
			$this->clearFromCache();
			$this->row["referrers"]++;
			return SERIA_Base::db()->exec("UPDATE ".SERIA_PREFIX."_files SET referrers=referrers+1, updated_at=NOW() WHERE id=:id", array('id' => $this->get("id")));
		}

		/**
		*	Decrease the counter for referring articles for this file.
		*/
		function decreaseReferrers()
		{
			$this->clearFromCache();
			$this->row["referrers"]--;
			return SERIA_Base::db()->exec("UPDATE ".SERIA_PREFIX."_files SET referrers=referrers-1, updated_at=NOW() WHERE id=:id", array('id' => $this->get("id")));
		}

		function delete($really=false)
		{
			$this->clearFromCache();
			SERIA_Base::debug("SERIA_File::delete(): Deleting ".$this->get("filename"));
			$db = SERIA_Base::db();
			if($really===false)
				throw new SERIA_Exception("Files should not be deleted, they should have their referrer count decreased with SERIA_File::decreaseReferrers().");

				
			// Decrease referer count on sub files
			foreach ($this->getRelatedFiles() as $file) {
				try {
					$file->decreaseReferrers();
				} catch (Exception $null) {}
			}
				
			if(file_exists(SERIA_UPLOAD_ROOT."/".$this->filename))
			{
				SERIA_Base::debug("- Deleting locally");
				try
				{
					@unlink(SERIA_UPLOAD_ROOT."/".$this->filename);
				}
				catch (Exception $e)
				{
					throw new SERIA_Exception("Unable to delete file from filesystem.");
				}
			}

			$res = $db->exec("DELETE FROM ".SERIA_PREFIX."_files WHERE id=:id", array('id' => $this->id));
			if($res)
				SERIA_Base::debug("- Deleted from database");
			else
				SERIA_Base::debug("- NOT Deleted from database");

			return $res;
		}
		
		public function rename($newName) {
			$this->clearFromCache();
			$db = SERIA_Base::db();
			
			$pathInfo = pathinfo($newName);
			$newName = SERIA_Sanitize::filename($pathInfo['filename']) . '.' . SERIA_Sanitize::filename($pathInfo['extension']);
			$oldName = $this->get('filename');
			
			$query = 'SELECT COUNT(*) FROM ' . SERIA_PREFIX . '_files WHERE filename=' . $db->quote($newName); 
			$queryResult = $db->query($query);
			list($count) = $queryResult->fetch(PDO::FETCH_NUM);
			if ($count >= 1) {
				throw new SERIA_Exception('Filename ' . $newName . ' allready exists');
			}
			$queryResult->closeCursor();
			
			$status = '';
			try {
				$status = 'db';
				$query = 'UPDATE ' . SERIA_PREFIX . '_files SET filename=:filename WHERE id=:id';
				if ($db->query($query, array('filename' => $newName, 'id' => $this->get('id')))) {
					$status = 'rename';
					if (rename(SERIA_UPLOAD_ROOT . '/' . $oldName, SERIA_UPLOAD_ROOT . '/' . $newName)) {
						$this->filename = $newName;
						return true;
					} else {
						throw new Exception('File rename failed');
					}
				}
			} catch (Exception $exception) {
				if ($status == 'rename') {
					// Rename failed. Revert database change.
					$query = 'UPDATE ' . SERIA_PREFIX . '_files SET filename=' . $db->quote($oldName) . ' WHERE id=' . (int) $this->get('id');
					$db->query($query);
				}
				
				throw new SERIA_Exception('File rename failed: ' . $exception->getMessage());
			}
		}

		/**
		 * Obfuscates the filename to avoid unauthorized downloads. This is required
		 * for untrusted uploads, as otherwise they (the enemy) can use this for free
		 * data storage and hosting.
		 *
		 * WARNING: BE SURE NOT TO LEAK ->get('filename') TO UNTRUSTED CLIENTS!
		 *
		 * @return unknown_type
		 */
		public function quarantine()
		{
			if ($this->getMeta('quarantine'))
				return;
			$origFilename = $this->get('filename');
			$newFilename = 'quarantined_'.mt_rand().'_'.mt_rand().'_'.mt_rand().'__'.$origFilename;
			$this->rename($newFilename);
			$this->setMeta('quarantine', true);
			$this->setMeta('origFilename', $origFilename);
		}
		public function unquarantine()
		{
			if (!$this->getMeta('quarantine'))
				return;
			/*
			 * Try to rename back to original name..
			 */
			$originalName = $this->getMeta('origFilename');
			if ($originalName) {
				try {
					$this->rename($originalName);
				} catch (Exception $e) {
					$pi = pathinfo($originalName);
					$originalName = $pi['filename'].'_'.mt_rand().'.'.$pi['extension'];
					try {
						$this->rename($originalName);
					} catch (Exception $e) {
						/* Ignore: rename failed, but we can still fall back to quarantine name.. */
					}
				}
			}
			$this->setMeta('quarantine', false);

			/*
			 * Unquarantine related files (thumbnails, etc)
			 */
			$files = $this->getRelatedFiles();
			foreach ($files as $file)
				$file->unquarantine();
		}
		public function isQuarantined()
		{
			return $this->getMeta('quarantine') ? true : false;
		}
		
		public function getMetaFromDb() {
			$fileMetaCache = new SERIA_Cache('filemetaCache');
			if ($this->meta == null) {
				$id = $this->get('id');
				if (is_array($this->meta = $fileMetaCache->get($id))) {
					if ($this->meta && (!is_array($this->meta[0]) || is_object($this->meta[0]))) {
						/* Invalid cache */
						$this->meta = null;
						$this->setMetaFromObject(SERIA_FileMetas::find_all_by_file_id($id));
					}
					return;
				} else {
				}
				
				$this->setMetaFromObject(SERIA_FileMetas::find_all_by_file_id($id));
			}
		}
		
		public function setMetaFromObject($object, $disableCache = false) {
			$this->clearFromCache();
			if (is_object($object)) {
				$object = $object->toArray();
			}
			if (!is_array($object)) {
				return false;
			}
			
			$this->meta = array();
			foreach ($object as $metaobject) {
				if ($metaobject->file_id == $this->get('id')) {
					$this->meta[] = array(
						'id' => $metaobject->id,
						'file_id' => $metaobject->file_id,
						'key' => $metaobject->key,
						'value' => $metaobject->value
					);
				}
			}
			
			if (!$disableCache) {
				$fileMetaCache = new SERIA_Cache('filemetaCache');
				$fileMetaCache->set($this->get('id'), $this->meta);
			}
		}
		
		public function getMeta($key = null) {
			$this->getMetaFromDb();
			
			if ($key !== null) {
				foreach ($this->meta as $meta) {
					/*
					 * TODO - Cache may contain ActiveRecord objects.
					 * Has fallback for ActiveRecord.
					 */
					if (is_array($meta) && $meta['key'] == $key)
						return $meta['value'];
					if ($meta->key == $key) {
						return $meta->value;
					}
				}
			} else {
				$return = array();
				foreach ($this->meta as $meta) {
					/*
					 * TODO - Cache may contain ActiveRecord objects.
					 * Has fallback for ActiveRecord.
					 */
					if (is_array($meta))
						$return[$meta['key']] = $meta['value'];
					else
						$return[$meta->key] = $meta->value;
				}
				
				return $return;
			}
			
			return null;
		}
		public function setMeta($key, $value) {
			$this->clearFromCache();
			$this->getMetaFromDb();
			
			$found = false;
			foreach ($this->meta as $mkey => $meta) {
				/*
				 * Has been active-record objects before. Catch stale cache.
				 */
				if (is_object($meta) || !isset($meta['key']))
					throw new SERIA_Exception('Should store meta-values as arrays now.');
				if ($meta['key'] == $key) {
					$found_key = $mkey;
					$found = $meta;
					break;
				}
			}

			/*
			 * Speed improvement can be gained by not re-reading the cache.
			 * Need to use guid or last_insert_id to do that.
			 */
			if (!$found) {
				SERIA_Base::db()->exec('INSERT INTO {file_meta} (`file_id`, `key`, value) VALUES (?, ?, ?)', array($this->get('id'), $key, $value), true);
				$fileMetaCache = new SERIA_Cache('filemetaCache');
				$fileMetaCache->set($this->get('id'), false);
				$this->meta = null;
				return;
			} else {
				$meta = $found;
			}
			
			if ($value == null) {
				$value = '';
			}

			SERIA_Base::db()->exec('UPDATE {file_meta} SET value = :value WHERE id = :id', array('value' => $value, 'id' => $meta['id']), true);
			
			$fileMetaCache = new SERIA_Cache('filemetaCache');
			$fileMetaCache->set($this->get('id'), false);
			$this->meta[$found_key]['value'] = $value;
		}
		
		public static function getMetaReadQueue($limit = null) {
			$db = SERIA_Base::db();
			
			$query = 'SELECT id FROM ' . SERIA_PREFIX . '_files WHERE meta_update < updated_at';
			if ($limit > 0) {
				$query .= ' LIMIT ' . (int) $limit;
			}
			$queryResult = $db->query($query);
			$files = array();
			$file_ids = array();
			$rows = $queryResult->fetchAll(PDO::FETCH_NUM);
			foreach ($rows as $row) {
				list($id) = $row;
				
				$file_ids[] = $id;
			}
			
			return self::createObjects($file_ids);
		}
		
		public function readMetaDataFromFile() {
			$this->clearFromCache();
			$metaReader = new SERIA_FileMetaReader($this);
			$metaReader->read();
			$this->updateMetaReadTimestamp();
		}
		
		public function updateMetaReadTimestamp() {
			$this->clearFromCache();
			$db = SERIA_Base::db();
			$query = 'UPDATE ' . SERIA_PREFIX . '_files SET meta_update = NOW() WHERE id=:id';
			$db->query($query, array('id' => $this->get('id')));
		}

		function getConverted($type) {
			//TODO: Hentes fra kontrollpanelet
		}
		
		function getExtension()
		{
			$filename = $this->get('filename');
			
			return strtolower(trim(array_pop(explode('.', $filename))));
		}
		
		function createTmpFile($readOnly=false) {
			$randomFilename = '';

			if(!$readOnly)
			{
				$randomFilename = tempnam(SERIA_TMP_ROOT, $this->filename);

				if (!copy(SERIA_UPLOAD_ROOT."/".$this->filename, $randomFilename)) {
					return false;
				}
				return $randomFilename;
			}
			else
			{
				return SERIA_UPLOAD_ROOT."/".$this->filename;
			}
			
			return false;
		}
		
		public function getThumbnailUrl($width, $height, $params = array()) {
			if(empty(self::$_imageThumbnailCache))
				self::$_imageThumbnailCache = new SERIA_Cache('fileImageThumbnails');

			$cacheKey = $this->get('id').md5($width."-".$height."-".serialize($params));

			if($res = self::$_imageThumbnailCache->get($cacheKey))
			{
				return $res;
			}

			list($url) = $this->getThumbnail($width, $height, $params);

			self::$_imageThumbnailCache->set($cacheKey, $url, 3600);
			return $url;
		}
		
		public function getThumbnail($width, $height, $params = array()) {
			if (!$this->id && $this->id !== 0)
				throw new SERIA_Exception('Can\'t create a thumbnail for file that is not saved to the database.');
			$protocols = array();
			if (isset($_SERVER['HTTPS'])) {
				$protocols = array('https', 'http', 'ftp');
			} else {
				$protocols = array('http', 'https', 'ftp');
			}
			
			$params = array(
				'width' => $width,
				'height' => $height,
				'transfill' => in_array('transparent_fill', $params)
			);
			
			$transfillKey = '';
			if ($params['transfill']) {
				$transfillKey = '_transfill';
			}
			
			if (!sizeof($this->thumbnailCache)) {
				self::fetchThumbnailsForObjects(array($this));
			}
			
			$relationKey = 'scaled_' . $width . 'x' . $height . $transfillKey;
			if (isset($this->thumbnailCache[$relationKey])) {
				$thumbnail = $this->thumbnailCache[$relationKey];
				return array($thumbnail->get('url', $protocols), $thumbnail->getMeta('image_width'), $thumbnail->getMeta('image_height'));
			}
			
			$cache = new SERIA_Cache('thumbnaildata');
			$cacheKey = $this->get('id') . '_' . crc32(implode('.', $protocols)) . $params['width'] . 'x' . $params['height'] . '_' . $params['transfill'];
			if (($fromCache = $cache->get($cacheKey)) !== null) {
				return $fromCache;
			}
			
			if (!$this->isImage() && !$this->isVideo()) {
				throw new SERIA_Exception('Cannot create thumbnail: File is not an image (Content-Type: '.$this->contentType.')');
			}
			
			$this->clearFromCache();
			$transcoder = SERIA_FileTranscoder::getTranscoder('Thumbnail');
			
			$thumbnail = $transcoder->transcode($this, $params);
			if (!$thumbnail) {
				throw new Exception('Unable to create thumbnail');
			}
			$data = array($thumbnail->get('url', $protocols), $thumbnail->getMeta('image_width'), $thumbnail->getMeta('image_height'));
			$cache->set($cacheKey, $data, 3600);
			return $data;
		}
		
		function isImage() {
			list($type) = explode("/", $this->contentType);
			return $type=="image";
		}
		
		function isVideo() {
			list($type) = explode("/", $this->contentType);
			return $type==="video";
		}

		function __toString() {
			return $this->filename;
		}

		public static function createFromUser($value)
		{
			return SERIA_File::createObject($value);
		}

		public static function renderFormField($fieldName, $value, array $params = NULL, $hasError = false)
		{
			$r = '<input type="hidden" name="'.$fieldName.'" class="fileselect'.($hasError?' ui-state-error':'').'" id="'.$fieldName.'" />';

			return $r;
		}

		public static function createFromDb($value)
		{
			return SERIA_File::createObject($value);
		}

		public function toDbFieldValue()
		{
			return $this->id;
		}

		public function toDB() { return $this->id; }

		public static function MetaField()
		{
			return array(
				'type' => 'integer',
				'class' => 'SERIA_File',
			);
		}
	}
