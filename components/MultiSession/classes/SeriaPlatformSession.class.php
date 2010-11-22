<?php

/**
 * Read various Seria-platform data from a session.
 *
 * @author Jan-Espen Pettersen
 *
 */
class SeriaPlatformSession extends OfflineSession
{
	/**
	 * Returns the logged in user for this session.
	 *
	 * @return SERIA_User
	 */
	public function getUser()
	{
		if ($this->exists(SERIA_PREFIX.'_USERID'.SERIA_SESSION_SUFFIX)) {
			$uid = $this->get(SERIA_PREFIX.'_USERID'.SERIA_SESSION_SUFFIX);
			if ($uid)
				return SERIA_User::createObject($uid);
		}
		return false;
	}
}