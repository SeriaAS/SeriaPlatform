<?php

class SERIA_SafeEmailUsers
{
	public static function registerUserEmail(&$user, $email)
	{
		$plist = SERIA_PropertyList::createObject($user);
		if ($plist->get(sha1('safeEmail:'.$email)) != 'safeEmail:'.$email) {
			$plist->set(sha1('safeEmail:'.$email), 'safeEmail:'.$email);
			$plist->save();
		}
	}
	public static function deleteUserEmail(&$user, $email)
	{
		$plist = SERIA_PropertyList::createObject($user);
		if ($plist->get(sha1('safeEmail:'.$email)) == 'safeEmail:'.$email)  {
			SERIA_Base::debug('Deleting safe-email: '.$email);
			$plist->delete(sha1('safeEmail:'.$email));
			$plist->save();
		}
	}
	public static function getUserByEmail($email)
	{
		SERIA_Base::debug('Searching for safe users with email: '.$email);
		$users = SERIA_PropertyList::query('SERIA_User', sha1('safeEmail:'.$email), 'safeEmail:'.$email);
		SERIA_Base::debug('Safe users: '.serialize($users));
		if (count($users) >= 1)
			return $users[0];
		return null;
	}
	public static function getSafeEmailAddresses(&$user)
	{
		$plist = SERIA_PropertyList::createObject($user);
		$plist = $plist->fetchAll();
		$prefix = 'safeEmail:';
		$prefixLen = strlen($prefix);
		$emails = array();
		foreach ($plist as $name => $val) {
			if (substr($val, 0, $prefixLen) == $prefix) {
				if ($name == sha1($val))
					$emails[] = substr($val, $prefixLen);
			}
		}
		return $emails;
	}
}