<?php

require_once(dirname(__FILE__).'/../../../../main.php');

SERIA_Base::preventCaching();
SERIA_Template::disable();

if (!isset($_GET['widget_id'])) {
	SERIA_Base::redirectTo(SERIA_HTTP_ROOT);
	die();
}

$widget = SERIA_Widget::createObject($_GET['widget_id']);
$article = $widget->getNamedObject();

$share_url = SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_ShareWithFriends/pages/doShare.php?type='.urlencode($_GET['type']).'&widget_id='.$widget->getId().'&loop=yes';
$article_url = SERIA_HTTP_ROOT.'/?article_id='.$article->get('id');

if ($_GET['type'] == 'twitter') {
	if (isset($_POST['queryNumber']) && isset($_POST['status_text'])) {
		$qid = $_POST['queryNumber'];
		if (($key = array_search($qid, $_SESSION['twitter_update_authorization'])) === false) {
			SERIA_Base::redirectTo($article_url.'&twitterfail='.urlencode(_t('The Twitter call failed due to an expired session.')).'&twittertime='.urlencode(time()));
			die();
		}
		unset($_SESSION['twitter_update_authorization'][$key]);
		$status_text = $_POST['status_text'];
	} else if (isset($_GET['qn']) && isset($_SESSION['twitter_query']) && isset($_SESSION['twitter_query'][$_GET['qn']])) {
		$status_text = $_SESSION['twitter_query'][$_GET['qn']];
		unset($_SESSION['twitter_query'][$_GET['qn']]);
	} else {
		SERIA_Base::redirectTo($article_url.'&twitterfail='.urlencode(_t('Missing required fields in submit.')).'&twittertime='.urlencode(time()));
		die();
	}
	
	SERIA_Base::addFramework('twitter');

	/*
	 * Provide twitter login..
	 */
	$twittersys = SERIA_TwitterSys::getAuth();
	if ($twittersys === null || !$twittersys->isAuthenticated()) {
		if (!isset($_GET['loop'])) {
			if (!isset($_SESSION['twitter_query']))
				$_SESSION['twitter_query'] = array();
			$_SESSION['twitter_query'][$qid] = $status_text;
			$share_url .= '&qn='.urlencode($qid);
			SERIA_TwitterSys::startAuth($share_url);
		} else
			SERIA_Base::redirectTo($article_url.'&twitterfail='.urlencode(_t('Authentication error.')).'&twittertime='.urlencode(time()));
		die();
	}

	/*
	 * Submit 
	 */
	$twitter_status = new SERIA_TwitterStatus($twittersys);
	try {
		$twitter_status->update($status_text);
		SERIA_Base::redirectTo($article_url);
	} catch (Exception $e) {
		$error = $e->getMessage();
		if (mb_check_encoding($error, 'UTF-8')) {
			if (strlen($error, 'UTF-8') > 140)
				$error = mb_substr($error, 0, 100, 'UTF-8').'...';
		} else if (mb_check_encoding($error)) {
			if (strlen($error) > 140)
				$error = mb_substr($error, 0, 100).'...';
		} else {
			if (strlen($error) > 140)
				$error = substr($error, 0, 130).'...';
		}
		SERIA_Base::redirectTo($article_url.'&twitterfail='.urlencode($error).'&twittertime='.urlencode(time()));
	}
} else {
	SERIA_Base::redirectTo(SERIA_HTTP_ROOT);
	die();
}

?>