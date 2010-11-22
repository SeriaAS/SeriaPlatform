<?php
	class SERIA_AmazonCloudServiceProvider extends SERIA_MetaObject implements SERIA_CloudServiceProvider
	{
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{aws_accounts}',
				'fields' => array(
					'name' => array('name required', _t('Account name')),
					'aws_key' => array('name required', '<a href="http://aws-portal.amazon.com/gp/aws/developer/account/index.html?ie=UTF8&action=access-key" target="_blank">'._t("Key").'</a>'),
					'aws_secret_key' => array('name required', '<a href="http://aws-portal.amazon.com/gp/aws/developer/account/index.html?ie=UTF8&action=access-key" target="_blank">'._t("Secret key").'</a>'),
					'aws_account_id' => array('name required', '<a href="http://aws-portal.amazon.com/gp/aws/developer/account/index.html?ie=UTF8&action=access-key" target="_blank">'._t("Account ID").'</a>'),
					'aws_assoc_id' => array('name','<a href="http://affiliate-program.amazon.com/gp/associates/join/" target="_blank">'._t("Referral Amazon Associates ID").'</a>'),
					'aws_api_url' => array('url required', _t("Complete endpoint URL")),
				),
			);
		}

		public function editForm($formId)
		{
			return SERIA_Meta::editAction($formId, $this, array('name','aws_key','aws_secret_key','aws_account_id','aws_assoc_id','aws_api_url'));
		}

		public static function getProviders()
		{
			return SERIA_Meta::all('SERIA_AmazonCloudServiceProvider');
		}

		public static function guiEmbed($gui)
		{
			$gui->addMenuItem('controlpanel/settings/services/cloud/amazon', _t('Amazon Web Services'), _t("Manage Amazon accounts"), SERIA_HTTP_ROOT.'/seria/components/SERIA_AmazonCloudServiceProvider/');
		}

		public function getInfo()
		{
			return array(
				'url' => SERIA_HTTP_ROOT.'/seria/components/SERIA_AmazonCloudServiceProvider/edit.php?id='.$this->get('id'),
				'serviceName' => _t('Amazon EC2'), 
				'accountName' => $this->get('name')
			);
		}
	}
