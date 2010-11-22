<?php
	/**
	*	Represents file types that are allowed to be uploaded on the system
	*/
	class SERIA_BlobType extends SERIA_MetaObject
	{
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{blobtypes}',
				'displayField' => 'name',
				'fields' => array(
					'name' => array('name required', _t("Name")),
					'extension' => array('fileextension', _t("File extension")),
					'mimetype' => array('mimetype required', _t("Mime")),
					'icon' => array('name', _t("Icon")),
				),
			);
		}

		public function getBlobs()
		{
			return SERIA_Meta::all('SERIA_Blob')->where('type=:id', $this);
		}

		public function editAction()
		{
			return SERIA_Meta::editAction('edit', $this, array('name','extension','mimetype','icon'));
		}

		public function deleteAction($reload=true)
		{
			return SERIA_Meta::deleteAction('delete', $this);
		}
	}
