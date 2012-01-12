<?php

class SERIA_JsonUserPropertyStorageDriver
{
	protected $user;

	/**
	 *
	 * Create a new storage for this user.
	 * @param SERIA_User $user
	 */
	public function __construct(SERIA_User $user)
	{
		$this->user = $user;		
	}

	/**
	 *
	 * Get all values belonging to this namespace.
	 * @param string $namespace
	 * @return array
	 */
	public function getAll($namespace)
	{
		$values = array();
		$candidates = $this->user->getAllMetaExtended();
		foreach ($candidates as $info) {
			$candidate = $info['name'];
			if (substr($candidate, 0, 10) != 'JsonUserP_')
				continue;
			$candidate = substr($candidate, 10);
			if (substr($candidate, 0, strlen($namespace)) != $namespace)
				continue;
			$candidate = substr($candidate, strlen($namespace));
			if (substr($candidate, 0, 10) != '_JsonUsPr_')
				continue;
			$candidate = substr($candidate, 10);

			if ($candidate !== '' && $info['value'])
				$values[$candidate] = unserialize($info['value']);
		}
		return $values;
	}
	/**
	 *
	 * Set a value.
	 * @param string $namespace
	 * @param string $name
	 * @param mixed $value
	 */
	public function set($namespace, $name, $value)
	{
		$this->user->setMeta('JsonUserP_'.$namespace.'_JsonUsPr_'.$name, serialize($value));
	}
	/**
	 *
	 * Get a value.
	 * @param string $namespace
	 * @param string $name
	 * @return mixed
	 */
	public function get($namespace, $name)
	{
		$value = $this->user->getMeta('JsonUserP_'.$namespace.'_JsonUsPr_'.$name);
		if ($value)
			return unserialize($value);
	}
	/**
	 *
	 * Delete a value (unset)
	 * @param string $namespace
	 * @param string $name
	 */
	public function delete($namespace, $name)
	{
		$this->user->setMeta('JsonUserP_'.$namespace.'_JsonUsPr_'.$name, '');
	}
}