<?php

	class AccessToken extends SERIA_MetaObject {
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{access_tokens}',
				'displayName' => 'accesstoken',
				'fields' => array(
					'token' => array('name required', _t('Name')),
					'presentationId' => array('integer required', _t("Presentation")),
					'used' => array('integer required', _t('Used')),
					'createdDate' => 'createdDate',
					'createdBy' => 'createdBy',
					'modifiedDate' => 'modifiedDate',
					'modifiedBy' => 'modifiedBy',
				)
			);
		}

		public function editAction()
		{
			$form = SERIA_Meta::editAction('edit_accesstoken', $this, array(
				'token',
				'used',
			));

			return $form;
		}
	}
