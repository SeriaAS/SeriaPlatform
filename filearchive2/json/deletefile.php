<?php
	require_once(dirname(__FILE__).'/../../main.php');
	require_once(dirname(__FILE__).'/../../filearchive/common.php'); /* For icons */
	SERIA_Base::pageRequires('login');
	SERIA_Base::setErrorHandlerMode('exception');

	try {
		if (isset($_REQUEST['fileArticleId'])) {
			$fileArticle = SERIA_Article::createObjectFromId($_REQUEST['fileArticleId']);
			if (isset($_REQUEST['confirmed'])) {
				$fileArticle->delete();
				SERIA_Lib::publishJSON(array(
					'error' => false
				));
			} else {
				SERIA_Lib::publishJSON(array(
					'error' => false,
					'confirm' => _t('Are you sure you want to delete the file: %FILE%?', array('FILE' => $fileArticle->get('title')))
				));
			}
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
