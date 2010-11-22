<?php
	require_once('SERIA_FluentObject.class.php');
	class Customer extends SERIA_FluentObject {

		public static function Fluent($instance = NULL)
		{
			return array(
				'table' => '{customers}',
				'fields' => array(
					'name' => array('name required unique', _t('Name')),
					'address' => array('address', _t('Address')),
					'country' => array('country', _t('Country')),
					'mailAddress' => array('address', _t('Mailing address')),
					'mailCountry' => array('country', _t('Mailing country')),
					'invoiceAddress' => array('address', _t('Invoice address')),
					'invoiceCountry' => array('country', _t('Invoice country')),
					'eMail' => array('email', _t("E-mail")),
					'phone' => array('phone', _t('Phone')),
					'fax' => array('phone', _t('Fax')),
					'website' => array('url', _t('Website')),
					'createdBy' => 'createdBy',
					'createdDate' => 'createdDate',
					'alteredBy' => 'modifiedBy',
					'alteredDate' => 'modifiedDate',
					'established' => array('birthdate', _('Established')),
					'employee' => array('SERIA_User', _t('Ansatt')),
					'cd' => array('CD', _t('CD')),
				),

			);
		}

	}












	SERIA_Fluent::_syncColumnSpec(SERIA_Fluent::_getSpec('Customer'));
