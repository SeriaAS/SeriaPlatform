<?php
	class SERIA_OutboardTestEntity extends SERIA_MetaObject
	{
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{outboard_test_entities}',
				'fields' => array(
					'name' => array('name required', _t('Name')),
				),
			);
		}

		public function editAction()
		{
			return SERIA_Meta::editAction('edit', $this, array('name'));
		}
	}
