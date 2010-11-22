<?php
	class SERIA_NewslistWidget extends SERIA_Widget {
		private $articleTypes = array();
		private $categories = array();
		private $limit = 100;
		
		public function addArticleType($articleType) {
			array_push($this->articleTypes, $articleType);
		}
		public function addCategory($category) {
			array_push($this->categories, $category);
		}
		public function setLimit($limit = 10) {
			$this->limit = $limit;
		}
		
		private function doQuery() {
			$queries = array();
			
			foreach ($this->articleTypes as $articleType) {
				$queries[$articleType] = $query = new SERIA_ArticleQuery($articleType);
				$query->isPublished();
			}
			
			foreach ($queries as $articleType => $query) {
				$subqueries = array();
				foreach ($this->categories as $category) {
					$subqueries[] = $subquery = new SERIA_ArticleQuery($articleType);
					if (is_int($category)) {
						$subquery->inCategoryId($category);
					} else {
						$subquery->inCategory($category);
					}
				}
				
				if (sizeof($subqueries)) {
					$queries[$articleType] = SERIA_ArticleQuery::_or($subqueries);
				}
			}
			
			$result = array();
			foreach ($queries as $query) {
				$result = array_merge($result, $query->page(0, 100));
			}
			
			// Order result
			
			// If there is zero or one row, there is nothing to sort
			if (sizeof($result) >= 2) {
				do {
					$sorted = true;
					$i = 0;
					while ($i < (sizeof($result) - 1)) {
						$key = $i++;
						$obj1 = $result[$key];
						$obj2 = $result[$key + 1];
						if ($obj1->get('published_date') < $obj2->get('published_date')) {
							$sorted = false;
							$tmp = $result[$key + 1];
							$result[$key + 1] = $result[$key];
							$result[$key] = $tmp;
						}
					}
				} while (!$sorted);
			}
			
			// Limit result
			$result2 = array();
			$count = 0;
			foreach ($result as $article) {
				if ($count++ > $this->limit) {
					break;
				}
				
				$result2[] = $article;
			}
			
			return $result2;
		}
		
		public function action_index() {
			$this->articles = $this->doQuery();
		}
	}
?>