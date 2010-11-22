<?php
/*

	DEPRECATED IMPLEMENTATION

	This class will be renamed and reimplemented as SERIA_Watchdog


*/
	class SERIA_SystemStatus {
		const NOTICE = 1;
		const WARNING = 2;
		const ERROR = 3;
		
		protected static function getCache() {
			return new SERIA_Cache('systemstatus');
		}
		
		public static function publishMessage($level, $content, $category = 'system', $key = null) {
			self::publishHtmlMessage($level, htmlspecialchars($content), $category, $key);
		}
		
		public static function publishHtmlMessage($level, $content, $category = 'system', $key = null) {
			if ($level < 1 || $level > 3) {
				throw new SERIA_Exception('Status message publish failed');
			}
			
			if ($key === false) {
				$key = md5($content);
			}
			
			$message = new SERIA_SystemStatusMessage();
			$message->level = $level;
			$message->message = $content;
			$message->status = 0;
			$message->time = date('c');
			$message->category = $category;
			
			if ($key !== null) {
				$message->key = $key;
			} else {
				$message->key = $category . '_' . time(). mt_rand(0,1000);
			}
			$message->replace();
		}
		
		public static function getMessageCount() {
			$query = 'SELECT level, COUNT(id) FROM ' . SERIA_PREFIX . '_systemstatusmessages WHERE status=0 GROUP BY level';
			$result = SERIA_Base::db()->query($query)->fetchAll(PDO::FETCH_NUM);
			
			$notices = 0;
			$warnings = 0;
			$errors = 0;
			
			foreach ($result as $row) {
				list($level, $count) = $row;
				switch ($level) {
					case SERIA_SystemStatus::NOTICE:
						$notices = $count;
						break;
					case SERIA_SystemStatus::WARNING:
						$warnings = $count;
						break;
					case SERIA_SystemStatus::ERROR:
						$errors = $count;
						break;
				}
			}
			
			return array($notices, $warnings, $errors);
		}
		
		public static function getMessages() {
			$messages = SERIA_SystemStatusMessages::find_all_by_status(0, array('order' => array('time' => 'DESC'), 'limit' => 10000));
			return $messages;
		}
	}
?>
