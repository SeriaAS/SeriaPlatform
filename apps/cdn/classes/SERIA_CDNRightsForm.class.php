<?php

	class SERIA_CDNRightsForm extends SERIA_Form
	{
		private $user;

		function __construct($user)
		{
			$this->user = $user;
		}

		public static function caption()
		{
			return _t("Seria CDN Rights");
		}

		public static function getFormSpec()
		{
			return array(
				'cdn_access' => array(
					'caption' => _t('Allow user to access Seria CDN management'),
					'fieldtype' => 'checkbox',
				),
				'cdn_edit_servers' => array(
					'caption' => _t('Allow user to add and remove server IP addresses'),
					'fieldtype' => 'checkbox',
				),
				'cdn_edit_servers_autohosts' => array(
					'caption' => _t('Allow user to set an IP address to automatically add hostnames'),
					'fieldtype' => 'checkbox',
				),
				'cdn_edit_hostnames' => array(
					'caption' => _t('Allow user to manage hostnames for his IP addresses'),
					'fieldtype' => 'checkbox',
				),
				'cdn_view_statistics' => array(
					'caption' => _t('Allow user to view statistics for his hostnames'),
					'fieldtype' => 'checkbox',
				),
			);
		}

		public function _handle($data)
		{
			if($this->user->isAdministrator()) return;
			foreach($this->getFieldSpec() as $right => $info) {
				$this->user->setRight($right, isset($data[$right]));
			}
			$this->user->save();
		}
		
		public function get($fieldName)
		{
			return $this->user->hasRight($fieldName);
		}
	}
