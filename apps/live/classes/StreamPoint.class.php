<?php
	/**
	* StreamPoint
	* streamName string
	* publishName string
	* inUse boolean
	* reserved boolean
	*
	*/

	class StreamPoint extends SERIA_MetaObject {
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{streampoints}',
				'displayName' => 'streamName',
				'fields' => array(
					'streamName' => array('name required', _t("Stream name")),
					'publishName' => array('name required', _t("Publish name")),
					'inUse' => array('boolean',_t("In use")),
					'reserved' => array('boolean',_t("Reserved")),
					'createdDate' => 'createdDate',
					'createdBy' => 'createdBy',
					'modifiedDate' => 'modifiedDate',
					'modifiedBy' => 'modifiedBy',
				)
			);
		}

		public function editAction()
		{
			$form = SERIA_Meta::editAction('edit_streampoint', $this, array(
				'streamName',
				'publishName'
			));

			return $form;
		}
	}
