<?php
	class SERIA_FileMetaImageReader extends SERIA_FileMetaReader {
		public function read() {
			$file = $this->file;
			$localPath = $file->get('localPath');
			
			list($width, $height) = getimagesize($localPath);
			$file->setMeta('image_width', $width);
			$file->setMeta('image_height', $height); 
		}
	}
?>