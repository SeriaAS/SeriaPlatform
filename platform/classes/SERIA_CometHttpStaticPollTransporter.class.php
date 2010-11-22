<?php
	class SERIA_CometHttpStaticPollTransporter extends SERIA_CometTransporter {
		protected $jsFile = 'httpStaticPoll.js';
		private $httpRoot;
		private $fileRoot;
		
		protected function onCreate() {
			$this->fileRoot = $fileRoot = SERIA_COMET_HTTPSTATICPOLL_FILEROOT;
			$this->httpRoot = SERIA_COMET_HTTPSTATICPOLL_HTTPROOT;
			$this->lockFile = $fileRoot . '/lock';
			
			if (!is_dir($fileRoot)) {
				if (!mkdir($fileRoot, 0755, true)) {
					throw new SERIA_Exception('Unable to create comet static file root');
				}
			}
		}
		
		public function onNewMessage($message) {
			$this->publishMessage($message);
		}
		
		public function publishMessage($message) {
			$counter = 0;
			while (!$this->setLock()) {
				sleep(0.2);
				if ($counter++ > 50) {
					return false;
				}
			}
			
			$subscribers = SERIA_CometSubscribers::find_all_by_channel_id($this->channel_id, array('include' => array('Channel', 'Channel.Messages')));
			foreach ($subscribers as $subscriber) {
				$file = $this->fileRoot . '/data_' . $subscriber->channel_id . '_' . $subscriber->key;
				
				$data = array();
				foreach ($subscriber->Channel->Messages as $message) {
					if ($message->time >= $subscriber->lastupdate) {
						$data[] = array($message->id, $message->key, $message->message, $message->time);
					}
				}
				
				file_put_contents($file, SERIA_Lib::toJSON($data));
			}
			
			$this->unsetLock();
		}
		
		public function getInitParams() {
			return array(
				'pingUrl' => SERIA_HTTP_ROOT . '/seria/comet/httpStaticPollPing.php',
				'dataUrl' => $this->httpRoot . 'data_' . $this->channel_id . '_{KEY}'
			);
		}
		
		protected function beforeInit() {
			SERIA_ScriptLoader::loadScript('Timer');
		}
		
		private function setLock() {
			if (!file_exists($lockFile = $this->lockFile)) {
				$file = fopen($lockFile, 'w');
				if (flock($file, LOCK_EX)) {
					fwrite($file, time());
				
					fclose($file);
					return true;
				}
				fclose($file);
			} else {
				// Lock file should be deleted if it has idled for more than one minute
				$lockContents = file_get_contents($lockFile);
				if ($lockContents + 60 < time()) {
					$this->unsetLock();
				}
			}
			
			return false;
		}
		
		private function unsetLock() {
			unlink($this->lockFile);
		}
	}
?>