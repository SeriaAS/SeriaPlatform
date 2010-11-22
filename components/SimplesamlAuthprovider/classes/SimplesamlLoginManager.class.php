<?php

class SimplesamlLoginManager extends SERIA_GenericAuthproviderLogin
{
	public function getIdentityPropertyName()
	{
		return 'simplesamlUser:'.$authproviderId;
	}
}