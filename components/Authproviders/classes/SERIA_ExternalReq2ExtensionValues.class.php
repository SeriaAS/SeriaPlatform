<?php

class SERIA_ExternalReq2ExtensionValues
{
	protected static $objects = array();
	protected $user;
	protected $values = NULL;

	const VALUES_HOOK = 'SERIA_ExternalReq2ExtensionValues::VALUES_HOOK';

	protected function __construct(SERIA_User $user)
	{
		$this->user = $user;
	}
	public static function getObject(SERIA_User $user)
	{
		$id = $user->get('id');
		if (!isset(self::$objects[$id]))
			self::$objects[$id] = new self($user);
		return self::$objects[$id];
	}

	protected function retrieveValues()
	{
		$values = array();
		$allValueArrays = SERIA_Hooks::dispatch(self::VALUES_HOOK, $this->user);
		foreach ($allValueArrays as $valueArray) {
			if ($valueArray) {
				foreach ($valueArray as $name => $value) {
					if (is_string($name))
						$values[$name] = $value;
				}
			}
		}
		return $values;
	}
	public function getValues()
	{
		if ($this->values === NULL)
			$this->values = $this->retrieveValues();
		return $this->values;
	}
}
