<?php
	function search_maintain($fromIndexer = false) {
		function local_logMessage($message) {
			if (function_exists('search_indexer_log_message')) {
				search_indexer_log_message($message);			
			}
		}
		
		$db = SERIA_Base::db();
		
		$wordCache = new SERIA_Cache('search_words');
		
		
		$searchConfigs = SERIA_SearchIndexConfigs::find_all(array('include' => array('Columns')));
		
		if (!sizeof($searchConfigs)) {
			return 'Nothing to index';
		}
		
		foreach ($searchConfigs as $config) {
			$tableName = $config->searchtablename;
			$indexTableName = $config->getIndexTableName();
			$sortTableName = $config->getSortTableName();
			$wordlistTableName = $config->getWordlistTableName();
			
			// Check if search index table exists
			$query = 'SHOW TABLES LIKE ' . $db->quote($indexTableName);
			$result = $db->query($query);
			$found = false;
			if ($row = $result->fetch(PDO::FETCH_NUM)) {
				if ($row[0] == $indexTableName) {
					$found = true;
				}
			}
			
			// Create search index table if not exists
			if (!$found) {
				$sortColumns = '';
				$query = 'DESCRIBE `' . $tableName . '`';
				$result = $db->query($query);
				while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
					$field = $row['Field'];
					$type = $row['Type'];
					$null = ($row['Null'] != 'NO');
					
					$found = false;
					foreach ($config->Columns as $column) {
						if ($column->name == $field) {
							if ($column->sortindex) {
								$found = true;
							}
						}
					}
					
					if ($found) {
						if ($null) {
							$nullText = 'NULL';
						} else {
							$nullText = 'NOT NULL';
						}
						
						$sortColumns .= ', sort_' . $field . ' ' . $type . ' ' . $nullText;
						$sortColumns .= ', KEY sort_' . $field . ' (sort_' . $field . ')';
					}
				}
				
				$query = 'CREATE TABLE `' . $indexTableName . '` (
				          	id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				          	lastupdate TIMESTAMP NOT NULL,
				          	guid INT UNSIGNED NOT NULL,
				          	KEY lastupdate (lastupdate),
				          	KEY guid (guid)
				          	' . $sortColumns . '
				          	) ENGINE=MyISAM';
				$db->query($query);
				
				$query = 'CREATE TABLE `' . $wordlistTableName . '` (
				          	id MEDIUMINT UNSIGNED NOT NULL,
				          	index_id MEDIUMINT UNSIGNED NOT NULL,
				          	word_id MEDIUMINT UNSIGNED NOT NULL,
				          	column_id SMALLINT UNSIGNED NOT NULL,
				          	next_id MEDIUMINT UNSIGNED NOT NULL,
				          	PRIMARY KEY (index_id, word_id, id),
				          	KEY word_index_id (word_id, index_id)
				          ) ENGINE=MyISAM';
				
				$db->query($query);
			}
		}
		
		$wordIdCache = array();
				
		// Update indexes
		$count = 0;
		$indexCount = 0;
		foreach ($searchConfigs as $config) {
			$tableName = $config->searchtablename;
			$indexTableName = $config->getIndexTableName();
			$sortTableName = $config->getSortTableName();
			$wordlistTableName = $config->getWordlistTableName();
			
			if ($fromIndexer) {
				$limit = 60;
			} else {
				$limit = 2;
			}
			
			// Get all rows updated after last index or not added to index
			$query = '	SELECT indextable.id indextable_id, realtable.id realtable_id FROM `' . $config->searchtablename . '` realtable
							LEFT JOIN `' . $indexTableName . '` indextable ON realtable.`' . $config->keycolumn . '` = indextable.guid
								WHERE realtable.`' . $config->timestampcolumn . '` > indextable.lastupdate
					UNION
						SELECT indextable.id indextable_id, realtable.id realtable_id FROM `' . $config->searchtablename . '` realtable
							LEFT JOIN `' . $indexTableName . '` indextable ON realtable.`' . $config->keycolumn . '` = indextable.guid
							WHERE indextable.id IS NULL LIMIT ' . $limit;
			$result = $db->query($query);
			while ($row = $result->fetch(PDO::FETCH_NUM)) {
				list($index_id, $real_id) = $row;
				
				$ordercolumns = array();
				$indexcolumns = array();
				$columns = array();
				$striphtml = array();
				foreach ($config->Columns as $configColumn) {
					if ($configColumn->indexable || $configColumn->sortindex) {
						$columns[] = $configColumn->name;
						if ($configColumn->sortindex) {
							$ordercolumns[$configColumn->id] = $configColumn->name;
						}
						if ($configColumn->indexable) {
							$indexcolumns[$configColumn->id] = $configColumn->name;
						}
					}
					
					$striphtml[$configColumn->id] = ($configColumn->striphtml > 0);
				}
				
				local_logMessage('Indexing ' . $real_id . ' from ' . htmlspecialchars($config->searchtablename) . '... ');
				$startTotal = microtime(true);
				$insertTime = 0;
				
				if (!$index_id) {
					$query = 'INSERT INTO `' . $indexTableName . '` (guid) VALUES(' . (int) $real_id . ')';
					$db->query($query);
					$index_id = $db->lastInsertId();
				} else {
					$query = 'DELETE FROM `' . $wordlistTableName . '` WHERE index_id = ' . (int) $index_id;
					$db->query($query);
				}
				
				foreach ($columns as &$column) {
					$column = '`' . $column . '`';
				}
				unset($column);
				
				$query = 'SELECT ' . implode(',', $columns) . ' FROM `' . $config->searchtablename . '` WHERE ' . $config->keycolumn . '=' . $real_id . ' LIMIT 1';
				$result2 = $db->query($query);
				$row = $result2->fetch(PDO::FETCH_ASSOC);
				$wordlistId = 0;
				
				$inserts = array();
				$sortInserts = array();
				
				
				foreach ($indexcolumns as $column_id => $columnName) {
					$data = $row[$columnName];
					if ($striphtml[$column_id]) {
						$data = strip_tags($data);
					}
					$wordProcessor = new SERIA_Search_Wordprocessor($data);
					$words = $wordProcessor->getWords();
					
					// reverse the wordlist to make indexing of next_id faster
					$words = array_reverse($words);
					
					// Go through all words, last word first.
					$last_wordlist_id = 0;
					
					$wordTotal = 0;
					$wordStart = microtime(true);
					foreach ($words as $word) {
						if (strlen($word) <= 100) {
							if (!($word_id = $wordCacheLocal[$word]) && !($word_id = $wordCache->get($word))) {
								$query = 'SELECT id FROM ' . SERIA_PREFIX . '_searchindex_words WHERE word=' . $db->quote($word);
								$result3 = $db->query($query);
								if ($row2 = $result3->fetch(PDO::FETCH_NUM)) {
									$word_id = $row2[0];
								} else {
									$query = 'INSERT INTO ' . SERIA_PREFIX . '_searchindex_words (length, word) VALUES(' . strlen($word) . ', ' . $db->quote($word) . ')';
									$db->query($query);
									$word_id = $db->lastInsertId();
								}
								
								$wordCache->set($word, $word_id);
								$wordCacheLocal[$word] = $word_id;
							}
							
							if ($word_id) {
								$inserts[] = '(' . (++$wordlistId) . ', ' . $index_id . ', ' . $word_id . ', ' . $column_id . ', ' . $last_wordlist_id . ')';
								$last_wordlist_id = $wordlistId;
							}
						} else {
							$last_wordlist_id = 0;
						}
						
						if (sizeof($inserts) > 10000) {
							$insertStart = microtime(true);
							search_maintain_commitWordlistInserts($inserts, $wordlistTableName, $sortColumnNames);
							$insertEnd = microtime(true);
							$insertTime += $insertEnd - $insertStart;
						}
					}
					$wordEnd = microtime(true);
					$wordTime = $wordEnd - $wordStart;
				}
				
				$sortColumnNames = $ordercolumns;
				$sortColumnValues = array();
				foreach ($sortColumnNames as $sortColumnName) {
					$sortColumnValues[$sortColumnName] = $row[$sortColumnName];
				}
				
				$sortColumns = '';
				foreach ($sortColumnNames as $name) {
					if (strlen($sortColumns)) {
						$sortColumns .= ', ';
					}
					$sortColumns .= '`sort_' . $name . '` = ' . $db->quote($sortColumnValues[$name]);
				}
				
				if (strlen($sortColumns)) {
					$query = 'UPDATE `' . $indexTableName . '` SET ' . $sortColumns . ' WHERE id=' . $index_id;
					$db->query($query);
				}
				
				$indexCount++;
				$query = 'UPDATE `' . $indexTableName . '` indextable SET indextable.lastupdate=(SELECT realtable.`' . $config->timestampcolumn . '` FROM `' . $config->searchtablename . '` realtable WHERE realtable.id=indextable.guid) WHERE indextable.id=' . $index_id;
				$db->query($query);
				
				$endTotal = microtime(true);
				$totalTime = $endTotal - $startTotal;
				local_logMessage('Done. Time: ' . round($totalTime, 3) . ' seconds. Insert time: ' . round($insertTime, 3) . ' seconds. Word indexing time: ' . round($wordTime, 3) . ' seconds<br />');
			}
		}
		
		$insertStart = microtime(true);
		search_maintain_commitWordlistInserts($inserts, $wordlistTableName, $sortColumnNames);
		$insertEnd = microtime(true);
		$insertTime += $insertEnd - $insertStart;
		
		return 'Indexed ' . $indexCount . ' rows';
	} 
	
	function search_maintain_commitWordlistInserts(&$inserts, $wordlistTableName, $sortColumnNames) {
		if (sizeof($inserts)) {
			$query = 'INSERT INTO ' . $wordlistTableName . ' (id, index_id, word_id, column_id, next_id)
			VALUES ' . implode(', ', $inserts);
			SERIA_Base::db()->query($query);
			
			$inserts = array();
		}
	}
?>