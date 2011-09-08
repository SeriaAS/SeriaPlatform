<?php

class SERIA_UserAuthenticationProviders
{
	protected $user;

	public function __construct(SERIA_User $user)
	{
		$this->user = $user;
	}

	protected function getProviderRefObjectCompat($authproviderClass, $unique)
	{
		$q = new SERIA_MetaQuery('SERIA_UserAuthenticationProvider', 'user = :userid AND authprovider = :providername', array('userid' => $this->user->get('id'), 'providername' => $authproviderClass));
		foreach ($q as $obj) {
			if (!$obj->get('id_unique')) {
				/* Found compat safe email object */
				SERIA_Base::debug('Got safe email provider ref object by compat (Salvaged)');
				$obj->set('id_unique', $unique);
				SERIA_Meta::save($obj);
				return $obj;
			}
		}
		return null;
	}
	protected function getProviderRefObject($authproviderClass, $unique)
	{
		if (!$unique)
			throw new SERIA_Exception('Not specified valid unique');
		$q = new SERIA_MetaQuery('SERIA_UserAuthenticationProvider', 'user = :userid AND authprovider = :providername AND id_unique = :unique', array('userid' => $this->user->get('id'), 'providername' => $authproviderClass, 'unique' => $unique));
		if ($obj = $q->limit(0, 1)->current())
			return $obj;
		else
			return $this->getProviderRefObjectCompat($authproviderClass, $unique);
	}
	protected function createProviderRefObject($authproviderClass, $unique)
	{
		$obj = new SERIA_UserAuthenticationProvider();
		$obj->set('user', $this->user);
		$obj->set('id_unique', $unique);
		$obj->set('authprovider', $authproviderClass);
		return $obj;
	}
	protected function aquireProviderRefObject($authproviderClass, $unique)
	{
		$obj = $this->getProviderRefObject($authproviderClass, $unique);
		if ($obj)
			return $obj;
		else
			return $this->createProviderRefObject($authproviderClass, $unique);
	}
	public function setProvider($authproviderClass, $unique, $email, $params)
	{
		$obj = $this->aquireProviderRefObject($authproviderClass, $unique);
		$obj->set('email', $email);
		$obj->set('params', $params);
		SERIA_Meta::save($obj);
	}
	public function getProviders()
	{
		return new SERIA_MetaQuery('SERIA_UserAuthenticationProvider', 'user = :userid', array('userid' => $this->user->get('id')));
	}
}