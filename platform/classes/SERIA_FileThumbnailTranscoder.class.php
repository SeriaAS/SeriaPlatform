<?php
	class SERIA_FileThumbnailTranscoder extends SERIA_FileTranscoder {
		public $sync = true;
		public $inputMimetypes = array('image/jpeg', 'image/png', 'image/gif');
		protected $name = 'Thumbnail';
		
		public function _transcode($inputFile, $arguments = array()) {
			
			$width = $arguments['width'];
			$height = $arguments['height'];
			$transfill = (bool) $arguments['transfill'];
			
			if (!$width || !$height) {
				throw new Exception('Width and/or height is not specified');
			}
			
			$paramsKey = '';
			if ($transfill) {
				$paramsKey .= '_transfill';
			}
			
			$filenameKey = $width . 'x' . $height . $paramsKey;
			$thumbnailKey = 'scaled_' . $filenameKey;
			
			// Search for cached cached thumbnail
			$thumbnailCache = new SERIA_Cache('thumbnailInfo');
			if ($thumbnailFromCache = $thumbnailCache->get($inputFile->get('id') . '_' . $thumbnailKey)) {
				return $thumbnailFromCache;
			}
			
			// No cached cached thumbnail, search for cached thumbnail
			$relatedFiles = $inputFile->getRelatedFiles();
			foreach ($relatedFiles as $file) {
				if ($file->relation == $thumbnailKey) {
					$thumbnailCache->set($inputFile->get('id') . '_' . $thumbnailKey, $thumbnail, 1800);
					return $file;
				}
			}
			$relatedFiles = null;
			
			$filename = $inputFile->get('localPath');
			
			if ($inputFile->isImage()) {
				list($imagewidth, $imageheight, $type) = getimagesize($filename);
				switch ($type) {
					case IMAGETYPE_GIF:
						$sourceImage = imagecreatefromgif($filename);
						$extension = 'gif';
						break;
					case IMAGETYPE_JPEG:
						$sourceImage = imagecreatefromjpeg($filename);
						$extension = 'jpg';
						break;
					case IMAGETYPE_PNG:
						$sourceImage = imagecreatefrompng($filename);
						$extension = 'png';
						break;
					default:
						throw new SERIA_Exception('Cannot create thumbnail: File is not an image: ' . $type);
						break;
				}
			} elseif ($inputFile->isVideo()) {
				$extension = 'jpg';
			}

			$pi = pathinfo($inputFile->get("filename"));
			$thumbnailFilename = 'scaled_' . $pi["filename"] . $filenameKey . '.' . $extension;
			
			$result = false;
			$localPath = SERIA_UPLOAD_ROOT . '/' . $thumbnailFilename;
			
			if ($inputFile->isImage()) {
				$ratio = 1;
				if ($imagewidth > $width) {
					$ratio = $imagewidth / $width;
				}
				if ($height < ($imageheight / $ratio)) {
					$ratio = $imageheight / $height;
				}
				
				$newwidth = floor($imagewidth / $ratio);
				$newheight = floor($imageheight / $ratio);
				
				$newImage = imagecreatetruecolor($newwidth, $newheight);
				
				imagealphablending($newImage, false);
				imagesavealpha($newImage, true);
				
				imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newwidth, $newheight, $imagewidth, $imageheight);
				imagedestroy($sourceImage);
				
				switch ($extension) {
					case 'jpg':
						$result = imagejpeg($newImage, $localPath);
						break;
					case 'gif':
						$result = imagegif($newImage, $localPath);
						break;
					default:
					case 'png':
						$result = imagepng($newImage, $localPath);
						break;
				}
				imagedestroy($newImage);
			} elseif ($inputFile->isVideo()) {
				$ffmpeg = new SERIA_Ffmpeg();
				if ($ffmpeg->isAvailable) {
					$result = $ffmpeg->createThumbnail($inputFile->get('localPath'), $localPath, $width, $height, 'jpeg', $transfill);
				}
			}
						
			if ($result && file_exists($localPath)) {
				$thumbnail = new SERIA_File($localPath, false, false, $inputFile->get('id'), $thumbnailKey);
				$thumbnail->setMeta('image_width', $newwidth);
				$thumbnail->setMeta('image_height', $newheight);
				$thumbnail->increaseReferrers();
				return $thumbnail;
			}
			
		}
	}
?>