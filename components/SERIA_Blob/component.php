<?php
	/**
	*	SERIA_Blob is a component of Seria Platform for managing files. Each file on a server is identified by an instance
	*	of the class SERIA_Blob.
	*/
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
