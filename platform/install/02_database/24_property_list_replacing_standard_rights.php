<?php
	/**
	 * Retrieves all the users in the current account and transfers all existing possible rights into
	 * propertylist right:$rightName instead.
	 *
	 * Rights: 
	 **/

	$db = SERIA_Base::db();
	$users_ids = $db->query('SELECT id FROM {users} WHERE is_administrator<>1', array())->fetchAll(PDO::FETCH_COLUMN, 0);

	if(sizeof($users_ids)) foreach($users_ids as $user_id)
	{
		$current_rights = $db->query('SELECT right_id from {user_rights} where user_id=:id', array('id' => $user_id))->fetchAll(PDO::FETCH_COLUMN,0);

		if(sizeof($current_rights))
		{
			$property_list = SERIA_PropertyList::createObject(SERIA_User::createObject($user_id));
	
			foreach($current_rights as $current_right_id)
			{
				$type_name = $db->query('SELECT type from {rights} where id=:right_id', array('right_id' => $current_right_id))->fetch(PDO::FETCH_COLUMN,0);
				// If it exists as a row in $current_rights it means the user has the right.
				$property_list->set('right:'.$type_name, '1');
				
			}
			$property_list->save();
		}			
	}
	$db->exec('DROP TABLE {guid_accesstable}');
	$db->exec('DROP TABLE {authplugin_autoaddgroup}');
	$db->exec('DROP TABLE {user_group}');
	$db->exec('DROP TABLE {user_group_rights}');
	$db->exec('DROP TABLE {user_groups}');
	$db->exec('DROP TABLE {user_rights}');
	$db->exec('DROP TABLE {rights}');
