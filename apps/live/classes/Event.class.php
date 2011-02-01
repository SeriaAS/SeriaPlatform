<?php
	/**
	* Event
	* type string ('SET_FOIL', 'SET_CHAPTER', 'START_RECORDING', 'STOP_RECORDING', ...)
	* presentation Presentation
	* eventData string
	* eventTime timestamp
	*
	*/

	class Chapter extends SERIA_MetaObject {
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{events}',
				'displayName' => 'type',
				'fields' => array(
					'type' => array('name required', _t("Event type")),
					'presentation' => array('Presentation required', _t("Presentation")),
					'eventData' => array('text', _t("Event data")),
					'eventTime' => array('datetime required', _t("Event time")),
					'createdDate' => 'createdDate',
					'createdBy' => 'createdBy',
					'modifiedDate' => 'modifiedDate',
					'modifiedBy' => 'modifiedBy',
				)
			);
		}

		public function editAction()
		{
			$form = SERIA_Meta::editAction('edit_event', $this, array(
				'type',
				'presentation',
				'eventData',
				'eventTime',
			));

			return $form;
		}
	}
