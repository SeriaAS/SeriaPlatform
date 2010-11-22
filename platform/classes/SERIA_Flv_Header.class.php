<?php
	class SERIA_Flv_Header {
		public $fileHandler;
		
		public $signature; // byte[3]: FLA
		public $version; // int8: 1 for known
		public $flags; // int 8 bitmask: 4 => audio, 1 => video
		
		public $audio = false;
		public $video = false;
		
		public $length; // unsigned int32
		
		public function read() {
			$fileHandler = $this->fileHandler;
			
			fseek($fileHandler, 0);
			$this->signature = fread($fileHandler, 3);
			$this->version = ord(fread($fileHandler, 1));
			$this->flags = ord(fread($fileHandler, 1));
			$this->length = (int) SERIA_Binary::decodeUint32(fread($fileHandler, 4));
			
			switch ($this->flags) {
				case 1:
					$this->video = true;
					break;
				case 4:
					$this->audio = true;
					break;
				case 5:
					$this->audio = true;
					$this->video = true;
					break;
			}
			
			// Seek past previous tag length
			fread($fileHandler, 4);
		}
		
		public function validate() {
			if ($this->signature != 'FLV') {
				return -1;
			} elseif ($this->version != 1) {
				return -2;
			} elseif ($this->flags == 0) {
				return -3;
			} elseif ($this->length != 9) {
				return -4;
			}
			
			return 1;
		}
		
		public function __construct($fileHandler) {
			$this->fileHandler = $fileHandler;
			$this->read();
			$result = $this->validate();
			
			if ($result < 1) {
				throw new Exception('Unknown format. Error code ' . $result);
			}
		}
	}
?>