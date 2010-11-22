<?php
	class SERIA_CometTransporter {
		protected $channel_id;
		
		public function __construct($channel_id) {
			if (!$channel_id) {
				throw new SERIA_Exception('Cannot create comet transporter without a channel ID');
			}
			
			$this->channel_id = $channel_id;
			if (method_exists($this, 'oncreate')) {
				$this->onCreate();
			}
		}
		
		public function onNewMessage($null) {}
		public function getInitParams() {}
		protected function beforeInit() {}
		protected function afterInit() {}
		
		public function init() {
			$this->beforeInit();
			SERIA_Template::jsInclude(SERIA_HTTP_ROOT . '/seria/comet/js/' . $this->jsFile);
			$this->afterInit();
		}
	}
?>