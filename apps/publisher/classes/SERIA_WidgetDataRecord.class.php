<?php
	class SERIA_WidgetDataRecord extends SERIA_ActiveRecord {
		public $tableName = '_widgetdata';
		public $usePrefix = true;
		
		public $belongsTo = array(
			'SERIA_WidgetRecord:WidgetRecord' => 'widget_id'
		);
	}
?>