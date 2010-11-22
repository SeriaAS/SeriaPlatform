<?php
	class SERIA_SearchPhraseQuery extends SERIA_SearchQueryParser {
		public $phrase;
		private $words;
		protected $table;
		protected $limit;
		protected $offset;
		
		public $query;
		public $where;
		public $tableAliasOffset = 0;
		private $lastTableAlias;
		
		protected $searchConfig;
		
		public function __construct($phrase, $table, $limit = 0, $offset = 0) {
			$this->phrase = $phrase;
			$this->table = $table;
			$this->limit = $limit;
			$this->offset = $offset;
			
			$searchConfig = SERIA_SearchIndexConfigs::find_first_by_searchtablename($this->table, array('include' => array('Columns')));
			if (!$searchConfig) {
				throw new SERIA_Exception('Search table config not found');
			}
			$this->searchConfig = $searchConfig;
		}
		
		public function setPhrase($phrase) {
			$this->phrase = $phrase;
		}
		
		public function createQueryForWord($word_id, $index_id, $tableAlias, $returnColumn, $where) {
			$wordlistTable = $this->searchConfig->getWordlistTableName();
			$query = 'SELECT `' . $tableAlias. '`.`' . $returnColumn . '` FROM `' . $wordlistTable . '` AS ' . $tableAlias . ' WHERE ' . $tableAlias . '.word_id = ' . (int) $word_id . ' AND ' . $tableAlias . '.index_id = ' . $index_id . ' ' . $where;
			return $query;
		}
		
		public function constructQueryForNextWord() {
			$lastTableAlias = $this->lastTableAlias;
			$tableAlias = 'tbl' . $this->tableAliasOffset++;
			
			$this->lastTableAlias = $tableAlias;
			$where = '';
			$word = array_shift($this->words);
			if (sizeof($this->words) >= 1) {
				$where = ' AND ' . $tableAlias . '.next_id IN (' . $this->constructQueryForNextWord() . ')';
			}
			
			return $this->createQueryForWord($this->getWordId($word), 'wordlist.index_id', $tableAlias, 'id', $where);
		}
		
		public function updateQuery_() {
			$this->words = $this->getWords($this->phrase);
			
			if (!$this->query) {
				$wordlistTable = $this->searchConfig->getWordlistTableName();
				$this->query = 'SELECT DISTINCT wordlist.index_id index_id FROM ' . $wordlistTable . ' wordlist';
				$this->where = ' WHERE ';
			} else {
				$this->where .= ' AND ';
			}
			
			$this->lastTableAlias = 'wordlist';
			
			$this->where .= ' wordlist.id IN (' . $this->constructQueryForNextWord() . ')';
		}
		
		protected function getWordId($word) {
			static $wordCache = null;
			$db = SERIA_Base::db();
			
			if (!$wordCache) {
				$wordCache = new SERIA_Cache('search_word');
			}
			
			if (!$word_id = $wordCache->get(md5($word))) {
				$wordquery  = 'SELECT id FROM ' . SERIA_PREFIX . '_searchindex_words WHERE word=' . $db->quote($word) . ' AND length=' . strlen($word);
				$result = $db->query($wordquery);
				if (!$row = $result->fetch(PDO::FETCH_NUM)) {
					$word_id = -1;
				}
				
				$word_id = $row[0];
				$wordCache->set(md5($word), $word_id, 3600);
			}
			
			return (int) $word_id;
		}
		
		public function updateQuery() {
			$db = SERIA_Base::db();
			
			$this->words = $words = $this->getWords($this->phrase);
			
			$matchingarticles = array();
			$word_ids = array();
			$wordpositions = array();
			$possibleArticles = array();
			
			$limit = $this->limit;
			$offset = $this->offset;
			
			$searchConfig = $this->searchConfig;
			
			$last_word_id = 0;
			$passNum = 0;
			
			// Construct a query of all words, one left join for every word
			// other than first word
			$tblCount = $this->tableAliasOffset;
			$phraseOffset = 0;
			$query = $this->query;
			$where = $this->where;
			$lastTableAlias = $this->lastTableAlias;
			
			
			foreach ($words as $word) {
				// Get word ID
				
				$word_id = $this->getWordId($word);
				
				$wordlistTableName = $searchConfig->getWordlistTableName();
				$indexTableName = $searchConfig->getIndexTableName();
				
				$tableAliases[$word_id] = $tableAlias = 'tbl' . ++$tblCount;
				
				$phraseOffset++;
				
				if ($tblCount == 1) {
					// Construct first part of query on first word.
					$query .= 'SELECT DISTINCT ' . $tableAlias . '.index_id FROM ' . $wordlistTableName . ' AS ' . $tableAlias . ' ';
					$where .= 'WHERE ' . $tableAlias . '.word_id = ' . $word_id . ' ';
				} else {
					// Construct a left join and a where for this word into the query
					
					$matchPrevWord = '';
					
					// If this is the first word in the phrase, it is not going to be matched against previous word in query 
					if ($phraseOffset > 1) {
						$matchPrevWord = $tableAlias . '.id=' . $lastTableAlias . '.next_id AND';
					}
					
					$query .= ' INNER JOIN ' . $wordlistTableName . ' AS ' . $tableAlias . '
					                 ON ' . $matchPrevWord . '
					                    ' . $tableAlias . '.index_id = tbl1.index_id AND 
					                    ' . $tableAlias . '.word_id=' . $word_id . ' ';
					
//					$where .= ' AND ' . $tableAlias . '.index_id IS NOT NULL ';
//					$where .= ' AND ' . $tableAlias . '.word_id=' . $word_id;
				}
				
				$lastTableAlias = $tableAlias;
			}
			
			$this->query = $query;
			$this->where = $where;
			$this->tableAliasOffset = $tblCount;
			$this->lastTableAlias = $lastTableAlias;
			
			return;
		}
		
		public function run() {
			$db = SERIA_Base::db();
			
			$query = $this->query;
			$where = $this->where;
			$searchConfig = $this->searchConfig;
			$this->words = $words = $this->getWords($this->phrase);
			
			$indexTableName = $searchConfig->getIndexTableName();
			//$query .= ' INNER JOIN ' . $indexTableName . ' AS indexTable ON indexTable.id = tbl1.index_id ';
			
			$orderElements = array();
			// Create ORDER BY definitions
			foreach ($searchConfig->columns as $columnObject) {
				if ($columnObject->sortindex) {
					$index = $columnObject->sortindex * 100;
					
					while (isset($orderElements[$index])) {
						++$index;
					}
					
					$orderElements[$index] = array($columnObject->name, $columnObject->sortdirection);
				}
			}
			
			$orderBy = '';
			
			if (sizeof($orderElements)) {
				$orderBy = array();
				
				ksort($orderElements);
				foreach ($orderElements as $orderElement) {
					$orderBy[] = $orderElement[0] . ' ' . $orderElement[1];
				}
				$orderBy = ' ORDER BY indexTable.sort_' . implode(', ', $orderBy);
			}
			
			// Create limit and offset definitions
			$limit = $this->limit;
			$offset = $this->offset;
			$limitPart = '';
			if ($limit) {
				$limitPart = ' LIMIT ' . $limit;
				if ($offset) {
					$limitPart .= ' OFFSET ' . $offset;
				}
			}
			
			$query .= $where;
			
			$query = 'SELECT indexTable.id FROM ' . $indexTableName . ' AS indexTable WHERE indexTable.id IN (' . $query . ') ' . $orderBy . ' ' . $limitPart;
			//$query = 'SELECT indexTable.id FROM ' . $indexTableName . ' AS indexTable WHERE indexTable.id IN (SELECT id FROM (' . $query . ') AS x) ' . $orderBy . ' ' . $limitPart;
			SERIA_Base::debug("$query<br />");
			
			$result = $db->query($query);
			while ($row = $result->fetch(PDO::FETCH_NUM)) {
				list($index_id) = $row;
				
				$possibleArticles[] = $index_id;
			}
			
			return $possibleArticles;
		}
	}
?>
