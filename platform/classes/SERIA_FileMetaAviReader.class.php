<?php
	class SERIA_FileMetaAviReader extends SERIA_FileMetaReader {
		public function read() {
			$this->extend('SERIA_FileMetaVideoDurationReader');
		}
	}
?>