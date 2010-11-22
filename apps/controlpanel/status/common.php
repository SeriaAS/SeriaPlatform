<?php
	$seria_options["cache_expire"] = 0; // do not cache this page, as it should list current messages
	require_once(dirname(__FILE__)."/../common.php");
	$gui->setActiveTopMenu("status");
	$gui->exitButton(_t("< Main Admin"), "location.href='./../'");
	
	$menu = $gui->createSectionMenu(_t('Options'));
	
	$cache = new SERIA_Cache('statusMessages');
	if (!$messageCount = $cache->get('messageCount')) {
		$messageCount = SERIA_SystemStatus::getMessageCount();
		$cache->set('messageCount', $messageCount, 15);
	}
	list($notices, $warnings, $errors) = $messageCount;
	$total = $notices + $warnings + $errors; 
	
	$messageCount = $errors . ' errors, ';
	$messageCount .= $warnings . ' warnings';
	
	$menu->addLink(_t('Events (%COUNT%)', array('COUNT' => $messageCount)), SERIA_HTTP_ROOT . '/seria/apps/controlpanel/status/events.php');
	
?>
