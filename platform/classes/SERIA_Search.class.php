<?php
	class SERIA_Search extends SERIA_SearchQueryParser {
		protected $query;
		protected $table;
		
		public function __construct($query, $table) {
			$this->table = $table;
			$this->querystring = $query;
			
			$this->parseQuery();
		}
		
/*		protected function searchOffsetPosition($position) {
			$offsetCache = new SERIA_Cache('Search_offset_' . md5($this->querystring));
			
			
		}
		
		protected function getOffsetPositionObject($position) {
			$offsetCache = new SERIA_Cache('Search_offset_' . md5($this->querystring));
			$objectPosition = floor($position / 100);
			$cacheObject = $offsetCache->get($objectPosition);
			
			if (!is_array($cacheObject)) {
				return array();
			}
			
			return $cacheObject;
		}
		
		protected function saveOffsetPositionObject($position, $object) {
			$offsetCache = new SERIA_Cache('Search_offset_' . md5($this->querystring));
			$objectPosition = floor($position / 100);
			
			$offsetCache->set($objectPosition, $object);
		}
		
		protected function saveOffsetPosition($position, $offset) {
			$offsetCache = new SERIA_Cache('Search_offset_' . md5($this->querystring));
			
			$cacheObject = $this->getOffsetPositionObject($position);
			$cacheObject[$position] = $offset;
			$this->saveOffsetPositionObject($position, $cacheObject);
		} */
		
		public function run($limit = 0, $offset = 0) {
			$result2 = array();
			$finalResult = array();
			
			/*
			$qlimit = 0;
			$passNum = 0;
			
			$offsetCache = new SERIA_Cache('Search_offset_' . md5($this->querystring));
			
			// Search for a cached result position to optimize LIMIT/OFFSET within +/- 100 rows
			if ($offset > 0) {
				$cacheObject = $this->getOffsetPositionObject($positionToSearchFor);
				ksort($offsetObject);
				$nearestStartPosition = 0;
				foreach ($cacheObject as $positionElement => $offsetElement) {
					if ($positionElement <= $offset) {
						$nearestStartPosition = $offsetElement;
					}
				}
			} else {
				$nearestStartPosition = 0;
			}
			$elementsToRemoveFromStart = $offset - $nearestStartPosition;
			
			$positionToSearchFor = $limit + $offset;
			$cacheObject = $this->getOffsetPositionObject($positionToSearchFor);
			foreach ($cacheObject as $positionElement => $offsetElement) {
				if ($positionElement >= $positionToSearchFor) {
					$qlimit = $offsetElement - $offset;
				}
			}
			
			if ($qlimit == 0) {
				$qlimit = $limit;
				SERIA_base::debug('Warning: Using nonindexed limit');
			}
			
			SERIA_Base::debug('Will start searching at position ' . $nearestStartPosition . ' and retreive ' . $qlimit . ' elements on first pass');
			
			
			do {
				$passNum++;
				$lastResultCount = 0;
				foreach ($this->queryphrases as $phrase) {
					$phraseSearch = new SERIA_SearchPhraseQuery($phrase, $this->table, $qlimit, $offset);
					if (!is_array($result[$phrase])) {
						$result[$phrase] = array();
					}
					
					$partialResult = $phraseSearch->run();
					$lastResultCount += sizeof($partialResult);
					$result[$phrase] = array_merge($result[$phrase], $partialResult);
				}
				
				// All results in $result must be checked to each other.
				// Only articles appearing in all search results is being
				// returned.
				if (sizeof($result) > 1) {
					$result2 = call_user_func_array('array_intersect', $result);
				} else {
					$result2 = array_shift($result);
				}
				$finalResult = array_merge($finalResult, $result2);
				$finalResult = array_unique($finalResult);
				$offset += $qlimit;
				
				if ($limit) {
					$remainingElements = $limit - sizeof($finalResult);
					if ($remainingElements > 0) {
						$qlimit = ceil((sizeof($result2) * $remainingElements));
					} else {
						$qlimit = $qlimit * $qlimit;
					}
				}
				
				// Cache position
				SERIA_Base::debug('Saving result position for ' . sizeof($finalResult) . ': '  . $offset);
				$this->saveOffsetPosition(sizeof($finalResult), $offset);
								
			} while (($limit > 0) && (sizeof($finalResult) < $limit) && ($lastResultCount > 0));
			
			if (sizeof($finalResult) > $limit) {
				$finalResult = array_slice($finalResult, 0, $limit);
			} */
			
			$searchEngine = new SERIA_SearchEngine();
			
			foreach ($this->queryphrases as $phrase) {
				$searchEngine->addPhrase(new SERIA_SearchPhraseQuery($phrase, $this->table, $limit, $offset));
			}
			
			$finalResult = $searchEngine->run();
			
			return $finalResult;
		}
	}
?>
