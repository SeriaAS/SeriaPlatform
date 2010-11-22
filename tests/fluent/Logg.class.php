<?php
	class Logg extends SERIA_FluentObject {

		public static function Fluent($instance=NULL) {
			return array(
				'table' => 'logg',
				'fields' => array(
					'ansatt' => 'createdBy',
					'navn' => array('name required unique', _t('Kundens navn')),
					'telefon' => array('phone', _t('Telefon')),
					'adresse' => array('address', _t('Adresse')),
					'land' => array('country', _t('Land')),
					'gender' => array('gender', _t('Kjonn')),
				),
			);
		}
	}
	SERIA_Fluent::_syncColumnSpec(SERIA_Fluent::_getSpec('Logg'));
