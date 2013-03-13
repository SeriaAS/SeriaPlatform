<?php

class SAPI_OutboardDeleteMe extends SAPI
{
	public static function post_detachMyComments()
	{
		if (!SERIA_Base::user())
			throw new SERIA_Exception('Not logged in!');
		$user = SERIA_Base::user();
		SERIA_Comment::deleteUserHook($user);
		return true;
	}
	public static function delete_detachMyComments()
	{
		return self::post_detachMyComments();
	}
	public static function get_detachMyComments()
	{
		throw new SERIA_Exception('Operation cannot be done by HTTP GET');
	}
	public static function put_detachMyComments()
	{
		throw new SERIA_Exception('Operation cannot be done by HTTP PUT');
	}
}