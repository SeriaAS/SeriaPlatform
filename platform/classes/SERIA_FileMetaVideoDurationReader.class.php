<?php
	class SERIA_FileMetaVideoDurationReader extends SERIA_FileMetaReader {
		public function read() {
			$file = $this->file;
			
			$filename = $file->get('localPath');
			
			$ffmpeg = new SERIA_Ffmpeg();
			if ($ffmpeg->isAvailable) {
				$lengthFloat = $ffmpeg->getDurationFloat($filename);
				$length = floor($lengthFloat);
				$lengthExtended = floor(($lengthFloat - $length) * 10);
				$file->setMeta('video_length', $length);
				$file->setmeta('video_lengthExtended', $lengthExtended);
			} else {
				SERIA_Base::debug('Cannot read meta data from file ' . $filename . ': FFMpeg is not available');
			}
			
			return false;
		}
	}
?>