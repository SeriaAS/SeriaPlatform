<?php
	class SERIA_FileMetaJpegReader extends SERIA_FileMetaReader {
		public function read() {
			$this->extend('SERIA_FileMetaJpgReader');
		}
	}
?>