<?php
	require_once(dirname(__FILE__)."/../common.php");
	$gui->setActiveTopMenu("status");
	SERIA_Base::pageRequires('admin');
	$id = $_GET['id'];
	
	if (!$id) {
		if ($_POST['status'] && $_POST['closeSelected']) {
			$status = $_POST['status'];
			
			if (is_array($status)) {
				$ids = array();
				foreach ($status as $id => $bool) {
					if ($bool) {
						$ids[] = $id;
					}
				}
				
				if (sizeof($ids)) {
					$messages = SERIA_SystemStatusMessages::find_all_by_id($ids);
					foreach ($messages as $message) {
						$message->status = 1;
						$message->save();
					}
					SERIA_HtmlFlash::notice(_t('The selected status messages are now closed'));
				}
			}
		} elseif ($_POST['closeOld']) {
			$messages = SERIA_SystemStatusMessages::find_all(array('criteriasSql' => 'UNIX_TIMESTAMP(time) <= ' . (time() - 3600)));
			foreach ($messages as $message) {
					$message->status = 1;
					$message->save();
			}
			SERIA_HtmlFlash::notice(_t('All messages older than one hour are now closed'));
		} else {
		}
	} else {
		if ($message = SERIA_SystemStatusMessages::find($id)) {
			$message->status = 1;
			$message->save();
			
			$cache = new SERIA_Cache('statusMessages');
			$cache->set('messageCount', null, 1);
			
			SERIA_HtmlFlash::notice(_t('The requested status message is now closed'));
		} else {
			SERIA_HtmlFlash::error(_t('Status message not found'));
		}
	}
	
	$url = SERIA_HTTP_ROOT . '/seria/apps/controlpanel/status/events.php';
	if ($_POST['intab']) {
		$url .= '#' . $_POST['intab'];
	}
	
	SERIA_Base::redirectTo($url);
?>
