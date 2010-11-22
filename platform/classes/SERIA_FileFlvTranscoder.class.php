<?php
	class SERIA_FileFlvTranscoder extends SERIA_FileTranscoder {
		public $sync = false;
		public $inputMimetypes = array('video/*');
		protected $name = 'Flv';
		
		protected function _transcode($inputFile, $arguments = array()) {
			$filePath =  $inputFile->get('localPath');
			if (!file_exists($filePath)) {
				throw new SERIA_Exception('File (' . $filePath . ') not found');
			}
			
			$outputFilename = $inputFile->get('filename') . '.flv';
			do {
				$outputTmpFilename = SERIA_UPLOAD_ROOT . '/transcode_' . time() . rand(0,10000);
			} while (file_exists($outputTmpFilename));
			
			$outputFile = false;
			$ffmpeg = new SERIA_Ffmpeg();
			if ($ffmpeg->convertToFlv($filePath, $outputTmpFilename)) {
				
				if (!file_exists($outputTmpFilename)) {
					throw new Exception('FFMpeg failed');
				}
				
				$outputFile = new SERIA_File($outputTmpFilename, $outputFilename, false, $inputFile->get('id'), 'transcoded_flv');
			}
			
			try {
				unlink($outputTmpFilename);
			} catch (Exception $null) {}
			
			if ($outputFile) {
				$outputFile->increaseReferrers();
				return $outputFile;
			}
		}
	}
?>
