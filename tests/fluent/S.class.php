<?php
	/**
	*	This is the base class for Seria Platform. It has no purpose at the moment.
	*
	*	To access various resources use the following examples:
	*
	*	$db = SDB::db();
	*	$user = SUser::user();
	*	$cache = SCache::cache('my_namespace');
	*	$allUsers = SFluent::all('SUser');
	*	$adminUsers = SFluent::all('SUser')->where('is_administrator=1');
	*
	*	For lower level access to database tables:
	*	$usersTable = new SDBData('{users}');
	*/
	class S
	{
	}
