<?php
	abstract class SERIA_UIWidget {
		public $error, $value;
		protected $name, $prefix, $form;
	
		abstract function output();
	
		public function toString() {
			return $this->output();
		}
	}

?>
