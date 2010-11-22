<?php

	class SERIA_PublisherRightsForm extends SERIA_Form
	{
		private $user;

		function __construct($user)
		{
			$this->user = $user;
		}

		public static function caption()
		{
			return _t("Seria Publisher Rights");
		}

		public static function getFormSpec()
		{
			return array(
				'view_other_articles' => array(
					'caption' => _t('Allow user to view others articles'),
					'fieldtype' => 'checkbox',
					'helptext' => _t('Please choose a value'),
				),
				'create_article' => array(
					'caption' => _t('Allow user to create articles'),
					'fieldtype' => 'checkbox',
					'helptext' => _t('Please choose a value'),
				),
				'delete_others_articles' => array(
					'caption' => _t('Allow user to delete others articles'),
					'fieldtype' => 'checkbox',
					'helptext' => _t('Please choose a value'),
				),
				'publish_article' => array(
					'caption' => _t('Allow user to publish articles'),
					'fieldtype' => 'checkbox',
					'helptext' => _t('Please choose a value'),
				),
				'edit_others_articles' => array(
					'caption' => _t('Allow user to edit others articles'),
					'fieldtype' => 'checkbox',
					'helptext' => _t('Please choose a value'),
				),
				'edit_categories' => array(
					'caption' => _t('Allow user to edit categories'),
					'fieldtype' => 'checkbox',
					'helptext' => _t('Please choose a value'),
				),
			);
		}

		public function _handle($data)
		{
			if($this->user->isAdministrator()) return;
			foreach($this->getFieldSpec() as $right => $info) {
				$this->user->setRight($right, !empty($data[$right]));
			}
			$this->user->save();
		}
		
		public function get($fieldName)
		{
			return $this->user->hasRight($fieldName);
		}
	}
