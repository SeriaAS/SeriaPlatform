<?php
	/**
	*	SERIA_Blob provides storage and retrieval of files on any storage backend. SERIA_Blob is the basic unit of file storage to use in
	*	Seria Platform.
	*
	*	@author Frode Børli
	*	@package SERIA_Blob
	*/
	class SERIA_BlobManifest {
		const SERIAL = 2;
		const NAME = "blobs";

		/**
		*	Hook is dispatched whenever a new blob is added to the database. The listener is called with the SERIA_Blob object
		*	as the first parameter.
		*
		*	Note! A listener should return immediately, so place any lengthy processes on a queue to be performed
		*	asynchronously!
		*/
		const NEW_BLOB_HOOK = 'SERIA_BlobManifest::NEW_BLOB_HOOK';

		/**
		*	Whenever a new file is added find a consumer to handle storage of this file.
		*	Consumers must listen to this hook, and will be provided with an instance of SERIA_Blob as well
		*	as the path to the current local location of the file. If the storage provider accepts the file
		*	it must call SERIA_Meta::save($blob) then return true.
		*
		*	Notice that the backend is responsible for populating the SERIA_Blob instance with practical meta data
		*	such as duration (seconds), height (pixels), width (pixels), bitrate (kbit/sec) whenever appropriate
		*	and parseable from the file.
		*
		*	@param $blob		The SERIA_Blob object instance.
		*	@param $path		The complete path to the file in local file system.
		*/
		const BACKEND_HOOK = 'SERIA_BlobManifest::BACKEND_HOOK';

		public static $classPaths = array(
			'classes/*.class.php',
		);
	}

	/**
	*	Default backend that accepts all files, and places them in an appropriate folder in the SERIA_FILES_ROOT folder
	*	based on its extension and todays date:
	*	/files/txt/2011/01/14/filename.txt
	*/
	SERIA_Hooks::listen(SERIA_BlobManifest::BACKEND_HOOK, 'SERIA_BlobFallback', 99999);
	function SERIA_BlobFallback($blob, $path)
	{
		return new SERIA_DefaultBlobBackend($blob, $path);
	}

	/**
	*	SERIA_Blob is a component of Seria Platform for managing files. Each file on a server is identified by an instance
	*	of the class SERIA_Blob.
	SERIA_Base::addClassPath(SERIA_ROOT.'/seria/components/SERIA_Blob/classes/*.class.php');

	function SERIA_Blob_init()
	{
		SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, 'SERIA_BlobGuiEmbed');
	}

	// attach to the controlpanel so that the administrator can manage file extensions
	function SERIA_BlobGuiEmbed($gui)
	{
		if(SERIA_Base::isAdministrator())
		{
			$gui->addMenuItem('controlpanel/settings/files', _t('Files'), _t("Manage files"), SERIA_HTTP_ROOT.'/seria/components/SERIA_Blob/index.php', SERIA_HTTP_ROOT.'/seria/components/SERIA_Blob/icon.png', 100);
			$gui->addMenuItem('controlpanel/settings/files/filetypes', _t('Filetypes'), _t("Filetypes that are uploadable trough the file archive"), SERIA_HTTP_ROOT.'/seria/components/SERIA_Blob/filetypes.php');
			$gui->addMenuItem('controlpanel/settings/files/filearchive', _t('Filearchive'), _t("Upload and manage files"), SERIA_HTTP_ROOT.'/seria/components/SERIA_Blob/filearchive.php');
		}
	}
	*/
