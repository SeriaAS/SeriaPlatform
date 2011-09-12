<?php

class FacebookLoginManager extends SERIA_GenericAuthproviderLogin
{
	public function getIdentityPropertyName()
	{
		/* Compatibility */
		return 'simplesamlUser:';
	}
}