<?php
	class SERIA_FileTranscoder {
		const STATUS_QUEUED = 0;
		const STATUS_TRANSCODING = 1;
		const STATUS_COMPLETED = 2;
		const STATUS_FAILED = 3;
		const STATUS_RESTART = 4;

		public $sync = true;
		public $inputMimetypes = array();
		public $outputFileExtension = '.unknown';
		protected $name = 'Unknown';
		protected $record = null;
		
		protected $inputFile;
		
		public function __construct() {
		}
		
		public function transcodeFromMaintain($file, $arguments, SERIA_FileTranscoding &$record) {
			$this->record =& $record;
			try {
				$newfile = $this->_transcode($file, $arguments);
				/* Quarantine subfiles if the file is q. */
				if ($file->isQuarantined() && $newfile && $newfile !== true)
					$newfile->quarantine();
			} catch (Exception $e) {
				/*
				 * Oops: transcoding has failed. This is bad!
				 */
				throw $e;
			}
			return $newfile;
		}
		
		public function transcode($file, $arguments = array()) {
			if ($this->sync) {
				$newfile = $this->_transcode($file, $arguments);
				/* Quarantine subfiles if the file is q. */
				if ($file->isQuarantined() && $newfile && $newfile !== true)
					$newfile->quarantine();
				return $newfile;
			} else {
				// Add to queue for async transcoding
				
				// Search for existing identical transcoding in queue
				sort($arguments);
				$serializedArguments = serialize($arguments);
				$transcodings = SERIA_FileTranscodings::find_all_by_file_id($file->get('id'), array('criterias' => array('transcoder' => $this->name)));
				foreach ($transcodings as $transcoding) {
					if ($transcoding->arguments == $serializedArguments) {
						$this->record =& $transcoding;
						return true;
					}
				}
				
				// No existing transcoding found, proceed add to queue
				$transcoding = new SERIA_FileTranscoding();
				$transcoding->file_id = $file->get('id');
				$transcoding->transcoder = $this->name;
				$transcoding->status = 0;
				$transcoding->arguments = $serializedArguments;
				if ($transcoding->save()) {
					$this->record =& $transcoding;
					return true;
				}
			}
		}
		public function getStatus($file, $arguments = array())
		{
			if (!$this->record) {
				sort($arguments);
				$serializedArguments = serialize($arguments);
				$transcodings = SERIA_FileTranscodings::find_all_by_file_id($file->get('id'), array('criterias' => array('transcoder' => $this->name)));
				foreach ($transcodings as $transcoding) {
					$this->record =& $transcoding;
					break;
				}
				if (!$this->record)
					throw new SERIA_NotFoundException('The file is not queued for transcoding.');
			}
			return $this->record->status;
		}
		public function setStatus($status, $file, $arguments = array())
		{
			if (!$this->record) {
				sort($arguments);
				$serializedArguments = serialize($arguments);
				$transcodings = SERIA_FileTranscodings::find_all_by_file_id($file->get('id'), array('criterias' => array('transcoder' => $this->name)));
				foreach ($transcodings as $transcoding) {
					$this->record =& $transcoding;
					break;
				}
				if (!$this->record)
					throw new SERIA_NotFoundException('The file is not queued for transcoding.');
			}
			$this->record->status = $status;
			$this->record->save();
		}
		public function reset($file, $arguments = array())
		{
			if (!$this->record) {
				sort($arguments);
				$serializedArguments = serialize($arguments);
				$transcodings = SERIA_FileTranscodings::find_all_by_file_id($file->get('id'), array('criterias' => array('transcoder' => $this->name)));
				foreach ($transcodings as $transcoding) {
					$this->record =& $transcoding;
					break;
				}
				if (!$this->record)
					throw new SERIA_NotFoundException('The file is not queued for transcoding.');
			}
			$this->record->delete();
			$this->record = null;
			return $this->transcode($file, $arguments);
		}
		
		public static function getTranscoder($name) {
			$className = 'SERIA_File' . $name . 'Transcoder';
			if (!class_exists($className)) {
				throw new Exception('Transcoder ' . $name . ' was not found');
			}
			return new $className();
		}
	}
?>