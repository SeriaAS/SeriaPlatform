<?php
	require_once(dirname(__FILE__).'/../../main.php');
	require_once(dirname(__FILE__).'/../../filearchive/common.php'); /* For icons */
	SERIA_Base::pageRequires('login');
	SERIA_Base::setErrorHandlerMode('exception');

	try {
		if (isset($_REQUEST['page']) && isset($_REQUEST['categoryId'])) {
			$page = intval($_REQUEST['page']);
			$categoryId = intval($_REQUEST['categoryId']);
			$pagingLimit = 5;
			if (isset($_REQUEST['limit']) && intval($_REQUEST['limit']) > 0)
				$pagingLimit = intval($_REQUEST['limit']);
			$pagingInfo = array(
				'page' => $page,
				'start' => $page*$pagingLimit,
				'limit' => $pagingLimit,
				'prevAvailable' => ($page > 0)
			);
			$q = new SERIA_FileQuery();
			$q->order("created_date DESC");
			if ($categoryId >= 0)
				$q->inDirectoryId($categoryId);
			$fa = $q->getFileArticleIds($pagingInfo['start'], $pagingInfo['limit']);
			$lima = $q->getFileArticleIds($pagingInfo['start'] + $pagingInfo['limit'], 1);
			$pagingInfo['nextAvailable'] = (count($lima) > 0);
			$caching_fileicons = new SERIA_Cache('Filearchive2_fileicons');
			$files = array();
			if ($fa) {
				foreach ($fa as $article_id) {
					$info = $caching_fileicons->get($article_id); /* File-article-id */
					if ($info === NULL) {
						$f = SERIA_Article::createObjectFromId($article_id);
						$file_id = $f->get('file_id');
						$file = $f->getFile();
						$icon = getIconFromFilename($file->get('filename'));
						$iconWidth = 32;
						$iconHeight = 32;

						$thumbnailUrl = '';
						try {
							$thumbnail = $file->getThumbnail(48, 48, array('transparent_fill'));
							list($thumbnailUrl, $iconWidth, $iconHeight) = $thumbnail;
						} catch (Exception $null) {
							$thumbnailUrl = '';
						}

						if ($thumbnailUrl) {
							$icon = $thumbnailUrl;
						}
						$info['file_id'] = $file_id;
						$info['icon'] = $icon;
						$info['title'] = $f->get('title');
						$caching_fileicons->set($article_id, $info, 36000);
					} else
						$file_id = $info['file_id'];
					$files[$article_id] = array(
						'name' => $info['title'],
						'icon' => $info['icon']
					);
				}
			}
			$html = SERIA_Template::parseToString(dirname(__FILE__).'/../template/fileicons/main.php', array(
				'files' => $files,
				'selected' => array(),
				'pagingInfo' => $pagingInfo,
				'json' => true,
				'filelist_id' => 'filearchive_filelist'
			));
			$result = array(
				'error' => false,
				'html' => $html
			);
			if (SERIA_DEBUG) {
				$messages = array();
				$debug = SERIA_Template::get('debugMessages');
				foreach ($debug as $msg) {
					$messages[] = htmlentities($msg['time'])."\t".htmlentities($msg['message']);
				}
				$result['debug'] = implode("\n", $messages);
			}
			SERIA_Lib::publishJSON($result);
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
