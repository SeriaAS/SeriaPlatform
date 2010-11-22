<?php
	class SERIA_Flv_Tag {
		public $position = 0;
		public $fileHandler;
		public $tagLength;
		
		public $bodyType; // uint8
		public $bodyLength; // uint24
		public $timestampExtended; // uint32
		public $timestamp; // uint24
		public $streamId; // uint24: 0
		public $body; // Byte[bodyLength]
		
		public $bodyPosition;
		
		public static function create($arg1, $arg2 = null) {
			$fileHandler = $arg1;
			$position = $arg2;
    	
    	$tag = new SERIA_Flv_Tag();
			$tag->fileHandler = $fileHandler;
			
			// Use position argument if specified, or use file position if not specified
			if ($position == null) {
				$position = ftell($fileHandler);
			}
			$tag->position = $position;
			$tag->fileHandler = $fileHandler;
			
			if (!$tag->readHeading()) {
				return false;
			}
			$tag->tagLength = $tag->bodyLength + 11;
			
			
			switch ($tag->bodyType) {
				case 0x12:
					// META
					break;
				case 0x09:
					// VIDEO
					$oldTag = clone $tag;
          $tag = SERIA_Flv_Tag_Video::create();
          $tag->setProperties($oldTag);
					break;
				case 0x08:
					// AUDIO
					$oldTag = clone $tag;
          $tag = SERIA_Flv_Tag_Audio::create();
          $tag->setProperties($oldTag);
					break;
			}
			
			$tag->readTagData();
      return $tag;
		}
		
		public function seekToStart() {
			fseek($this->fileHandler, $this->position);
		}
		
		public function setProperties($tag) {
			$vars = get_object_vars($tag);
			foreach ($vars as $var => $value) {
				$this->$var = $value;
			}
		}
		
		public function readHeading() {
			try {
				$this->seekToStart();
				$fileHandler = $this->fileHandler;
				
				$packet = fread($fileHandler, 11);
				
				if (strlen($packet) != 11) {
					return false;
				}
				
				$this->bodyType = ord($packet[0]);
				
				$this->bodyLength = SERIA_Binary::decodeUint24(substr($packet, 1, 3));
				
				$this->timestamp = SERIA_Binary::decodeUint24(substr($packet, 4, 3));
				$this->timestampExtended = ord($packet[7]);
				$this->streamId = SERIA_Binary::decodeUint24(substr($packet, 8, 3));
				
				$this->bodyPosition = $this->position + 11;
				
				return true;
			} catch (Exception $exception) {
				return false;
			}
		}
		
		public function seekToNextTag() {
			fseek($this->fileHandler, $this->position + $this->bodyLength + 11 + 4);
		}
		
		// Returns body
		public function readBody($offset = null, $length = null) {
			if (!$offset) {
				$offset = 0;
			}
			
			if ($length > $this->bodyLength) {
				$length = $this->bodyLength;
			}
			if ($length === null) {
				$length = $this->bodyLength;
			}
			
			$offset += $this->bodyPosition;
			
			fseek($this->fileHandler, $offset);
			$data = fread($this->fileHandler, $length);
			return $data;
		}
		
		// Returns raw tag
		public function readRawTag() {
			$this->seekToStart();
			
			return fread($this->fileHandler, $this->tagLength);
		}
		
		public function readTagData() {
		}
		
		public function isVideoFrame() {
			return false;
		}
		
		public function isAudioFrame() {
			return false;
		}
	}
?>