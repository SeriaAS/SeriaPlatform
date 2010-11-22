<?php
	class SERIA_SearchEngine {
		private $phrases;
		
		public function __construct() {
			
		}
		
		public function addPhrase($phrase) {
			$this->phrases[] = $phrase;
		}
		
		public function run() {
			$mysqlEngine = new SERIA_SearchMySqlEngine($this->phrases);
			
			return $mysqlEngine->run();
		}
	}
?>