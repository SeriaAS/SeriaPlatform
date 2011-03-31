<?php

$GLOBALS['debug_lockfile'] = false;
function DL_debug_logging_lock()
{
	global $debug_lockfile;

	if ($debug_lockfile !== false)
		throw new SERIA_Exception('Don\'t lock debug recursively!');
	$debug_lockfile = fopen(DEBUG_LOGFILE.'.lck', 'w');
	if ($debug_lockfile === false)
		throw new SERIA_Exception('Debug lockfile ('.DEBUG_LOGFILE.'.lck) open failure!');
	if (!flock($debug_lockfile, LOCK_EX))
		throw new SERIA_Exception('Failed to lock debug system!');
}
function DL_debug_logging_unlock()
{
	global $debug_lockfile;

	if ($debug_lockfile === false)
		throw new SERIA_Exception('Debug system is not locked!');
	flock($debug_lockfile, LOCK_UN);
	fclose($debug_lockfile);
	$debug_lockfile = false;
}

function DL_debug_logging_write($ts, $msg)
{
	global $page_view_id;

	DL_debug_logging_lock();
	$fh = fopen(DEBUG_LOGFILE, 'a');
	if ($fh) {
		fwrite($fh, $page_view_id.': '.base64_encode(serialize(array(
			'ts' => $ts,
			'msg' => $msg
		)))."\n");
		fclose($fh);
		DL_debug_logging_unlock();
		return true;
	}
	DL_debug_logging_unlock();
	return false;
}

if (SERIA_DEBUG && defined('DEBUG_LOGFILE')) {
	$page_view_id = sha1(mt_rand().time().serialize($_REQUEST));

	SERIA_Base::addClassPath(SERIA_ROOT.'/seria/components/DebugLogging/classes/*.class.php');

	$component = new DebugLoggingComponent();
	SERIA_Components::addComponent($component);

	SERIA_Base::debug('Starting debug logging component..');
	$sysparams = array(
		'timestamp' => time(),
		'$_SERVER' => $_SERVER,
		'$_GET' => $_GET,
		'$_POST' => $_POST,
		'$_FILES' => $_FILES,
		'$_COOKIE' => $_COOKIE
	);
	if (session_id()) {
		$sysparams['session_id'] = session_id();
		$sysparams['$_SESSION'] = $_SESSION;
	}
	SERIA_Base::debug('sysparams:'.base64_encode(serialize($sysparams)));
	try {
		DL_debug_logging_lock();
	} catch (SERIA_Exception $e) {
		SERIA_Base::debug('Debug logger fails with message: '.$e->getMessage());
		return;
	}
	if (defined('DEBUG_LOGFILE_MAX_SIZE')) {
		$st = stat(DEBUG_LOGFILE);
		if ($st) {
			if ($st['size'] > DEBUG_LOGFILE_MAX_SIZE) {
				/*
				 * Rotate the logs..
				 */
				for ($i = 5; $i > 0; $i--) {
					if (file_exists(DEBUG_LOGFILE.'.'.($i+1)))
						unlink(DEBUG_LOGFILE.'.'.($i+1));
					if (file_exists(DEBUG_LOGFILE.'.'.$i))
						rename(DEBUG_LOGFILE.'.'.$i, DEBUG_LOGFILE.'.'.($i+1));
				}
				rename(DEBUG_LOGFILE, DEBUG_LOGFILE.'.1');
			}
		}
	}
	DL_debug_logging_unlock();
	if (class_exists('SERIA_Template')) {
		$msgs = SERIA_Template::$vars['debugMessages'];
		$debugLog = array();
		foreach ($msgs as $mvals) {
			$debugLog[] = array(
				time(),
				$mvals['message']
			);
		}
		$debugLog[] = array(time(), 'WARNING: Timestamps before this are not correct (FIXME)!');
		$debugLog[] = array(time(), 'WARNING: Some messages may have been lost due to discarding of repeated messages!');
	} else
		$debugLog = array(
			array(time(), 'WARNING: Lost all debug messages before this! (Requires SERIA_Template)')
		);
	foreach ($debugLog as $value)
		DL_debug_logging_write($value[0], $value[1]);
	SERIA_Hooks::listen(SERIA_Base::DEBUG_HOOK, 'DL_debug_logging_write');
}

function DebugLogging_init()
{
	$component = SERIA_Components::getComponent('debug_logging_component_jep');
	if ($component)
		$component->embed();
}
