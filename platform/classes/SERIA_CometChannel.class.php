<?php
	class SERIA_CometChannel extends SERIA_ActiveRecord {
		public $tableName = '_comet_channels';
		public $usePrefix = true;
		public $engine;
		
		public $hasMany = array(
			'SERIA_CometSubscriber:Subscribers' => 'channel_id',
			'SERIA_CometMessage:Messages' => 'channel_id'
		);
		
		public function setEngine($engine) {
			$this->engine = $engine;
		}
		
		public function publishMessage($key, $content) {
			if (!$this->id) {
				throw new SERIA_Exception('Cannot publish message to unsaved channel');
			}
			
			$message = new SERIA_CometMessage();
			$message->channel_id = $this->id;
			$message->key = $key;
			$message->message = $content;
			if ($message->save()) {
				$this->engine->onNewMessage($message);
				
				return true;
			}
		}
		
		// This method create a new subscriber record if it does not exist.
		public function createSubscriber($key = null) {
			if (!$this->id) {
				throw new SERIA_Exception('Cannot create subscriber on unsaved channel');
			}
			
			$subscriber = null;
			if ($key) {
				$subscriber = SERIA_CometSubscribers::find_first_by_key($key);
			}
			
			if (!$subscriber) {
				do {
					$key = '_';
					$keyLength = mt_rand(25,50);
					$charset = 'abcdefghijklmnopqrstuvwxyz0123456789';
					for ($i = 0; $i < $keyLength; $i++) {
						$key .= $charset[mt_rand(0, strlen($charset) - 1)];
					}
				} while (SERIA_CometSubscribers::find_all_by_key($key)->count > 0);
				
				if (!$key) {
					throw new SERIA_Exception('Unable to create key');
				}
				
				$subscriber = new SERIA_CometSubscriber();
				$subscriber->channel_id = $this->id;
				$subscriber->key = $key;
				$subscriber->lastupdate = time();
				$subscriber->save();
			}
			return $subscriber->key;
		}
	}
?>