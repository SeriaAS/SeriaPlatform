<?php
	class SERIA_WidgetRecord extends SERIA_ActiveRecord {
		public $tableName = '_widgets';
		public $usePrefix = true;
		public $useGuid = true;
		protected $guidKey = 'widget';
		
		public $hasMany = array(
			'SERIA_WidgetDataRecord:DataRecords' => 'widget_id'
		);
	}
?>