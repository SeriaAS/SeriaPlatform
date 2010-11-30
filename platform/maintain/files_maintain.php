<?php
	function files_maintain_ftpSync() {
		if(!class_exists('SERIA_FTPs'))
			return;
		// Create arrays of filenames and objects for local files
		$ftpServers = SERIA_FTPs::find_all(array('include' => array('FileProtocols', 'Filetypes')));
		if (!sizeof($ftpServers)) {
			SERIA_Base::debug(' - No FTP servers configured');
			return;
		}

		// If it is more than 24 hours since last recalculation of ftp server storage usage - recalculate
		foreach ($ftpServers as $ftpServer) {
			try {
				if ($ftpServer->storageupdate < (time() - (24 * 3600))) {
					SERIA_Base::debug('Recalculating storage usage on FTP server ' . $ftpServer->host . '/' . $ftpServer->username);
					$ftpServer->recalculateStorageUsage();
					SERIA_Base::debug($ftpServer->filecount . ' files, ' . round($ftpServer->storageusage / 1024, 2) . ' MB storage usage');
				}
			} catch (Exception $exception) {
				SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, _t('Recalculation of storage usage on FTP server %SERVER% failed: ' . $exception->getMessage(), array('SERVER' => $ftpServer->host . '/' . $ftpServer->username)));
			}
		}

		// Create a list of id => filename for each file in the database.
		$localFiles = array();
		$query = 'SELECT id, filename FROM ' . SERIA_PREFIX . '_files';

		$rows = SERIA_Base::db()->query($query)->fetchAll(PDO::FETCH_NUM);
		foreach ($rows as $row) {
			$localFiles[$id = (int) $row[0]] = $row[1];
		}

		// Create a list of all ftp server group.
		// Each group contain one or more servers supporting one particular protocol
		// Each of the configured servers can exist in different groups if they support
		// more than one request protocol.
		$ftpServerGroups = array();
		foreach ($ftpServers as $ftpServer) {
			$protocols = array();
			foreach ($ftpServer->FileProtocols as $protocol) {
				$protocols[$protocol->name] = $protocol->name;
			}
			sort($protocols);
			foreach ($protocols as $protocol) {
				$ftpServerGroups[strtolower($protocol)][$ftpServer->id] = $ftpServer;
			}
		}

		// Check if the file type configuration has been changed since last maintain run
		// if it hasn't changed there is no need to check if files allready uploaded still matching
		// configured file patterns.
		$filePatternsChanged = array();
		foreach ($ftpServers as $ftpServer) {
			$filePatternsChanged[$ftpServer->id] = false;
			$fileTypes = $ftpServer->Filetypes;
			$pattern = array();
			foreach ($fileTypes as $fileType) {
				$pattern[] = $fileType->type . '_' . $fileType->pattern;
			}
			sort($pattern);
			$pattern = md5(implode('/', $pattern));
			if ($pattern != $ftpServer->filetypepattern) {
				SERIA_Base::debug('File type patterns on FTP server ' . $ftpServer->host . '/' . $ftpServer->username . ' has changed.');
				$filePatternsChanged[$ftpServer->id] = true;
				$ftpServer->filetypepattern = $pattern;
				$ftpServer->save();
			}
		}

		foreach ($ftpServerGroups as $ftpServerGroup) {
			$deleteQueue = array();
			$addRecordQueue = array();
			$ftpFiles = array();

			if (sizeof($ftpServerGroup)) {
				// Create array of all files currently on any ftp servers in current group
				foreach ($ftpServerGroup as $inGroupId => &$ftpServer) {
					try {
						$currentFtpFileObjects = $ftpServer->getFileRootFileListAsObjects();
						$currentFtpFiles = $ftpServer->getFileRootFileList();
						if (!is_array($currentFtpFiles)) {
							$currentFtpFiles = array();
						}

						// Check if files uploaded to server still matching
						// the configured file patterns. This block is only run if the server
						// has different file type configuration after last maintain run.
						$filesToDelete = array();
						$filesToAddRecord = array();
						if ($filePatternsChanged[$ftpServer->id]) {
							foreach ($currentFtpFiles as $ftpFile) {
								$ftpFile = basename($ftpFile);

								// Delete files no longer matching upload patterns
								if (!$ftpServer->checkFilenameSupport($ftpFile)) {
									SERIA_Base::debug('Filename ' . $ftpFile . ' does no longer match pattern on server ' . $ftpServer->host . '/' . $ftpServer->username . '. Deleting.');

									// Search for file ID
									$file_id = 0;
									foreach ($localFiles as $id => $filename) {
										if ($filename == $ftpFile) {
											$file_id = $id;
										}
									}

									if ($file_id) {
										$filesToDelete[$file_id] = $ftpFile;
									}
								}
							}
						}

						$ftpFiles = array_merge($ftpFiles, $currentFtpFiles);
						// Find files existing in server, but not in local file list.
						// Files not existing in local list will be deleted from server.
						foreach (array_diff($currentFtpFiles, $localFiles) as $key => $value) {
							$filesToDelete[$key] = $value;
						}

						// Find all files on FTP server but not stored in database FTP server file list
						$query = 'SELECT files.id file_id, files.filename filename, ftpfiles.ftp_server_id ftpServer_id FROM ' . SERIA_PREFIX . '_ftp_files ftpfiles RIGHT JOIN ' . SERIA_PREFIX . '_files files ON files.id = ftpfiles.file_id WHERE ftpfiles.ftp_server_id IS NULL OR ftpfiles.ftp_server_id = ' . $ftpServer->id;
						$fileListRaw = SERIA_Base::db()->query($query)->fetchAll(PDO::FETCH_ASSOC);
						$fileList = array();
						$invertedFileList = array();
						foreach ($fileListRaw as $file) {
							if ($file['ftpServer_id'] == $ftpServer->id) {
								$fileList[$file['file_id']] = $file['filename'];
							}
							$invertedFileList[$file['filename']] = $file['file_id'];
						}

						$filesWithoutRecord = array_diff($currentFtpFiles, $fileList);

						foreach ($filesWithoutRecord as $filename) {
							if (isset($invertedFileList[$filename])) {
								$id = $invertedFileList[$filename];
								if (!$filesToDelete[$id]) {

									$ftpFileSize = -1;
									foreach ($currentFtpFileObjects as $item) {
										if ($item->filename == $filename) {
											$ftpFileSize = $item->size;
										}
									}

									if (($ftpFileSize >= 0) && (filesize(SERIA_UPLOAD_ROOT . '/' . $filename) == $ftpFileSize)) {
										SERIA_Base::debug('Found file on FTP server not having local database record (' . $filename . '/' . $id . '). File size match. Add record.');
										$filesToAddRecord[] = $id;
									} else {
										SERIA_Base::debug('Found file on FTP server not having local database record (' . $filename . '/' . $id . '). File size do not match. Deleting.');
										$filesToDelete[$id] = $filename;
									}
								}
							}
						}


						foreach ($filesToDelete as $id => $filename) {
							$deleteQueue[$id] = array($filename, $ftpServer);
						}
						foreach ($filesToAddRecord as $id) {
							$addRecordQueue[$id] = $ftpServer;
						}
					} catch (Exception $exception) {
						SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::ERROR, _t('Ftp server %HOST%/%USERNAME% failed: ' . strip_tags($exception->getMessage()), array('HOST' => $ftpServer->host, 'USERNAME' => $ftpServer->username)));
						SERIA_Base::debug('FTP server ' . $ftpServer->host . '/' . $ftpServer->username . ' failed: ' . strip_tags($exception->getMessage()));

						// Remove FTP server from group
						unset($ftpServerGroup[$inGroupId]);
					}
				}
				unset($ftpServer);

				// Get a list of all local files not existing on the FTP server.
				$filesNotOnFtp = array_diff($localFiles, $ftpFiles);
				if (!is_array($filesNotOnFtp)) {
					$filesNotOnFtp = array();
				}

				SERIA_Base::debug(sizeof($filesNotOnFtp) . ' files to check for possible FTP server upload');

				$cache = new SERIA_Cache('filematch');

				// Add records for found files on FTP server not having any database record for FTP server existence
				$fileObjects = SERIA_File::createObjects(array_keys($addRecordQueue));
				foreach ($addRecordQueue as $id => $ftpServer) {
					foreach ($fileObjects as $fileObject) {
						if ($fileObject->get('id') == $id) {
							$fileObject->addFtpServerRecord($ftpServer);
						}
					}
				}

				// Find file objects for all files not on FTP
				$localFileObjects = array();
				$ids = array();
				$ftpServerToUploadTo = array();
				$shuffleCounter = 10;
				$filematchCache = array();

				$checkedFiles = array();
				foreach ($ftpServerGroup as $ftpServer) {
					if (!$filePatternsChanged[$ftpServer->id]) {
						$fromCache = $cache->get('checkedFileList_' . $ftpServer->id);
						if (!$fromCache) {
							$fromCache = array();
						} else {
							SERIA_Base::debug('File match list read from cache for server ' . $ftpServer->host . '/' . $ftpServer->username);
						}
						$checkedFiles[$ftpServer->id] = $fromCache;
					}
				}

				$fileCounter = 0;
				$startTime = microtime(true);
				$remainingFiles = sizeof($filesNotOnFtp);

				foreach ($filesNotOnFtp as $id => $filename) {
					$add = false;

					// Shuffle ftp server group for each 10th file to randomize which server to upload to
					if (++$shuffleCounter >= 10) {
						shuffle($ftpServerGroup);
						$shuffleCounter = 0;
					}

					if (SERIA_DEBUG) {
						$remainingFiles--;
						if (++$fileCounter == 1000) {
							$endTime = microtime(true);
							$duration = round($endTime - $startTime, 3);
							$startTime = microtime(true);
							SERIA_Base::debug($fileCounter . ' files checked. Time: ' . $duration . ' seconds. ' . $remainingFiles . ' files left.');
							$fileCounter = 0;
						}
					}
					
					foreach ($ftpServerGroup as $ftpServer) {
						$add = false;
						
						if (!isset($checkedFiles[$ftpServer->id][$filename])) {
							if ($ftpServer->checkFilenameSupport($filename)) {
								$add = true;
							}
							
							$checkedFiles[$ftpServer_id][$filename] = $add;
						} else {
							$add = $checkedFiles[$ftpServer_id][$filename];
						}
						
						if ($add) {
							if (!$ftpServer->validateFileForUpload(SERIA_UPLOAD_ROOT . '/' . $filename)) {
								$add = false;
							} else {
								break;
							}
						}
					}
					
					if ($add) {
						$ids[] = $id;
						// The ftpServer object is still containing last iterated server -
						// store it for later usage.
						$ftpServerToUploadTo[$id] = $ftpServer;
					}
				}
				
				foreach ($filematchCache as $server_id => $data) {
					$cache->set($server_id, $data);
				}
				
				foreach ($checkedFiles as $server_id => $list) {
					$cache->set('checkedFileList_' . $server_id, $list, 1800);
				}
				
				// Limit file upload to 500 files for each run
				if (!isset($_GET['disableFileLimit'])) {
					$fileLimit = 500;
				} else {
					$fileLimit = 0;
				}
				if ((sizeof($ids) > $fileLimit) && $fileLimit) {
					$id_keys = array_rand($ids, $fileLimit);
				} else {
					$id_keys = array_keys($ids);
				}
				if (!is_array($id_keys)) {
					$id_keys = array();
				}
				$ids2 = array();
				foreach ($id_keys as $key) {
					$ids2[] = $ids[$key];
				}
				$ids = $ids2;
				unset($ids2);
				
				$fileObjects = SERIA_File::createobjects($ids, true, true);
				foreach ($fileObjects as $fileObject) {
					$localFileObjects[$fileObject->get('id')] = $fileObject;
				}
				
				$counter = 0;
				$totalFiles = sizeof($localFileObjects);
				
				foreach ($localFileObjects as $id => $fileObject) {
					$filename = $fileObject->get('filename');
					++$counter;
					
					// FTP server is allready selected, get it from stored hash
					$ftpServer = $ftpServerToUploadTo[$fileObject->get('id')];
					
					// If ftpServer is empty, there is no supporting servers in this group,
					// ignore group for this file.
					// (it should not be here at all of there is no ftpServer object)
					if ($ftpServer) {
						try {
							$file = $fileObject;
							$filePath = SERIA_UPLOAD_ROOT . '/' . $filename;
							if (!file_exists($filePath)) {
								throw new Exception('Local file does not exist');
							}
							$file->checkFtpServerRecords();
							
							SERIA_Base::debug('Uploading file (' . $counter . '/' . $totalFiles . ') ' . $filename . ' to FTP server ' . $ftpServer->host);
							$ftpServer->uploadFileToFileRoot($filePath, $filename);
							$file->addFtpServerRecord($ftpServer);
						} catch (Exception $exception) {
							SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, _t('Upload of file(s) (including %FILENAME%) is failing on %HOST%/%USERNAME%: %ERROR%', array('FILENAME' => $filename, 'HOST' => $ftpServer->host, 'USERNAME' => $ftpServer->username, 'ERROR' => strip_tags($exception->getMessage()))));
							SERIA_Base::debug('File upload failed: ' . strip_tags($exception->getMessage()));
							try {
								$file->checkFtpServerRecords();
							} catch (Exception $null) {}
						}
					}
				}
				
				$fileObjects = SERIA_File::createObjects(array_keys($deleteQueue));
				
				SERIA_Base::debug(sizeof($deleteQueue) . ' file(s) to delete from FTP server(s)');
				
				foreach ($deleteQueue as $id => $queueObject) {
					list($filename, $ftpServer) = $queueObject;
					SERIA_Base::debug('Deleting file ' . $filename . ' from FTP server ' . $ftpServer->host);
					
					try {
						$ftpServer->deleteFileFromFileRoot($filename);
						$file = $fileObjects[$id];
						if ($file) {
							$file->deleteFtpServerRecord($ftpServer);
						}
					} catch (Exception $exception) {
						SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, _t('Deleting of file %FILENAME failed on FTP server %HOST%/%USERNAME%', array('FILENAME' => $filename, 'HOST' => $ftpServer->host, 'USERNAME' => $ftpServer->username)));
						SERIA_Base::debug('Unable to delete file: ' . $filename . ': ' . $exception->getMessage());
					}
				}
			}
		}	
	}
	
	function files_maintain_garbageCollector() {
		// Code from old files_maintain:
		/**
		*	Delete garbage files (files that has 0 referrers and was created more than 24 hours ago)
		*/
		$filesToDelete = SERIA_Base::db()->query("SELECT id FROM ".SERIA_PREFIX."_files WHERE updated_at < FROM_UNIXTIME(".(time()-60*60*24).") AND referrers<=0")->fetchAll(PDO::FETCH_NUM);
		SERIA_Base::debug("- ".sizeof($filesToDelete)." files to delete (not in use anymore)");
		
		$ids = array();
		foreach ($filesToDelete as $file_row) {
			$ids[] = $file_row[0];
		}
		
		$filesToDelete = SERIA_File::createObjects($ids);
		
		foreach($filesToDelete as $file)
		{
			// Delete file
			try {
				unlink($file->get('localPath'));
			} catch (Exception $null) {
				SERIA_Base::debug('Unable to delete file from local storage: ' . $file->get('filename'));
				SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::ERROR, _t('File has no referrers, bit could not be deleted from file system: %FILENAME%', array('FILENAME' => $file->get('filename'))));
			}
			
			$file->delete(true);
		}
		
		// Check for ghost files in database.
		$query = 'SELECT id, filename FROM ' . SERIA_PREFIX . '_files';
		$files = SERIA_Base::db()->query($query)->fetchAll(PDO::FETCH_NUM);
		
		$ghostFiles = array();
		foreach ($files as $file) {
			list($file_id, $filename) = $file;
			$localPath = SERIA_UPLOAD_ROOT . '/' . $filename;
			if (!file_exists($localPath)) {
				SERIA_Base::debug('Ghost file found in database: ' . $localPath);
				$ghostFiles[] = $filename;
				SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::ERROR, _t('File has record in database, but not existent in/deleted from local storage: %FILENAME%', array('FILENAME' => $filename)));
			}
		}
	}
	
	function files_maintain_transcoding() {
		if(!class_exists('SERIA_FileTranscodings'))
			return;
		$transcodingQueue = SERIA_FileTranscodings::find_all_by_status(SERIA_FileTranscoder::STATUS_QUEUED)->toKeyArray();
		SERIA_Base::debug(' - ' . sizeof($transcodingQueue) . ' file(s) to transcode');
		
		foreach ($transcodingQueue as $transcoding) {
			$transcoding->status = SERIA_FileTranscoder::STATUS_TRANSCODING;
			$transcoding->save();
			
			try {
				$file = new SERIA_File($transcoding->file_id);
				SERIA_Base::debug(' - Transcoding file ' . $file->get('filename'));
				if (!$file) {
					throw new SERIA_Exception('File not found');
				}
				$transcoder = SERIA_FileTranscoder::getTranscoder($transcoding->transcoder);
				if ($transcoder->transcodeFromMaintain($file, unserialize($transcoding->arguments), $transcoding)) {
					$transcoding->status = SERIA_FileTranscoder::STATUS_COMPLETED;
				} else {
					$transcoding->status = SERIA_FileTranscoder::STATUS_QUEUED;
				}
			} catch (Exception $exception) {
				$transcoding->status = SERIA_FileTranscoder::STATUS_FAILED;
				SERIA_Base::debug('Unable to transcode file: ' . $exception->getMessage());
				$transcoding->message = $exception->getMessage();
			}
			$transcoding->save();
		}
	}
	
	function files_maintain_metareader() {
		$db = SERIA_Base::db();
		
		if (!isset($_GET['disableFileLimit'])) {
			$limit = 500;
		} else {
			$limit = 0;
		}
		
		$metaReadQueue = SERIA_File::getMetaReadQueue($limit);
		SERIA_Base::debug(' - ' . sizeof($metaReadQueue) . ' file(s) to read meta data from');
		if ((sizeof($metaReadQueue) == $limit) && $limit) {
			SERIA_Base::debug(' - There may be more files as query is limited to 500 files');
		}
		$counter = 0;
		$filecount = sizeof($metaReadQueue);
		foreach ($metaReadQueue as &$file) {
			try {
				SERIA_Base::debug('Reading meta data from file ' . ++$counter . ' of ' . $filecount . ' ' . $file->get('filename'));
				$localPath = $file->get('localPath');
				if (!file_exists($localPath)) {
					throw new SERIA_Exception('File does not exist');
				}
				
				$file->readMetaDataFromFile();
				$file = null;
			} catch (Exception $exception) {
				SERIA_Base::debug('Meta read failed: ' . $exception->getMessage());
			}
		}
	}

	function files_maintain() {
		$workQueue = array(
			'files_maintain_ftpSync',
			'files_maintain_garbageCollector',
			'files_maintain_transcoding',
			'files_maintain_metareader'
		);
		
		shuffle($workQueue);
		
		$startTime = time();
		while (($startTime > (time() - (60 * 10))) && (sizeof($workQueue))) {
			$function = array_shift($workQueue);
			$function();
		}
		
		if (sizeof($workQueue) == 0) {
			return 'Ok';
		} else {
			return 'Partial: Not enough time for processing';
		}
	}
?>
