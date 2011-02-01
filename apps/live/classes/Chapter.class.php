<?php
	/**
	* Chapter
	* chapterName string
	* presentation Presentation
	*
	*/

	class Chapter extends SERIA_MetaObject {
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{chapters}',
				'displayName' => 'chapterName',
				'fields' => array(
					'chapterName' => array('title required', _t("Chapter name")),
					'presentation' => array('Presentation required', _t("Presentation")),
					'createdDate' => 'createdDate',
					'createdBy' => 'createdBy',
					'modifiedDate' => 'modifiedDate',
					'modifiedBy' => 'modifiedBy',
				)
			);
		}

		public function editAction()
		{
			$form = SERIA_Meta::editAction('edit_chapter', $this, array(
				'chapterName',
				'presentation'
			));

			return $form;
		}
	}
