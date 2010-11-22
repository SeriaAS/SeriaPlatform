<?php
	class SERIA_SearchMySqlEngine {
		public $phrases;
		
		public function __construct(array $phrases) {
			$this->phrases = $phrases;
		}
		
		public function run() {
			$phraseSearch = array_shift($this->phrases);
			$this->phrases[] = clone $phraseSearch;
			foreach ($this->phrases as $phrase) {
				$phraseSearch->setPhrase($phrase->phrase);
				$phraseSearch->updateQuery();
			}
			
			return $phraseSearch->run();
		}
	}
?>