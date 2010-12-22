<?php
	class ActiveResourceException extends Exception {
		public $errorCode;
		
		public function __construct($message, $code) {
			parent::__construct($message . ' (' . $code . ')');
			$this->errorCode = $code;
		}
	}
?>