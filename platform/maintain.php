<?php
/**
 *	This file should be called every minute all year. It is responsible for performing maintenance functions
 *	for Seria Platform. This includes uploading files to mirror FTP-servers if they are used etcetera.
 *
 *	Helper functions:
 *
 *	function start_maintain($name, $seconds)
 *		Returns true if the script should start, false if it is running or if it is less than $seconds since last time it was run.
 *
 *	function stop_maintain($name)
 *		Registers that the script finished.
 */

ini_set("memory_limit", "1024M");
set_time_limit(1800);
ignore_user_abort();
$seria_options["cache_expire"] = 0;
$seria_options['skip_session'] = true;
$seria_options['skip_authentication'] = true;
require_once(dirname(__FILE__)."/../main.php");

/**
 * Return quickly (output will not be sent to client)
 */
if(isset($_GET["quick"]))
{
	ob_end_clean();
	header("Connection: close");
	header("Content-Length: 0");
	ob_end_flush();
	flush();
}

$gui = new SERIA_Gui(_t('Seria Maintain Script'));

// Disable output buffering to enable debug output on a crash
//while(ob_end_flush());
//ob_implicit_flush(true);

function run_search_maintain($fromIndexer = false) {
	try
	{
		if(start_maintain("search_maintain", SERIA_INSTALL||SERIA_DEBUG?1:60))
		{
			include_once(dirname(__FILE__)."/maintain/search_maintain.php");
			SERIA_Base::debug("Search maintain: ".search_maintain($fromIndexer));
			stop_maintain("search_maintain");
		}
	}
	catch (Exception $e)
	{
		SERIA_Base::debug("Error: ".$e->getMessage());
		stop_maintain("search_maintain");
		throw $e;
	}
}

function run_payment_maintain() {
	try
	{
		if(start_maintain("payment_maintain", SERIA_INSTALL||SERIA_DEBUG?1:60))
		{
			include_once(dirname(__FILE__)."/maintain/payment_maintain.php");
			SERIA_Base::debug("Payment maintain: " . payment_maintain());
			stop_maintain("payment_maintain");
		}
	}
	catch (Exception $e)
	{
		SERIA_Base::debug("Error: ".$e->getMessage());
		stop_maintain("payment_maintain");
		throw $e;
	}
}


function run_articles_maintain() {
	try
	{
		if(start_maintain("articles_maintain", SERIA_INSTALL||SERIA_DEBUG?1:60))
		{
			include_once(dirname(__FILE__)."/maintain/articles_maintain.php");
			SERIA_Base::debug("Articles maintain: ".articles_maintain());
			stop_maintain("articles_maintain");
		}
	}
	catch (Exception $e)
	{
		SERIA_Base::debug("Error: ".$e->getMessage());
		stop_maintain("articles_maintain");
		throw $e;
	}
}

function run_janitor_maintain() {
	try {
		if(start_maintain('janitor', 1))
		{
			$janitor = SERIA_Janitor::getInstance();
			$janitor->run();
			stop_maintain('janitor');
		}
	} catch (Exception $e) {
		SERIA_Base::debug('WARNING: Janitor failed: '.$e->getMessage());
		stop_maintain('janitor');
		throw $e;
	}
}

function run_async_maintain() {
	try
	{
		if (start_maintain('async', 1))
		{
			try {
				$queue = SERIA_Queue::createObject('SERIA_Async');
				while (($task = $queue->fetch(600))) {
					try {
						$data = unserialize($task->get('data'));
						call_user_func_array($data['call'], $data['args']);
						$task->success();
					} catch (Exception $e) {
						$task->failed($e->getMessage());
					}
				}
			} catch (SERIA_NotFoundException $e) {
				SERIA_Base::debug('Unable to read queue: '.$e->getMessage());
			}
			stop_maintain('async');
		}
	} catch (Exception $e) {
		SERIA_Base::debug('WARNING: Async failed: '.$e->getMessage());
		stop_maintain('async');
		throw $e;
	}
}


