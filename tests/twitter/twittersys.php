<?php

require_once(dirname(__FILE__).'/../../main.php');

if (!defined('SERIA_TWITTERSYS_TEST_ENABLED'))
	die('TwitterSys test disabled.');

if (!defined('SERIA_TWITTERSYS_TEST_WITHOUT_LOGIN')) {
	SERIA_Base::pageRequires('login');
	if (!SERIA_Base::isAdministrator())
		die('Must be admin to test this.');
}

$gui = new SERIA_Gui('Twitter test system');

SERIA_Base::addFramework('twitter');

$twittersys = SERIA_TwitterSys::getAuth();
if ($twittersys !== null || isset($_GET['tryauth'])) {
	ob_start();
	if ($twittersys !== null) {
		?>
		<p>Successfully authenticated</p>
		<?php
	} else {
		?>
		<p>Authentication failed</p>
		<?php
	}
	$gui->contents(ob_get_clean());
	echo $gui->output();
	return;
} else {
	SERIA_Template::disable();
	SERIA_TwitterSys::startAuth(SERIA_HTTP_ROOT.'/seria/tests/twitter/twittersys.php?tryauth=done');
	die();
}

?>
