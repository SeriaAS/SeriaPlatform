<?php
	class SERIA_Flv {
		private $filename;
		private $fileHandler;
		public $header;
		private $lastTag;
		
		public function __construct($filename) {
			if ($this->fileHandler = fopen($filename, 'rb')) {
				$this->readHeader();
				
				if ($this->header->signature != 'FLV') {
					throw new SERIA_Exception('Invalid FLV file. No FLV header found.');
				}
			} else {
				throw new SERIA_Exception('Unable to open file for reading');
			}
		}
		
		public function readHeader() {
			$this->header = new SERIA_Flv_Header($this->fileHandler);
		}
		
		public function getNextTag() {
			if (feof($this->fileHandler)) {
				return false;
			}
			
			if ($this->lastTag) {
				$this->lastTag->seekToNextTag();
			}
			
			$tag = SERIA_Flv_Tag::create($this->fileHandler);
			$this->lastTag = $tag;
			return $tag;
		}
	}
?>