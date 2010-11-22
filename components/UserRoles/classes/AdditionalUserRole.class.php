<?php

/**
 * This is a database table where roles that is locally unknown are
 * automatically (or manually) added. So that they can be seen in the
 * user interface.
 *
 * @author Jan-Espen Pettersen
 *
 */
class AdditionalUserRole extends SERIA_MetaObject
{
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{replicated_user_role}',
				'primaryKey' => 'role',
				'fields' => array(
					'role' => array('name required unique', _t('Role name')),
					'caption' => array('name required', _t('Role caption')),
				),
			);
		}
}