<?php
	/**
	* PresentationCompany
	* name String
	* orgNumber int
	* contactName
	*
	* A PresentationCompany is a production company or a regular company holding presentations.
	*
	*/

	class PresentationCompany extends SERIA_MetaObject implements SERIA_IMetaField
	{
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{presentation_companies}',
				'displayField' => 'name',
				'fields' => array(
					'name' => array('name required', _t('Company name')),
					'orgNumber' => array('integer', _t('Organization number')),
					'contactName' => array('name required', _t('Contact name')),
					'contactPhone' => array('phone required', _t('Phone number')),
					'contactNote' => array('text', _t('Note')),
					'currentTheme' => array('PresentationCompanyTheme', _t("Company theme")),
					'createdDate' => 'createdDate',
					'createdBy' => 'createdBy',
					'modifiedDate' => 'modifiedDate',
					'modifiedBy' => 'modifiedBy',
				)
			);
		}

		public function editAction()
		{
			$form = SERIA_Meta::editAction('edit_presentation_companies', $this, array(
				'name',
				'orgNumber',
				'contactName',
				'contactPhone',
				'contactNote',
				'currentTheme',
			));

			return $form;
		}

		public static function createFromUser($value)
		{
			try
			{
				return SERIA_Meta::load('PresentationCompany', $value);
			}
			catch (SERIA_Exception $e)
			{
				return NULL;
			}
		}
		public static function renderFormField($fieldName, $value, array $params=NULL, $hasError=false)
		{
			if($value!==NULL) $value = $value->get('id');
			$r = '<select id="'.$fieldName.'" name="'.$fieldName.'" class="select'.($hasError?' ui-state-error':'').'"><option></option>';
			$companies = SERIA_Meta::all('PresentationCompany');

			foreach($companies as $company)
				$r.= '<option value="'.$company->get("id").'"'.($company->get('id')===$value?' selected="selected"':'').'>'.$company->get("name").'</option>';

			$r .= '</select>';

			return $r;
		}
		public static function createFromDb($value)
		{
			return SERIA_Meta::load('PresentationCompany', $value);
		}
		public function toDbFieldValue()
		{
			return $this->get("id");
		}
		public static function MetaField()
		{
			return array('type'=>'integer', 'class' => 'PresentationCompany');
		}
/*
		public function getThemesFromProducerUser($producerUser)
		{
			$themeQuery = SERIA_Meta::all('PresentationCompanyTheme')->where('presentationCompany='.$this->get("id"))->where('producer='.$producerUser->get("producer")->get("id"));

			return $themeQuery;
		}
*/
		public function getThemes()
		{
			$producerUser = ProducerUser::getProducerUserByUser(SERIA_Base::user());
			$themeQuery = SERIA_Meta::all('PresentationCompanyTheme')->where('presentation_company='.$this->get("id"))->where('producer='.$producerUser->get("producer")->get("id"));

			return $themeQuery;
		}
	}
