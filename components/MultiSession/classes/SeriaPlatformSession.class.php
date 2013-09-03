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
	public function sessionGC()
	{
		$expiry = ini_get('session.gc_maxlifetime');
		$expiry_lim = time() - $expiry;
		$unset_lim = 1800;
		if ($unset_lim > ini_get('session.gc_maxlifetime'))
			$unset_lim = ini_get('session.gc_maxlifetime');
		$unset_lim += $expiry;
		if ($this->exists(SERIA_PREFIX.'_USERTIME'.SERIA_SESSION_SUFFIX)) {
			if ($this->get(SERIA_PREFIX.'_USERTIME'.SERIA_SESSION_SUFFIX) < $expiry_lim) {
				/*
				 * Session has expired!
				 */
				$this->clearAll();
				$this->save();
			}
		} else {
			$this->set(SERIA_PREFIX.'_USERTIME'.SERIA_SESSION_SUFFIX, $unset_lim);
			$this->save();
		}
	}
}