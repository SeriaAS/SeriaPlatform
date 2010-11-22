<?php
	class SERIA_FileMetaMpegReader extends SERIA_FileMetaReader {
		public function read() {
			$this->extend('SERIA_FileMetaVideoDurationReader');
		}
	}
?>