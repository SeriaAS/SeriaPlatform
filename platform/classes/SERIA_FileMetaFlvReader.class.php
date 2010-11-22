<?php
	class SERIA_FileMetaFlvReader extends SERIA_FileMetaReader {
		public function read() {
			$file = $this->file;
			
			$flv = new SERIA_Flv($file->get('localPath'));
			
			while ((get_class($videoTag = $flv->getNextTag()) != 'SERIA_Flv_Tag_Video') && $videoTag) {
			}
			while ((get_class($audioTag = $flv->getNextTag()) != 'SERIA_Flv_Tag_Audio') && $audioTag) {
			}
			
			if ($videoTag) {
				$videoCodec = '';
				switch ($videoTag->videoCodecId) {
					case 1:
						$videoCodec = 'JPEG';
						break;
					case 2:
						$videoCodec = 'Sorenson_H.263';
						break;
					case 3:
						$videoCodec = 'Screen_video';
						break;
					case 4:
						$videoCodec = 'On2_VP6';
						break;
					case 5:
						$videoCodec = 'On2_VP6_Alpha';
						break;
					case 6:
						$videoCodec = 'Screen_video_2';
						break;
					case 7:
						$videoCodec = 'AVC';
						break;
				}
				$file->setMeta('video_flv_video_codec', $videoCodec);
			}
			
			if ($audioTag) {
				switch ($audioTag->soundFormat) {
					case 0:
						$audioCodec = 'Linear_PCM_PE';
						break;
					case 1:
						$audioCodec = 'ADPCM';
						break;
					case 2:
						$audioCodec = 'MP3';
						break;
					case 3:
						$audioCodec = 'Linear_PCM_LE';
						break;
					case 4:
						$audioCodec = 'Nellymoser_16khz_mono';
						break;
					case 5:
						$audioCodec = 'Nellymoser_8khz_mono';
						break;
					case 6:
						$audioCodec = 'Nellymoser';
						break;
					case 7:
						$audioCodec = 'G.711_A-law_logarithmic_pcm';
						break;
					case 8:
						$audioCodec = 'G.711_mu-law_logarithmic_pcm';
						break;
					case 9:
						$audioCodec = 'reserved';
						break;
					case 10:
						$audioCodec = 'AAC';
						break;
					case 11:
						$audioCodec = 'Speex';
						break;
					case 14:
						$audioCodec = 'MP3_8khz';
						break;
					case 15:
						$audioCodec = 'DSP';
						break;
				}
				
				$soundRate = $audioTag->soundSampleRate;
				
				$soundSize = $audioTag->soundSize;
				
				$file->setMeta('video_flv_audio_codec', $audioCodec);
				$file->setMeta('video_flv_audio_samplerate', $soundRate);
				$file->setMeta('video_flv_audio_size', $soundSize);
			}
			
			$fp = fopen($file->get('localPath'), 'r');
			if ($fp) {
				if (fseek($fp, 0, SEEK_END) == 0) {
					$length = ftell($fp);
					if ($length !== false) {
						if (fseek($fp, -4, SEEK_END) == 0) {
							$value = fread($fp,4);
							if ($value !== false) {
								$taglen = hexdec(bin2hex($value));
								if ($length > $taglen) {
									if (fseek($fp, $length - $taglen, SEEK_SET) == 0) {
										$value = fread($fp, 3);
										if ($value !== false) {
											$lengthMs = round(hexdec(bin2hex($value)));
											$lengthSeconds = floor($lengthMs / 1000);
											$lengthExtended = round(($lengthMs - ($lengthSeconds * 1000)) / 100, 0);
											$file->setMeta('video_length', $lengthSeconds);
											$file->setMeta('video_lengthExtended', $lengthExtended);
											$file->setMeta('video_lengthExtendedMs', floor($lengthMs - ($lengthSeconds * 1000)));
										}
									}
								}
							}
						}
					}
				}
				fclose($fp);
			}
		}
	}
?>