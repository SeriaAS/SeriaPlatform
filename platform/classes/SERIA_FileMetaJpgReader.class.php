<?php
	class SERIA_FileMetaJpgReader extends SERIA_FileMetaReader {
		public function read() {
			$this->extend('SERIA_FileMetaImageReader');
			
			if (!defined('SERIA_DISABLE_EXIF_READ')) {
				if (function_exists('exif_read_data')) {
					try {
						$data = exif_read_data($this->file->get('localPath'));
						foreach ($data as $sectionName => $section) {
							if (is_string($section)) {
								$this->file->setMeta('image_exif_' . strtolower($sectionName), $section);
							} elseif (is_array($section)) {
								foreach ($section as $key => $value) {
									$this->file->setMeta('image_exif_' . strtolower($key) . '.' . strtolower($sectionName), $value);
								}
							}
						}
					} catch (Exception $exception) {
						SERIA_Base::debug('Exif read failed: ' . $exception->getMessage());
					}
				}
			}
		}
	}
?>