function run_maintains() {
	// If running in install mode, first try to install base database tables to be able to proceed if database is empty
	if (SERIA_INSTALL) {
		require_once(SERIA_ROOT . '/seria/platform/install/base.php');
	}
	
	/**
	 * Register time of running this service
	 */
	try {
		SERIA_Base::setParam("maintain_last_run", time());
	} catch (Exception $null) {}
	
	/**
	 *	Check if there is any new files in the seria/platform/install/ that is newer
	 *	than the date of base.php
	 */
	try
	{
		if(start_maintain("install_maintain", SERIA_INSTALL?1:60))
		{
			include(dirname(__FILE__)."/maintain/install_maintain.php");
			SERIA_Base::debug("Install maintain: ".install_maintain());
			stop_maintain("install_maintain");
		}
	
	}
	catch (Exception $e)
	{
		SERIA_Base::debug("Error: ".$e->getMessage());
		stop_maintain("install_maintain");
		throw $e;
	}
	
	try
	{
		if(start_maintain("install_maintain2", SERIA_INSTALL?1:60))
		{
			include(dirname(__FILE__)."/maintain/install_maintain2.php");
			SERIA_Base::debug("Install maintain 2: ".install_maintain2());
			stop_maintain("install_maintain2");
		}
	
	}
	catch (Exception $e)
	{
		SERIA_Base::debug("Error: ".$e->getMessage());
		stop_maintain("install_maintain2");
		throw $e;
	}
	
	
	/**
	 *	Upload files to the FTP mirror servers.
	 */
	try
	{
		$maintainName = 'files_maintain';
		
		if(start_maintain($maintainName, SERIA_INSTALL||SERIA_DEBUG?1:60))
		{
			include(dirname(__FILE__)."/maintain/" . $maintainName . ".php");
			SERIA_Base::debug("Files maintain: ".files_maintain());
			stop_maintain($maintainName);
		}
	}
	catch (Exception $e)
	{
		SERIA_Base::debug("Error: ".$e->getMessage());
		stop_maintain("files_maintain");
		throw $e;
	}
	
	run_search_maintain();
	run_articles_maintain();
	run_payment_maintain();
	run_janitor_maintain();
	run_maintain_hooks();
	run_async_maintain();
}

function run_maintain_hooks() {
	$hooks = array(
		SERIA_MAINTAIN_HOOK => 0,
		SERIA_MAINTAIN_1_MINUTE_HOOK => 60,
		SERIA_MAINTAIN_5_MINUTES_HOOK => 60 * 5,
		SERIA_MAINTAIN_15_MINUTES_HOOK => 60 * 15,
		SERIA_MAINTAIN_30_MINUTES_HOOK => 60 * 30,
		SERIA_MAINTAIN_1_HOUR_HOOK => 60 * 60
	);
	
	foreach ($hooks as $name => $timelimit) {
		if (SERIA_Base::getParam($key = 'seria_maintain_hook_' . $name, true) < (time() - $timelimit)) {
			SERIA_Base::setParam($key, time());
			SERIA_Hooks::dispatch($name);
		}
	}
}

$g_running_maintains = array();
function start_maintain($name, $seconds)
{
	global $g_running_maintains;
	SERIA_Base::debug('Starting maintain: '.$name);
	// script is running
	if(!SERIA_Base::setParamIfNotExists("maintain_".$name."_is_running", 1) &&
	   !SERIA_Base::setParamIfEqualTo("maintain_".$name."_is_running", 1, 0)) {

	   	$last_run = SERIA_Base::getParam('maintain_' . $name . '_last_run', true);
		if ($last_run > time() - 3600) {
			SERIA_Base::debug('Maintain is already running for: '.$name);
			return false;
		} else {
			if (SERIA_Base::setParamIfEqualTo('maintain_' . $name . '_last_run', time(), $last_run))
			{
				SERIA_Base::debug($name . ' maintain is marked as running, but time limit exceeded. Ignoring status.');
			}
			else
			{
				return false; /* Another instance handled it first! */
			}
		}
	}
	// less than $seconds since last run
	$lastRun = intval(SERIA_Base::getParam("maintain_".$name."_last_run", true));
	if($lastRun > time()-$seconds) {
		SERIA_Base::setParam("maintain_".$name."_is_running", 0);
		return false;
	}

	SERIA_Base::setParam("maintain_".$name."_last_run", time());
	$g_running_maintains[] = $name;

	register_shutdown_function("stop_maintain", $name);

	SERIA_Base::debug('Started maintain: '.$name);

	return true;
}

function stop_maintain($name)
{
	global $g_running_maintains;

	SERIA_Base::debug('Stopping maintain: '.$name);
	if (($key = array_search($name, $g_running_maintains)) !== false) {
		unset($g_running_maintains[$key]);
		SERIA_Base::setParam("maintain_".$name."_is_running", 0);
	} else
		SERIA_Base::debug('Duplicate stop maintain: '.$name);
}

if (!isset($manual_maintain)) {
	run_maintains();
}

$gui->contents('<h1>'._t('Seria Platform Maintain Script').'</h1>');

if(isset($_GET['continue']))
{
	SERIA_Template::head('continue', '<script type="text/javascript">document.location.href="'.$_GET['continue'].'";</script>');
}

echo $gui->output();
