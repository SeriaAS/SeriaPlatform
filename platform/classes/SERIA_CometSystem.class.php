<?php
	class SERIA_CometSystem {
		protected $engine = null;
		protected $className;
		
		/**
		 * Constructs a new Comet system using specified comet engine in config file
		 * @return SERIA_CometSystem
		 */
		public function __construct() {
			$engineName = SERIA_COMET_ENGINE;
			$this->className = $className = 'SERIA_Comet' . $engineName . 'Transporter';
			if (!class_exists($className)) {
				throw new SERIA_Exception('Comet engine ' . $engineName . ' is not available');
			}
		}
		
		/**
		 * Returns a new comet channel, or existing channel by key
		 * @param $key string Channel key/id.
		 * @return SERIA_CometChannel
		 */
		public function getChannel($key) {
			$channel = SERIA_CometChannels::find_first_by_key($key);
			if (!$channel) {
				$channel = new SERIA_CometChannel();
				$channel->key = $key;
				$channel->save();
			}
				
			if (mt_rand(1,100) == 1) {
				try {
					self::garbageCollector();
				} catch (Exception $null) {}
			}
			
			$engine = new $this->className($channel->id);
			$channel->setEngine($engine);
			
			return $channel;
		}
		
		/**
		 * Remove old channels and idle subscribers
		 */
		protected static function garbageCollector() {
			$subscribers = SERIA_CometSubscribers::find_all();
			foreach ($subscribers as $subscriber) {
				if ($subscriber->lastupdate < (time() - (3600 * 6))) {
					$subscriber->delete();
				}
			}
		}
		
		
	}
?>