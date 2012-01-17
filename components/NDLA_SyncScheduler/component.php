<?php

$ndlaSyncEnabled = false;

if (defined('NDLA_SYNC_SCRIPT_2'))
	$ndlaSyncEnabled = NDLA_SYNC_SCRIPT_2;
if ($ndlaSyncEnabled)
	$ndlaSyncEnabled = file_exists($ndlaSyncEnabled);
if (!$ndlaSyncEnabled) {
	if (defined('NDLA_SYNC_SCRIPT'))
		$ndlaSyncEnabled = NDLA_SYNC_SCRIPT;
	if ($ndlaSyncEnabled)
		$ndlaSyncEnabled = file_exists($ndlaSyncEnabled);
}

define('NDLA_SYNC_SCHEDULER_ENABLED', $ndlaSyncEnabled);

if ($ndlaSyncEnabled) {
	SERIA_Base::addClassPath(dirname(__FILE__).'/classes/*.class.php');
	SERIA_Base::addClassPath(dirname(__FILE__).'/sapi/*.class.php');
	$component = new NDLA_SyncSchedulerComponent();
	SERIA_Components::addComponent($component);
	$component->embed();
}

function NDLA_SyncScheduler_init()
{
	if (defined('NDLA_SYNC_SCHEDULER_ENABLED') && NDLA_SYNC_SCHEDULER_ENABLED) {
		$component = SERIA_Components::getComponent('NDLA_SyncSchedulerComponent');
		$component->init();
	}
}
