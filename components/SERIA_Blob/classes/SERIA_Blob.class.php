<?php

//define('SERIA_BLOB_IMAGE', 'small=400x300,medium=600x400,large=1000x700');
//define('SERIA_BLOB_VIDEO_FORMATS', 'ipod-small,ipod-medium,ipod-large,flv-medium,custom-flv3');





	/**
	*	Represents a file. The following applies to special file types:
	*
	*	Images can be resized by applying parameters to the image url: <img src="'.$image.'?thumbnail=300x200">
	*	will create a scaled version to fit within 300x200 pixels and store it in dyn/images/$image_thumbnrail-300x200.jpg
	*	then rewrite the output.
	*
	*	Video files are automatically transcoded when uploaded, according to transcoding rules defined in the control
	*	panel.
	*
	*	Thumbnails of videos can be created by adding the query parameter ?thumbnail=300x200&offset=0.5
	*	where 0.5 means at 50 % of the video file, while any number 1 or larger will give a thumbnail at the keyframe closest
	*	to the second specified.
	*/
	class SERIA_Blob extends SERIA_MetaObject
	{
		public static function Meta($instance=NULL)
		{
			return array(
				'table' => '{blobs}',
				'displayField' => 'originalName',
				'fields' => array(
					'editionOf' => array('SERIA_Blob', _t("An edition of")),
					'editionName' => array('name', _t("Edition identifier")),
					'originalName' => array('name required', _t("Filename")),
					'filesize' => array('integer', _t("Filesize")),
					'duration' => array('integer', _t("Duration")),
					'height' => array('integer', _t("Height")),
					'width' => array('integer', _t("Width")),
					'bitrate' => array('integer', _t("Kbps")),
					'isAccessible' => array('boolean', _t("Ready to be accessed")),
					'backendClass' => array('classname', _t("Backend class"), array("implements" => "SERIA_IBlobBackend")),
					'backendData' => array('text', _t("Backend data")),
					'createdBy' => 'createdBy',
					'createdDate' => 'createdDate',
				),
			);
		}

		/**
		*	Whenever this blob is saved to the database
		*/
		public function MetaAfterSave()
		{
			if($this->MetaIsNew())
			{
				SERIA_Hooks::dispatch(SERIA_BlobManifest::NEW_BLOB_HOOK, $this);
			}
		}

		public function getEditions() {
			return SERIA_Meta::all('SERIA_Blob')->where('editionOf=:id', $this);
		}

		public function getEdition($editionName) {
			$edition = SERIA_Meta::all('SERIA_Blob')->where('editionOf=:id AND editionName=:name', array('id' => $this->get('id'), 'name' => $editionName))->fetch();
			if($edition) return $edition;
			throw new SERIA_Exception('Could not find that edition of this file.', SERIA_Exception::NOT_FOUND);
		}

		/**
		*	Consume a local file and store in in an appropriate backend, such as
		*	the local file system or a cloud backed service.
		*/
		public static function createFromFile($filepath)
		{
			if(!file_exists($filepath))
				throw new SERIA_Exception('File "'.$filepath.'" not found.', SERIA_Exception::NOT_FOUND);

			if(is_dir($filepath))
				throw new SERIA_Exception('File "'.$filepath.'" is a directory.');

			$blob = new SERIA_Blob();
			$blob->set("filesize", filesize($filepath));
			$handled = SERIA_Hooks::dispatchToFirst(SERIA_BlobManifest::BACKEND_HOOK, $blob, $filepath);
			if($handled)
			{
				SERIA_Meta::save($blob);
				return $blob;
			}
			throw new SERIA_Exception("No backend captured the file");
		}

		/**
		*	Return the url of this file
		*/
		public function getUrl($protocol) {
			if($this->get('isAccessible'))
				return $this->getBackend()->getUrl($protocol);
			throw new SERIA_Exception('The file is currently not available. It may take some time to upload a file to an external hosting provider.', SERIA_Exception::NOT_READY);
		}

		public function delete() {
			$this->getBackend()->delete();
			SERIA_Meta::delete($this);
		}

		public function getBackend() {
			$className = $this->get('backendClass');
			return new $className($this);
		}
	}
