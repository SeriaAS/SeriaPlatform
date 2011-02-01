<?php

	class RegisteredViewer extends SERIA_MetaObject {
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{registered_viewers}',
				'displayName' => 'registeredviewer',
				'fields' => array(
					'name' => array('name required', _t('Name')),
					'email' => array('email required', _t('E-Mail')),
					'usedPassword' => array('boolean required', _t('Used password')),
					'sentAsEmail' => array('boolean required', _t('Sent as email')),
					'presentationId' => array('integer required', _t('Presentation ID')),
					'accessToken' => array('name', _t('Accesstoken')),
					'createdDate' => 'createdDate',
					'createdBy' => 'createdBy',
					'modifiedDate' => 'modifiedDate',
					'modifiedBy' => 'modifiedBy',
				)
			);
		}

		public function editAction()
		{
			$form = SERIA_Meta::editAction('edit_registeredViewer', $this, array(
				'name',
				'email',
				'usedPassword',
				'sentAsEmail',
				'presentationId',
				'accessToken',
			));

			return $form;
		}
	}
