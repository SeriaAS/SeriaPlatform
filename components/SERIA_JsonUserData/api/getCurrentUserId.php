<?php
/**
 * Get the user data as JSON.
 * Parameters:
 *   user_id
 * Returns:
 *   array(name => value)
 */

require_once(dirname(__FILE__).'/../../../main.php');

SERIA_ProxyServer::noCache();

$user = SERIA_Base::user();
SERIA_Lib::publishJSON($user ? $user->get('id') : false);
