<?php
	require_once(dirname(__FILE__).'/../../main.php');
	SERIA_Base::pageRequires('admin');
	SERIA_Base::setErrorHandlerMode('exception');

	if (isset($_REQUEST['dirname'])) {
		try {
			if (isset($_REQUEST['parent_directory_id']))
				$parentDirectory = SERIA_FileDirectory::createObject(intval($_REQUEST['parent_directory_id']));
			else
				$parentDirectory = false;
			if (($error = SERIA_IsInvalid::name($_REQUEST['dirname'], true)) !== false)
				throw new SERIA_Exception(_t('Directory name: %ERROR%', array('ERROR' => $error)));
			$newdir = SERIA_FileDirectory::createDirectory($_REQUEST['dirname'], $parentDirectory);
			SERIA_Lib::publishJSON(array(
				'error' => false,
				'id' => $newdir->getId(),
				'name' => $newdir->get('name')
			));
		} catch (Exception $e) {
			SERIA_Lib::publishJSON(array(
				'error' => $e->getMessage()
			));
		}
	} else {
		SERIA_Lib::publishJSON(array(
			'error' => 'Invalid argument'
		));
	}
