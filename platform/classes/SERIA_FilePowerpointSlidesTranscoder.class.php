<?php
	class SERIA_FilePowerpointSlidesTranscoder extends SERIA_FileTranscoder
	{
		protected static $rpcServiceName = 'MSPowerpointSlides';
		public $sync = false;
		public $inputMimetypes = array('application/*powerpoint*');
		protected $name = 'PowerpointSlides';
		
		protected function _transcode($inputFile, $arguments = array())
		{
			if ($this->sync)
				throw new SERIA_Exception('Powerpoint transcoder can not do synchronous transcoding!');
			$filePath =  $inputFile->get('localPath');
			if (!file_exists($filePath)) {
				throw new SERIA_Exception('File (' . $filePath . ') not found');
			}

			$extension = pathinfo($filePath, PATHINFO_EXTENSION);

			if(!in_array($extension, array('ppt', 'pptx'))) {
				throw new SERIA_Exception('File (' . $filePath . ') does not have a valid powerpoint extension (.ppt or .pptx)');
			}

			try {
				$data = unserialize($this->record->data);
				if ($data === false) {
					$data = array();
					$powerpoint_bin = new SERIA_RPCFileTransfer(self::$rpcServiceName);
					$data['remoteFileId'] = $powerpoint_bin->uploadFile($inputFile);
					$rpc = SERIA_RPCClient::connect(self::$rpcServiceName, 'SERIA_MSPowerPointConverter');
					$rpc->loadFramework('powerpoint');
					$rpc->startConvertToFiles($data['remoteFileId'], 'png');
					$totalSlides = $powerpoint_bin->getMeta($data['remoteFileId'], 'powerpoint_num_slides');
					$convSlides = $powerpoint_bin->getMeta($data['remoteFileId'], 'powerpoint_latest_converted_slide');
					$data['started'] = time();
					$data['percentCompleted'] = floor(($convSlides * 100) / $totalSlides);
					$data['numberOfSlides'] = $totalSlides;
				} else if (is_array($data) && isset($data['remoteFileId'])) {
					$powerpoint_bin = new SERIA_RPCFileTransfer(self::$rpcServiceName);
					$totalSlides = $powerpoint_bin->getMeta($data['remoteFileId'], 'powerpoint_num_slides');
					$convSlides = $powerpoint_bin->getMeta($data['remoteFileId'], 'powerpoint_latest_converted_slide');
					$exc = $powerpoint_bin->getMeta($data['remoteFileId'], 'powerpoint_exception');
					if ($exc)
						throw new SERIA_Exception('Transcoding failed with a remote exception: '.$exc);
					$cstatus = $powerpoint_bin->getMeta($data['remoteFileId'], 'powerpoint_converted');
					$dstatus = $powerpoint_bin->getMeta($data['remoteFileId'], 'powerpoint_downloaded_slides');
					$data['numberOfSlides'] = $totalSlides;
					if (!$cstatus) {
						if ($convSlides && $totalSlides)
							$data['percentCompleted'] = floor(($convSlides * 100) / $totalSlides);
						else
							$data['percentCompleted'] = 0;
					} else {
						/*
						 * Convertion complete, download the files:
						 */
						if ($dstatus)
							return true; /* Already done */

						$dcount = 0;
						$fileids = $powerpoint_bin->getRelatedFiles($data['remoteFileId'], 'ms_powerpoint_slide');
						SERIA_Base::debug('Got download ids from remote converter: '.implode(', ', $fileids));
						foreach ($fileids as $id) {
							$dlfile = $powerpoint_bin->downloadFile($id);
							SERIA_Base::debug('Downloaded to id '.$dlfile->get('id').' with name '.$dlfile->get('filename'));
							/* Attach to parent */
							$dlfile->setFileRelation($inputFile, 'slidefrompresentation');
							$inputFile->setMeta('powerpoint_downloaded_from_remote', $dcount++);
						}
						$powerpoint_bin->setMeta($data['remoteFileId'], 'powerpoint_downloaded_slides', true);
						$inputFile->setMeta('powerpoint_transcode_download_complete', true);
						return true;
					}
				}
				$this->record->data = serialize($data);
				$this->record->save();
			} catch (SERIA_AccessDeniedException $e) {
				SERIA_Base::debug(_t('Powerpoint slide transcoder stumbled upon an access denied exception.'));
			}
			return false;
		}
	}
?>
