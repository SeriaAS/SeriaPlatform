<?php
	class SERIA_Flv_Tag_Video extends SERIA_Flv_tag {
		public $frameType;
		public $bodyPosition;
		
		public $videoCodecId;
		public $videoFrameType;
		
		public static function create() {
			return new SERIA_Flv_Tag_Video();
		}
		
		public function readTagData() {
			$fileHandler = $this->fileHandler;
			$framedata = ord($this->readBody(0, 1));
			
			$this->videoCodecId = $framedata & 0x0f;
			$this->videoFrameType = ($framedata & 0xf0) >> 4;
		}
		
		public function isVideoFrame() {
			return true;
		}
		
		public function isKeyFrame() {
			return ($this->videoFrameType == 1);
		}
	}
?>
