<?php
	// This class receives a data block of text, and returns all words the text contains, in order of presence

	class SERIA_Search_Wordprocessor {
		protected $data;
		
		public function __construct($data) {
			$this->data = $data;
		}
		
		public static function processString($text) {
			$text = mb_strtolower($text, 'utf8');
			$text = str_replace(array('Æ', 'Ø', 'Å'), array('æ', 'ø', 'å'), $text);
			$text = preg_replace('/[^a-z0-9æøå _]/', ' ', $text);
			
			return $text;
		}
		
		public function getWords() {
			$text = $this->data;
			$words = array();
			
			$text = self::processString($text);
			
			$words = explode(' ', $text);
			
			foreach ($words as $word) {
				if ($word = trim($word, "\s")) {
					$words[] = $word;
				}
			}
			
			foreach ($words as $id => $word) {
				$word = trim($word, "\n\t\s\r-_.");
				if (!$word) {
					unset($words[$id]);
				} else {
					$words[$id] = $word;
				}
			}
			
			return $words;
		}
	}
?>