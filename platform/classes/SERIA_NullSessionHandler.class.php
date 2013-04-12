<?php

/*
 * Interface is missing on php <5.4
 */
if (PHP_VERSION_ID < 50400 && !interface_exists('SessionHandlerInterface')) {
	interface SessionHandlerInterface
	{
	}
}

/**
 * 
 * Null-handler for session. Load this session-handler to prevent php from storing session data.
 * The session data will be valid for this request only.
 * @author Jan-Espen
 *
 */
class SERIA_NullSessionHandler implements SessionHandlerInterface
{
	public function open($savePath, $sessionName)
	{
		return true;
	}

	public function close()
	{
		return true;
	}

	public function read($id)
	{
		return '';
	}

	public function write($id, $data)
	{
		return true;
	}

	public function destroy($id)
	{
		return true;
	}

	public function gc($maxlifetime)
	{
		return true;
	}

	public static function activateSessionHandler($handler=null)
	{
		if ($handler === null)
			$handler = new self();

		$sessid = session_id();
		if ($sessid)
			session_write_close();

		/*
		 * OOP-enabled session-handler on php 5.4 and later.
		 */
		if (PHP_VERSION_ID >= 50400)
			session_set_save_handler($handler, true);
		else
			session_set_save_handler(
				array($handler, 'open'),
				array($handler, 'close'),
				array($handler, 'read'),
				array($handler, 'write'),
				array($handler, 'destroy'),
				array($handler, 'gc')
			);

		session_id($sessid);
		session_start();
	}
}
