<?php

class SERIA_RateArticleWidget extends SERIA_Widget
{
		protected $attachedEvents = array('DELETE' => 'onDelete');

		public function initialize() {
		}

		protected function onDelete($source) {
			$this->delete();
		}
		protected function onPublish($source) {
		}
		
		public function action_index() {
		}
		public function action_link() {
		}
}

?>