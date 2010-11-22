<?php
	class SERIA_SearchQueryParser {
		protected $querystring;
		protected $querywords;
		protected $queryphrases;
		
		protected function parseQuery() {
			$this->querywords = array();
			$this->queryphrases = array();
			
			$querystring = $this->querystring;
			
			// Parse phrases inside " " from query string
			$offset = 0;
			while ($offset < strlen($querystring)) {
				
				// Find next "
				$startPos = strpos($querystring, '"', $offset);
				$end = false;
				if ($startPos === false) {
					// End of query string reached. No phrases found
					$startPos = strlen($querystring);
					$end = true;
				}
				
				// Split all words before found " or end
				$this->splitWords(substr($querystring, $offset, $startPos));
				
				// If not at end of query, search for ending "
				if (!$end) {
					$endingPos = strpos($querystring, '"', $startPos + 1);
					// if no " found, threat end of query as ending
					if ($endingPos === false) {
						$endingPos = strlen($querystring);
					}
					 
					$this->queryphrases[] = substr($querystring, $startPos + 1, $endingPos - $startPos - 1);
				}
				
				if ($end) {
					break;
				}
				
				$offset = $endingPos + 1;
			}
		}
		
		// This will return all words in the string, in order
		protected function getWords($string) {
			$string = strtolower($string);
			
			$words = explode(' ', $string);
			
			// Remove empty words
			$words2 = array();
			foreach ($words as $word) {
				if ($word = trim($word)) {
					$words2[] = $word;
				}
			}
			
			return $words2;
		}
		
		private function splitWords($string) {
			$words = $this->getWords($string);
			
			// Words is added to query phrases, as the same search
			// method is used for both single words and phrases with
			// multiple words.
			foreach ($words as $word) {
				if (!in_array($word, $this->queryphrases)) {
					$this->queryphrases[] = $word;
				}
			}
		}
	}
?>
