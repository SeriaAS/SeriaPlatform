<?php
	class SERIA_Flv_Tag_Audio extends SERIA_Flv_Tag {
		public $soundStereo = false;
		public $soundSize;
		public $soundSampleRate;
		public $soundFormat;
		
		public static function create() {
			return new SERIA_Flv_Tag_Audio();
		}
		
		public function readTagData() {
			$dataframe = ord($this->readBody(0, 1));
			
			$this->soundStereo = ($dataframe & 0x01);
			
			$soundSize = ($dataframe & 0x02) >> 1;
			if ($soundSize == 0) {
				$this->soundSize = 8;
			} else {
				$this->soundSize = 16;
			}
			
			switch (($dataframe & 0x0c) >> 2) {
				case 0:
					$this->soundSampleRate = 5500;
					break;
				case 1:
					$this->soundSampleRate = 11000;
					break;
				case 2:
					$this->soundSampleRate = 22000;
					break;
				case 3:
					$this->soundSampleRate = 44000;
					break;
			}
			
			$this->soundFormat = ($dataframe & 0xf0) >> 4;
		}
		
		public function isAudioFrame() {
			return true;
		}
	}
?>
