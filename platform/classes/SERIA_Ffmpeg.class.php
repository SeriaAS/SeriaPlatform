<?php
	class SERIA_Ffmpeg {
		public $isAvailable = false;
		
		public function __construct() {
			$path = '';
			if (defined('SERIA_FFMPEG_PATH')) {
				$path = SERIA_FFMPEG_PATH;
			}
			
			if (!$path || !file_exists($path)) {
				$this->isAvailable = false;
			} else {
				$this->isAvailable = true;
			}
			$this->path = $path;
		}
		
		public function run($arguments) {
			if (!$this->isAvailable) {
				throw new Exception('FFMpeg is not available. Check SERIA_FFMPEG_PATH');
			}
			
			if (is_array($arguments)) {
				$arguments = implode(' ', $arguments);
			}
			
			$command = $this->path . ' ' . $arguments;
			$result = shell_exec($command);
			return $result;
		}
		
		public function getDurationFloat($source) {
			$arguments = array(
				'-i', escapeshellarg($source),
				'2>&1'
			);
			$data = $this->run($arguments);
			
			$data = explode("\n", $data);
			foreach ($data as $line) {
				if (strpos(trim($line), 'Duration: ') === 0) {
					$startSeconds = 0;
//TODO: Find out how this should be parsed
// what does the 'duration' part, and what does the 'start' part of the output from ffmpeg mean (slem)?
// In the most of the cases the output f.ex look like this: duration: 00:00:15.9, start: 0.000000 (The length of the movie is like it says: 15.9 seconds)
// In one case I have seen an output like this: duration: 00:00:12.5, start: 2.3 (The length of the movie in this case was 14.8 seconds)
// In one case I have seen an output like this: duration: 00:00:19.3, start: 4.1 (The length of the movie in this case was 19.3 seconds)
/*
					if ($startPos = strpos($line, 'start: ')) {
						$endPos = strpos($line, ', ');
						$startPos += 7;
						$startSeconds = substr($line, $startPos, ($endPos - $startPos)); 
					} else {
						$startSeconds = 0;
					}
*/
					// Remove Duration: from start of string
					list($null, $line) = explode(': ', $line, 2);
					// Remove all data after duration string
					list($line) = explode(',', $line);
					if (sizeof($parts = explode(':', trim($line))) == 3) {
						list($hours, $minutes, $seconds) = $parts;
						$hours = (int) $hours;
						$minutes = (int) $minutes;

						
						list($seconds, $desiseconds) = explode('.', $seconds);
						
						$desiseconds = $desiseconds[0];
						$seconds = $seconds + ($desiseconds / 10);
						$seconds += ($minutes * 60) + ($hours * 3600) + $startSeconds;
						return $seconds;
					} else {
						SERIA_Base::debug('Expected time from ffmpeg, got ' . htmlspecialchars($line));
					}
				}
			}
		}
		
		public function getDuration($source) {
			return floor($this->getDurationFloat($source));
		}
		
		public function convertToFlv($source, $destination) {
			$arguments = array(
				'-i', escapeshellarg($source),
				'-f flv',
				'-ar 22050', // Sample rate
				'-ac 2', // Force stereo
				escapeshellarg($destination)
			);
			
			$this->run($arguments);
			
			if (file_exists($destination)) {
				if (filesize($destination) == 0) {
					unlink($destination);
				} else {
					return true;
				}
			}
			
			return false;
		}
		
		public function createThumbnail($inputFilename, $outputFilename, $width, $height, $format = 'jpeg', $transfill = false) {
			return false;
			
			$arguments = array(
				'-i', escapeshellarg($inputFilename),
				'-itsoffset', 4,
				'-vcodec', 'm' . $format,
				'-vframes', 1,
				'-s', $width . 'x', $height,
				'-f', 'rawvideo',
				escapeshellarg($outputFilename),
				'2>&1'
			);
			
			$this->run($arguments);
			
			return true;
		}
	}
?>
