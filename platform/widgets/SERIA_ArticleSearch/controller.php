<?php
	class SERIA_ArticleSearchWidget extends SERIA_Widget {
		protected $resultCssClasses = array();
		public $linkResultTo;
		
		public function addResultCssClass($className) {
			$this->resultCssClasses[] = $className;
		}
		
		private function processQuery() {
			if (isset($_POST['SERIA_ArticleSearchWidget'])) {
				// Request is from post. Redirect to query action.
				$searchData = $_POST['SERIA_ArticleSearchWidget'];
				$query = $searchData['query'];
				SERIA_Base::redirectTo($this->getUrl('query', array('query' => $query)));
			}
		}
		
		private function checkPermissions() {
			// If widget is later extended to support user search it should be a administrator only
			// if not other specified by a method or property to maintain compatibility.
			if (!SERIA_Base::isAdministrator()) {
				throw new SERIA_Exception('Search widget only available for administrators');
			}
		}
		
		
		
		public function action_index() {
			$this->checkPermissions();
			
			$this->processQuery();
		}
		
		// Handler for search queries.
		public function action_query($params) {
			$this->checkPermissions();
			
			$this->processQuery();
			
			$query = $params['query'];
			
			$articleQuery = new SERIA_ArticleQuery();
			$articleQuery->where('@is_published');
			$articleQuery->where($query);
			$this->articleQuery = $articleQuery;
		}
	}
?>