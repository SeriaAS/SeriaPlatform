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
				'displayField' => 'extension',
				'fields' => array(
					'extension' => array('fileextension required unique', _t("File extension")),
					'mediatype' => array('internetmediatype required', _t("Internet media type")),
				),
			);
		}

		public function getBlobs()
		{
			return SERIA_Meta::all('SERIA_Blob')->where('type=:id', $this);
		}

		public function editAction()
		{
			return SERIA_Meta::editAction('edit', $this, array('extension','mediatype'));
		}

		public function deleteAction($reload=true)
		{
			return SERIA_Meta::deleteAction('delete', $this);
		}
	}
