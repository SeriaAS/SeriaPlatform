<?php
	class SERIA_FileMetaPngReader extends SERIA_FileMetaReader {
		public function read() {
			$this->extend('SERIA_FileMetaImageReader');
		}
	}
?>