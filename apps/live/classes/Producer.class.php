<?php
	/**
	* Producer
	* name String
	* orgNumber int
	* billingName ( recipient )
	* billingAddress
	* billingZip
	* billingPhone
	* billingNote
	*
	* currentBlockPrice
	* currentBlockSize
	*
	* A producer is a production company or a regular company holding presentations.
	*
	*/

	class Producer extends SERIA_MetaObject implements SERIA_IMetaField
	{
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{producers}',
				'displayField' => 'name',
				'fields' => array(
					'name' => array('name required', _t('Company name')),
					'orgNumber' => array('integer', _t('Organization number')),
					'billingName' => array('name required', _t('Contact name')),
					'billingAddress' => array('text required', _t('Address')),
					'billingZip' => array('integer required', _t('Zipcode')),
					'billingPhone' => array('integer required', _t('Phone')),
					'billingNote' => array('text', _t('Note')),
					'currentBlockSize' => array('integer required', _t('Size per block')),
					'currentBlockPrice' => array('integer required', _t('Price per block')),
					'createdDate' => 'createdDate',
					'createdBy' => 'createdBy',
					'modifiedDate' => 'modifiedDate',
					'modifiedBy' => 'modifiedBy',
				)
			);
		}

		public function editAction()
		{
			$form = SERIA_Meta::editAction('edit_produceruser', $this, array(
				'name',
				'orgNumber',
				'billingName',
				'billingAddress',
				'billingZip',
				'billingPhone',
				'billingNote',
				'currentBlockPrice',
				'currentBlockSize'
			));

			return $form;
		}

		public static function createFromUser($value)
		{
			try
			{
				return SERIA_Meta::load('Producer', $value);
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
			$producers = SERIA_Meta::all('Producer');

			foreach($producers as $producer)
				$r.= '<option value="'.$producer->get("id").'"'.($producer->get('id')===$value?' selected="selected"':'').'>'.$producer->get("name").'</option>';

			$r .= '</select>';

			return $r;
		}
		public static function createFromDb($value)
		{
			return SERIA_Meta::load('Producer', $value);
		}
		public function toDbFieldValue()
		{
			return $this->get("id");
		}
		public static function MetaField()
		{
			return array('type'=>'integer', 'class' => 'Producer');
		}
		public function getTotalBlocksByMonth($month, $year=2011)
		{
			return SERIA_Base::db()->query('SELECT SUM(blockHours) from {blockhours} WHERE customerId=:customerId AND month=:month AND year=:year', array(
				'customerId' => $this->get("id"),
				'month' => $month,
				'year' => $year
			))->fetch(PDO::FETCH_COLUMN);
		}
	}
