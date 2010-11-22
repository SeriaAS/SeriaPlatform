<?php
	class SERIA_FileMetaGifReader extends SERIA_FileMetaReader {
		public function read() {
			$this->extend('SERIA_FileMetaImageReader');
		}
	}
?>