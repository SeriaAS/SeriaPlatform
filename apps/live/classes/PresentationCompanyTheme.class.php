<?php
	/**
	* PresentationCompanyTheme
	* Producer producer
	* PresentationCompany company
	*
	* Creates a link between a company and a producer, this link contains theme information (background image, top image and colors)
	*
	* The theme can then be used in a presentation to change the view of the player.
	*
	*/

	class PresentationCompanyTheme extends SERIA_MetaObject {
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{presentation_company_themes}',
				'displayName' => 'name',
				'displayField' => 'name',
				'fields' => array(
					'name' => array('name required', _t('Name of theme')),
					'producer' => array('Producer required', _t('Owner')),
					'presentation_company' => array('PresentationCompany required', _t('Company')),
					'theme_background' => array('SERIA_File required', _t("Theme background"), array('validator' => new SERIA_Validator(array(array(SERIA_Validator::INTEGER))))),
					'theme_color' => array('hexcolor', _t("Theme color")),
					'theme_topimage' => array('SERIA_File required', _t("Top image"), array('validator' => new SERIA_Validator(array(array(SERIA_Validator::INTEGER))))),
					'createdDate' => 'createdDate',
					'createdBy' => 'createdBy',
					'modifiedDate' => 'modifiedDate',
					'modifiedBy' => 'modifiedBy',
				)
			);
		}

		public function editAction()
		{
			$form = SERIA_Meta::editAction('edit_presentation_company_theme', $this, array(
				'themename',
				'producer',
				'presentationCompany',
				'theme_color',
				'theme_background',
				'theme_topimage',
			));

			return $form;
		}
	}
