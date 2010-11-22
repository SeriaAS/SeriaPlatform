<?php
	class SERIA_FileMetaWmvReader extends SERIA_FileMetaReader {
		public function read() {
			$this->extend('SERIA_FileMetaVideoDurationReader');
		}
	}
?>