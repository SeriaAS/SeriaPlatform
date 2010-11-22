<?php
	class SERIA_SystemStatusMessage extends SERIA_ActiveRecord {
		public $tableName = '_systemstatusmessages';
		
		public $columnTypes = array(
			'time' => 'TIMEDIFF'
		);
		
		public function getTableRowCssClass() {
			switch ($this->level) {
				case SERIA_SystemStatus::ERROR:
					return 'tableErrorRow';
					break;
				case SERIA_SystemStatus::NOTICE:
					return 'tableNoticeRow';
					break;
				case SERIA_SystemStatus::WARNING:
					return 'tableWarningRow';
					break;
			}
		}
	}
?>