<?php
	class SERIA_FileZiptoimagesTranscoder extends SERIA_FileTranscoder {
		public $sync = false;
		public $inputMimetypes = array('application/zip');
		protected $name = 'Ziptoimages';
		
		private $maxFilesize = 100; // in MB, total for all files
		private $maxFilecount = 1000;
		
		protected function _transcode($inputFile, $arguments = array()) {
			if (!function_exists('zip_open')) {
				throw new SERIA_Exception('PHP ZIP Extension is not enabled');
			}
			
			$filePath =  $inputFile->get('localPath');
			if (!file_exists($filePath)) {
				throw new SERIA_Exception('File (' . $filePath . ') not found');
			}
			
			$zip = zip_open($filePath);
			if (!is_resource($zip)) {
				throw new SERIA_Exception('File is not a valid zip file');
			}
			
			$fileCount = 0;
			$fileSize = 0;
			$fileIds = array();
			
			try {
				while ($entry = zip_read($zip)) {
					if (zip_entry_open($zip, $entry)) {
						do {
							$tmpFilename = SERIA_TMP_ROOT . '/ziptranscode_' . time() . '_' . rand(0,1000);
						} while (file_exists($tmpFilename));
						
						if (($fileSize += zip_entry_filesize($entry)) / 1024 / 1024 > $this->maxFilesize) {
							throw new SERIA_Exception('File size limit reached');
						}
						if (++$fileCount > $this->maxFilecount) {
							throw new SERIA_Exception('File count limit reaced');
						}
						
						$name = zip_entry_name($entry);
						
						$outputFile = fopen($tmpFilename, 'w');
						if (!$outputFile) {
							throw new SERIA_Exception('Unable to open output file ' . $tmpFilename);
						}
						while (strlen($data = zip_entry_read($entry, 1024))) {
							fwrite($outputFile, $data);
						}
						fclose($outputFile);
						if (filesize($tmpFilename) > 0) {
							$file = new SERIA_File($tmpFilename, basename($name), false, $inputFile->get('id'), 'imagefromzip');
							$file->increaseReferrers();
						} else {
							SERIA_Base::debug('Zero file size: ' . $tmpFilename . ' from ' . $name);
						}
						
						try {
							unlink($tmpFilename);
						} catch (Exception $null) {}
					}
				}
			} catch (Exception $exception) {
				foreach ($fileIds as $file_id) {
					$file = new SERIA_File($file_id);
					$file->decreaseReferrers();
				}
				throw $exception;
			}
			
			return true;
		}
	}
?>
