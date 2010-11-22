<?php
	class SERIA_Block extends SERIA_Widget {
		public $isBlock = true;
		
		protected function initialize() {
			$this->attachedEvents['DELETE'] = 'onDelete';
		}
		
		public function onDelete() {
			$this->delete();
		}
	}
?>