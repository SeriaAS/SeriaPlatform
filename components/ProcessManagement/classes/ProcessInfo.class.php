<?php

class ProcessInfo
{
	protected $systemType;
	protected $data;

	protected function __construct($systemType, $data)
	{
		$this->systemType = $systemType;
		$this->data = $data;
	}

	public static function createWin32ProcessInfo($processName, $pid, $sessionName, $session, $memoryUsageString)
	{
		return new self('Windows', array(
			'processName' => $processName,
			'pid' => $pid,
			'sessionName' => $sessionName,
			'session' => $session,
			'memoryUsageString' => $memoryUsageString
		));
	}

	public function get($name)
	{
		if (isset($this->data[$name]))
			return $this->data[$name];
		else
			return null;
	}

	public function kill($force=false)
	{
		if (!$this->get('pid') || intval($this->get('pid'), 10) != $this->get('pid'))
			throw new SERIA_Exception('Pid number is not available or is not useable.');
		$pid = intval($this->get('pid'), 10);
		switch ($this->systemType) {
			case 'Windows':
				if (!$force)
					shell_exec('Taskkill /PID '.$pid);
				else
					shell_exec('Taskkill /F /PID '.$pid);
				break;
			default:
				throw new SERIA_Exception('Kill is not supported on this platform.');
		}
	}
}