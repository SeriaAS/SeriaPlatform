<?php

	class SERIA_LiveRightsForm extends SERIA_Form
	{
		private $user;

		function __construct($user)
		{
			$this->user = $user;
		}

		public static function caption()
		{
			return _t("Seria Live Rights");
		}

		public static function getFormSpec()
		{
			return array(
				'live_chapters' => array(
					'caption' => _t('Allow user to use Chapters'),
					'fieldtype' => 'checkbox',
					'helptext' => _t('Please choose a value'),
				),
				'live_quality' => array(
					'caption' => _t('Max streaming bandwidth'),
					'fieldtype' => 'select',
					'options' => array(
						'ultrahigh' => 'Very high quality',
						'high' => 'High quality',
						'medium' => 'Medium quality',
						'low' => 'Low quality',
						'ultralow' => 'Very low quality'
					),
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
					)),
					'helptext' => _t('Please choose a value'),
				),
				'live_presentation_foils' => array(
					'caption' => _t('Allow user to use Presentation Foils'),
					'fieldtype' => 'checkbox',
					'helptext' => _t('Please choose a value'),
				),
			);
		}

		public function _handle($data)
		{
			if($this->user->isAdministrator()) return;

			foreach($this->getFieldSpec() as $right => $info) {
				switch($right)
				{
					case 'live_chapters' : 
					case 'live_presentation_foils' :
						$this->user->setRight($right, $data[$right]);
						break;
					case 'live_quality' : 
						SERIA_PropertyList::createObject($this->user)->set('live_quality', $data['live_quality']);
						break;
				}
			}
			$this->user->save();
		}

		public function get($fieldName)
		{
			switch($fieldName)
			{
				case 'live_chapters' : case 'live_presentation_foils' :
					return $this->user->hasRight($fieldName);
					break;
				default :
					return SERIA_PropertyList::createObject($this->user)->get($fieldName);
					break;
			}
		}
	}
