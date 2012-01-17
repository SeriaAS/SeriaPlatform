<?php

class SAPI_EsiFrontend extends SAPI
{
	/**
	 *
	 * Delete the cached data by the EsiFrontend application.
	 */
	public static function deleteCache()
	{
		$cache = new SERIA_Cache('Esi');
		$cache->deleteAll();
		$cache = new SERIA_Cache('OR_EsiHtmlTokenCompiler');
		$cache->deleteAll();
		return true;
	}
	public static function get_deleteCache()
	{
		return array('error' => 'Not available as GET-request!');
	}
	public static function put_deleteCache()
	{
		return array('error' => 'Not available as PUT-request!');
	}
	/**
	 *
	 * Accept DELETE ?api=SAPI_EsiFrontend/cache
	 */
	public static function delete_cache()
	{
		return self::deleteCache();
	}
}
