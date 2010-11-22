<?php
	require_once(dirname(__FILE__).'/../../main.php');
	require_once(dirname(__FILE__).'/../../filearchive/common.php'); /* For icons */
	SERIA_Base::pageRequires('login');
	SERIA_Base::setErrorHandlerMode('exception');

	try {
		if (isset($_REQUEST['directory_id'])) {
			if ($_REQUEST['directory_id'] != 'root')
				$directory = SERIA_FileDirectory::createObject($_REQUEST['directory_id']);
			else
				$directory = SERIA_FileDirectory::createObject();
			$path = array();
			while (!$directory->isRoot()) {
				array_unshift($path, array(
					'id' => $directory->getId(),
					'name' => $directory->get('name')
				));
				$directory = $directory->getParent();
			}
			$path['length'] = count($path);
			SERIA_Lib::publishJSON(array(
				'error' => false,
				'path' => $path
			));
		} else {
			SERIA_Lib::publishJSON(array(
				'error' => 'Invalid argument!'
			));
		}
	} catch (Exception $e) {
		SERIA_Lib::publishJSON(array(
			'error' => $e->getMessage()
		));
	}
