<?php

if (!isset($_GET['taskurl']) || !$_GET['taskurl'])
	throw new SERIA_Exception('Task-url is required!');

$taskData = file_get_contents($_GET['taskurl']);
$taskData = json_decode($taskData, true);

$roamAuthData = SERIA_UserLoginXml::parseXml($taskData['userxml']);

$user = RoamAuthprovider::findUserByRoamAuthData($roamAuthData);

if ($user)
	SERIA_User::deleteUserPermanently($user);

try {
	$reportDone = new SERIA_WebBrowser();
	$reportDone->navigateTo($taskData['postReportDoneUrl'], $taskData['postReportDoneData']);
	$reportDone->fetchAll();
	if ($reportDone->responseCode != 200)
		throw new SERIA_Exception('Failed to report back user delete!');

	if (isset($_GET['jsonp']) && $_GET['jsonp']) {
		SERIA_Template::disable();
		?>
			<?php echo $_GET['jsonp']; ?>(true);
		<?php
		die();
	}

	echo 'OK';
} catch (Exception $e) {
	if (isset($_GET['jsonp']) && $_GET['jsonp']) {
		SERIA_Template::disable();
		?>
			<?php echo $_GET['jsonp']; ?>(false);
		<?php
		die();
	}
}