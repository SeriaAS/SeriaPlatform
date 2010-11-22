<?php
/**
*	30.1.2010, Frode: Modified this update script as it fails on some installations. Notably in cases when the name of the foreign key ends with _2 instead of _1
*/
	try
	{
		SERIA_Base::db()->exec('ALTER TABLE '.SERIA_PREFIX.'_users DROP FOREIGN KEY `seria_users_ibfk_1`');
	}
	catch (PDOException $e)
	{
		try {
			SERIA_Base::db()->exec('ALTER TABLE '.SERIA_PREFIX.'_users DROP FOREIGN KEY `seria_users_ibfk_2`');
		} catch (PDOException $e) {}
	}
	try
	{ // due to bug this column might already have been dropped
		SERIA_Base::db()->exec('ALTER TABLE '.SERIA_PREFIX.'_users DROP COLUMN authplugin_id');
	} catch (PDOException $e) {}
	try { SERIA_Base::db()->exec('DROP TABLE '.SERIA_PREFIX.'_authplugin_settings'); } catch (PDOException $e) {}
	try { SERIA_Base::db()->exec('DROP TABLE '.SERIA_PREFIX.'_authplugins'); } catch (PDOException $e) {}



