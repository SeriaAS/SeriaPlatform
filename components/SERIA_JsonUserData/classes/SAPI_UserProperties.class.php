<?php

class SAPI_UserProperties extends SAPI
{
	public static function setUserProperty($namespace, $name, $value)
	{
		if (($user = SERIA_Base::user())) {
			$storage = new SERIA_JsonUserPropertyStorageDriver($user);
			$storage->set($namespace, $name, $value);
			return true;
		} else
			return false;
	}
	public static function setUserPropertyBatch($namespace, $batch)
	{
		if (is_string($batch))
			$batch = json_decode($batch, true);
		if (($user = SERIA_Base::user())) {
			$storage = new SERIA_JsonUserPropertyStorageDriver($user);
			foreach ($batch as $name => $value)
				$storage->set($namespace, $name, $value);
			return true;
		} else
			return false;
	}
	public static function getAllUserProperties($namespace)
	{
		if (($user = SERIA_Base::user())) {
			$storage = new SERIA_JsonUserPropertyStorageDriver($user);
			$data = $storage->getAll($_REQUEST['namespace']);
			return $data;
		} else
			return false;
	}
	public static function deleteUserProperty($namespace, $name)
	{
		if (($user = SERIA_Base::user())) {
			$storage = new SERIA_JsonUserPropertyStorageDriver($user);
			$storage->delete($namespace, $name);
			return true;
		} else
			return false;
	}
}