<?php
	class SERIA_FileMetaMpgReader extends SERIA_FileMetaReader {
		public function read() {
			$this->extend('SERIA_FileMetaVideoDurationReader');
		}
	}
?>