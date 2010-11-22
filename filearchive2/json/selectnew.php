<?php
	require_once(dirname(__FILE__).'/../../main.php');
	SERIA_Base::pageRequires('login');
	SERIA_Base::setErrorHandlerMode('exception');

	if (isset($_REQUEST['files']) && (isset($_REQUEST['selectNew']) || isset($_REQUEST['unselect']))) {
		try {
			$files = unserialize($_REQUEST['files']);
			if (isset($_REQUEST['selectNew'])) {
				$filearticle = SERIA_Article::createObjectFromId($_REQUEST['selectNew']);
				$fileobj = $filearticle->getFile();
				$files[$filearticle->get('id')] = array(
					'name' => $filearticle->get('title'),
					'articleId' => $filearticle->get('id'),
					'fileId' => $fileobj->get('id'),
					'filesize' => $fileobj->get('filesize'),
					'createdAt' => $fileobj->get('createdAt')
				);
			} else {
				$filearticle = SERIA_Article::createObjectFromId($_REQUEST['unselect']);
				if (isset($files[$filearticle->get('id')]))
					unset($files[$filearticle->get('id')]);
			}
			$html = SERIA_Template::parseToString(dirname(__FILE__).'/../template/fileinfo/main.php', array(
				'selected' => $files,
				'json' => true
			));
			SERIA_Lib::publishJSON(array(
				'html' => $html,
				'files' => serialize($files),
				'error' => false
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
