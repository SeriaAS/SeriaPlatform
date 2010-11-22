<?php

/**
 * 
 * I would definitely not have written this database model like this if I could. But because of
 * limitations in SERIA_MetaGrid I need to have a database model that is strictly consistent
 * with how exactly this is shown to the user. (There has to be a one to one relationship between
 * rows in the database and rows shown to the user). I would have had one row for each invokation
 * of the sync operation, but here I have one row for each hour (0-23) and boolean fields for
 * invokation or not. I would have liked to have a database that is tied more to the logic
 * of this component, and not to the graphical representation.
 *
 * @author janespen
 *
 */
class NDLA_WeeklySyncSchedule extends SERIA_MetaObject
{
	public static function Meta($instance = null)
	{
		return array(
			'table' => '{ndla_weekly_sync_schedule}',
			'fields' => array(
				'hour' => array(
					'caption' => _t('Hour'),
					'fieldtype' => 'select',
					'type' => 'int(11)',
					'values' => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23),
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::ONE_OF, array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23)),
					))
				),
				'Monday' => array('boolean', _t('Monday')),
				'Tuesday' => array('boolean', _t('Tuesday')),
				'Wednesday' => array('boolean', _t('Wednesday')),
				'Thursday' => array('boolean', _t('Thursday')),
				'Friday' => array('boolean', _t('Friday')),
				'Saturday' => array('boolean', _t('Saturday')),
				'Sunday' => array('boolean', _t('Sunday'))
			)
		);
	}

	/**
	 *
	 * Generates the table content if not done.
	 * @return SERIA_MetaQuery A query with correct order.
	 */
	public static function generate()
	{
		static $serial = 0;

		$createdRows = 0;
		SERIA_Base::debug((++$serial).': Generating weekly schedule');
		for ($hour = 0; $hour < 24; $hour++) {
			$sh = SERIA_Meta::all('NDLA_WeeklySyncSchedule')->where('hour = :hour', array('hour' => $hour));
			switch ($sh->count()) {
				case 1:
					SERIA_Base::debug((++$serial).': Found hour row: '.$hour);
					break;
				case 0:
					SERIA_Base::debug((++$serial).': Did not find hour row: '.$hour);
					$sh = new NDLA_WeeklySyncSchedule();
					$sh->set('hour', $hour);
					$sh->set('Monday', false);
					$sh->set('Tuesday', false);
					$sh->set('Wednesday', false);
					$sh->set('Thursday', false);
					$sh->set('Friday', false);
					$sh->set('Saturday', false);
					$sh->set('Sunday', false);
					SERIA_Meta::save($sh);
					$createdRows++;
					SERIA_Base::debug((++$serial).': Created hour row: '.$hour);
					break;
				default:
					throw new SERIA_Exception('Duplicate weekly hour row: '.$hour);
			}
		}
		if ($createdRows)
			return self::generate(); /* Detects duplicates and does rollback (exception rollback) */
		SERIA_Base::debug((++$serial).': Returning schedule query');
		return SERIA_Meta::all('NDLA_WeeklySyncSchedule')->order('hour');
	}

	public static function editAction()
	{
		$days = array(
			'Monday',
			'Tuesday',
			'Wednesday',
			'Thursday',
			'Friday',
			'Saturday',
			'Sunday'
		);
		$action = new SERIA_ActionForm('editWeeklySyncSchedule');
		$spec = array();
		$rows = self::generate();
		foreach ($rows as $hour) {
			$h = $hour->get('hour');
			foreach ($days as $day) {
				$spec[$h.'at'.$day] = array(
					'fieldtype' => 'checkbox',
					'caption' => _t('%HOUR% o\'clock on %DAY%.', array('HOUR' => $h, 'DAY' => $day)),
					'value' => $hour->get($day),
					'validator' => new SERIA_Validator(array(SERIA_Validator::REQUIRED))
				);
			}
		}
		foreach ($spec as $name => $fspec)
			$action->addField($name, $fspec, $fspec['value']);
		if ($action->hasData()) {
			$data = array();
			$rows = self::generate();
			foreach ($rows as $hour) {
				$h = $hour->get('hour');
				foreach ($days as $day) {
					$fieldname = $h.'at'.$day;
					$hour->set($day, $action->get($fieldname));
					$data[$day][$h] = $action->get($fieldname);
				}
				SERIA_Meta::save($hour);
			}
			$action->success = true;
		}
		return $action;
	}
}