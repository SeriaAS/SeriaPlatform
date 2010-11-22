<?php

class ProcessInfoList
{
	protected $objects;

	public function __construct($processListArray)
	{
		$this->objects = $processListArray;
	}

	public function toArray()
	{
		return $this->objects;
	}
	public function getProcessesByName($name)
	{
		$processes = array();
		foreach ($this->objects as $obj) {
			if ($obj->get('processName') == $name)
				$processes[] = $obj;
		}
		return $processes;
	}
	public function getProcess($pid)
	{
		foreach ($this->objects as $obj) {
			if ($obj->get('pid') == $pid)
				return $obj;
		}
		return null;
	}
}