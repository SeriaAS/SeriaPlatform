<?php

class SERIA_AsyncUserMetaSync
{
	const CLOCK_SKEW_BUFFER = 120;

	public static function doSync(SERIA_User $user, array $providers)
	{
		foreach ($providers as $provider) {
			SERIA_Base::debug('Updating with '.$provider->getProviderId().' for user '.$user->get('id'));
			$provider->updateUserMeta($user);
		}
	}
	public static function getUpdatedUsers($fromTime)
	{
		$users = SERIA_Base::db()->query('SELECT DISTINCT owner FROM {user_meta_value} WHERE timestamp >= :fromTime', array('fromTime' => date('Y-m-d H:i:s', $fromTime)))->fetchAll(PDO::FETCH_COLUMN, 0);
		$invalid = array();
		foreach ($users as $key => &$user) {
			try {
				$user = SERIA_User::createObject($user);
			} catch (SERIA_NotFoundException $e) {
				/* OOps - values are not attached to an existing user */
				$invalid[] = $key;
			}
		}
		foreach ($invalid as $key)
			unset($users[$key]);
		return $users;
	}
	public static function updateAllUsers()
	{
		SERIA_Authproviders::loadProviders('SERIA_ExternalAuthprovider');
		$providers = SERIA_Authproviders::getProviders();
		foreach ($providers as $key => $provider) {
			if (get_class($provider) != 'SERIA_ExternalAuthprovider' || !$provider->isAvailable())
				unset($providers[$key]);
		}
		$startTime = time();
		$updateMark = SERIA_Base::getParam('SERIA_AsyncUserMetaSync::syncTime');
		if ($updateMark && $updateMark > self::CLOCK_SKEW_BUFFER)
			$updateMark -= self::CLOCK_SKEW_BUFFER;
		else
			$updateMark = 0;
		SERIA_Base::debug('Updating user-meta-values with update point: '.date('Y-m-d H:i:s', $updateMark));
		$users = self::getUpdatedUsers($updateMark);
		foreach ($users as $user)
			self::doSync($user, $providers);
		SERIA_Base::setParam('SERIA_AsyncUserMetaSync::syncTime', $startTime);
	}
}