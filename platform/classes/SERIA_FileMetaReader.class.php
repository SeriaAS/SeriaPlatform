<?php
	class SERIA_FileMetaReader {
		protected $file;
		public $fileMetaReader = true;
		
		public function __construct(SERIA_File $file) {
			$this->file = $file;
		}
		
		public function extend($className) {
			$object = new $className($this->file);
			if (!$object->fileMetaReader) {
				return false;
			}
			$object->read();
			
			return true;
		}
		
		public function read() {
			$file = $this->file;
			
			$filename = $file->get('filename');
			$localPath = $file->get('localPath');
			
			$pathInfo = pathinfo($filename);
			
			try {
				$file->setMeta('fileSize', filesize($localPath));
				$file->setMeta('contentType', SERIA_Lib::getContentType($filename));
				$file->setMeta('filename', $filename);
				
				$extension = $pathInfo['extension'];
				$extension = strtolower($extension);
				$extension[0] = strtoupper($extension[0]);
				$className = 'SERIA_FileMeta' . $extension . 'Reader';
				if (class_exists($className)) {
					$this->extend($className);
				}
			} catch (Exception $null) {
				return false;
			}
		}
	}
?>