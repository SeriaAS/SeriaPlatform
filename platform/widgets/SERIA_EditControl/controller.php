<?php

class SERIA_EditControlWidget extends SERIA_Widget
{
		protected $attachedEvents = array('DELETE' => 'onDelete');

		protected function onDelete($source) {
			$this->delete();
		}

		public function getGUID()
		{
			return $this->guid;
		}

		public function action_index() {
		}
		public function action_link() {
		}
}

?>