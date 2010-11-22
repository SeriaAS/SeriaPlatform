<?php

require_once(dirname(__FILE__).'/../main.php');
SERIA_Template::disable();

$output = array();

try {
	if (isset($_GET['subscriberKey'])) {
		$subscriber = SERIA_CometSubscribers::find_first_by_key($_GET['subscriberKey']);
		if ($subscriber) {
			$subscriber->updateTime();
		} else {
			$output['error'] = _t('Connection was lost. Try to reload the page.');
		}
	}
} catch (Exception $e) {
	$output['error'] = _t('AJAX ping request malfunction');
}

SERIA_Lib::publishJSON($output);

?>
