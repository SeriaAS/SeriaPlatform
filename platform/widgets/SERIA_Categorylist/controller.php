<?php
	class SERIA_CategorylistWidget extends SERIA_Widget {
		public function action_index() {
			$this->categories = SERIA_ArticleCategory::getCategories();
		}
	}
?>