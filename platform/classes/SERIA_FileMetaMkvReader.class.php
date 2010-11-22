<?php
	class SERIA_FileMetaMkvReader extends SERIA_FileMetaReader {
		public function read() {
			$this->extend('SERIA_FileMetaVideoDurationReader');
		}
	}
?>