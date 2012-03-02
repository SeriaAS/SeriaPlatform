<?php

	define('SESSION_CLOSE_HOOK', 'session_closing_hook');

	/**
	*	Functions for sending headers to the browser regarding caching. If you, for any reason, need to ensure the page is NOT cached you can call
	*	seria_headers_nocache() at any time, and the page will not be cached.
	*/
	function seria_headers_nocache()
	{
		return SERIA_ProxyServer::noCache();
	}

	function seria_headers_privatecache($ttl=60)
	{
		return SERIA_ProxyServer::privateCache($ttl);
	}

	function seria_headers_publiccache($ttl=60)
	{
		return SERIA_ProxyServer::publicCache($ttl);
	}

	function seria_headers_publiccache_init()
	{
		return SERIA_ProxyServer::init();
	}

	/**
	*	Below code essentially does this:
	*	1. If there are signs of a session existing (cookie or get-param), and $seria_options['skip_session'] is not set
	*		1. If $seria_options['cache_expire'] specifically; cache the page for cache_expire seconds
	*		2. else, never cache the page
	*	2. else, if there exists POST or FILES-data with this request - never cache
	*	3. else always cache the page. 
	*/

	if(!($seria_options['skip_session']) && (isset($_GET[get_cfg_var('session.name')]) || isset($_COOKIE[get_cfg_var('session.name')])))
	{ // session has not explicitly been stopped, and a session id seem to exist in either COOKIE or GET

		// the session is sent trough GET-variable, possibly from a Flash or Silverlight application.
		if(!empty($_GET[get_cfg_var('session.name')]))
			session_id($_GET[get_cfg_var('session.name')]);

		if(isset($seria_options) && isset($seria_options['cache_expire']) && intval($seria_options['cache_expire'])>0)
		{ // private caching
			seria_headers_privatecache(intval($seria_options['cache_expire']));
		}
		else
		{ // no caching
			seria_headers_privatecache(null);
		}
		session_start();
	}
	else if((isset($_POST) && sizeof($_POST)>0) || (isset($_FILES) && sizeof($_FILES)>0))
	{ // never cache this page, since it contains $_POST or $_FILES
		seria_headers_nocache();
	}
	else
	{ // always cache this page
		if($seria_options["cache_expire"]>0)
			seria_headers_publiccache($seria_options["cache_expire"]);
		else
			seria_headers_publiccache_init();
	}

	// PHP 5.3 destroys objects before closing the session. This will be a problem for most custom session handlers written in Seria Platform as we normally use objects.
	// this ensures session_write_close is called before object destruction
	function seria_call_session_write_close()
	{
		SERIA_Hooks::dispatch(SESSION_CLOSE_HOOK);
		SERIA_Base::debug('Closing session..');
		session_write_close();
	}
	register_shutdown_function('seria_call_session_write_close');

