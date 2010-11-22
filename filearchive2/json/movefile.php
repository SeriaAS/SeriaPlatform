<?php
	require_once(dirname(__FILE__).'/../../main.php');
	require_once(dirname(__FILE__).'/../../filearchive/common.php'); /* For icons */
	SERIA_Base::pageRequires('login');
	SERIA_Base::setErrorHandlerMode('exception');

	try {
		if (isset($_REQUEST['file_article_id']) && isset($_REQUEST['directory_id'])) {
			$fileArticle = SERIA_Article::createObjectFromId($_REQUEST['file_article_id']);
			$current_directory = SERIA_FileDirectory::getDirectoryOfFileArticle($fileArticle);
			$loopDetect = false;
			if ($_REQUEST['directory_id'] != 'root') {
				if ($current_directory->isRoot() || $current_directory->get('id') != $_REQUEST['directory_id'])
					$directory = SERIA_FileDirectory::createObject($_REQUEST['directory_id']);
				else
					$loopDetect = true;
			} else {
				if (!$current_directory->isRoot())
					$directory = SERIA_FileDirectory::createObject();
				else
					$loopDetect = true;
			}
			if (!$loopDetect)
				$directory->moveFileArticleInto($fileArticle);
			SERIA_Lib::publishJSON(array(
				'loop' => $loopDetect,
				'error' => false
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
