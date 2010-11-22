<?php
	class TestWidget extends SERIA_Widget {
		protected $attachedEvents = array('DELETE' => 'onDelete');

		public function initialize() {
			$articleQuery = new SERIA_ArticleQuery('Video');
			$this->articles = $articles = $articleQuery->page(0,1000);
			
			foreach ($articles as $article) {
				$this->attachObject($article);
			}
		}
		
		protected function onDelete($source) {
			echo '<p><strong>DELETE!!</strong></p>';
		}
		protected function onPublish($source) {
			echo '<p><strong>PUBLISH!!</strong></p>';
		}
		
		public function action_index() {
		}
	}
?>
