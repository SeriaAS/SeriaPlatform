<?php

class SERIA_RecommendByEMailWidget_Record extends SERIA_ActiveRecord
{
	public $tableName = '_recommend_by_email';
	public $usePrefix = true;
}

class SERIA_RecommendByEMailWidget extends SERIA_Widget
{
		protected $attachedEvents = array('DELETE' => 'onDelete');
		protected $r_record;
		protected $linkText = false;

		protected function initWidgetRecord()
		{
			$this->r_record = SERIA_RecommendByEMailWidget_Records::find_first_by_widget_id($this->getId());
			if (!$this->r_record) {
				$record = new SERIA_RecommendByEMailWidget_Record();
				$record->widget_id = $this->getId();
				$record->save();
				$this->r_record = $record;
			}
		}
		public function initialize() {
			$installer = new SERIA_Installer();

			$installer->runWidgetUpdates();

			$this->initWidgetRecord();
		}
		protected function wakeup()
		{
			$this->initWidgetRecord();
		}
		protected function sleep()
		{
			$this->r_record->save();
		}

		public function setURL($url)
		{
			$this->r_record->url = $url;
			return $this;
		}
		public function assertGetURL()
		{
			if (!$this->r_record->url)
				throw new Exception('URL is not set for recommend widget.');
			return $this->r_record->url;
		}
		public function setFrom($from)
		{
			$this->r_record->em_from = $from;
			return $this;
		}
		public function getFrom()
		{
			return $this->r_record->em_from;
		}

		/**
		 * WARNING: The link text is not persistent. Call this function on every page load if needed.
		 *
		 * @param $linkText
		 * @return unknown_type
		 */
		public function setLinkText($linkText)
		{
			$this->linkText = $linkText;
			return $this;
		}
		public function getLinkText()
		{
			return $this->linkText;
		}
		protected function onDelete($source) {
			$this->r_record->delete();
			$this->delete();
		}
		protected function onPublish($source) {
		}
		
		public function action_index() {
		}
		public function action_link() {
		}
}

?>