<?php
	require('common.php');
	$id = $_GET['id'];
	
	SERIA_Base::pageRequires('admin');
	
	$menuItem = SERIA_SiteMenus::find($id);
	if (!$menuItem) {
		SERIA_HtmlFlash::error(_t('The requested menu item was not found'));
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/sitemenu/');
	}
	if (!isset($_GET['move']) || ($_GET['move'] != 'down' && $_GET['move'] != 'up')) {
		SERIA_HtmlFlash::error(_t('Invalid move operation'));
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/sitemenu/');
	}

	$menuItemParent = $menuItem->getParent();
	if (!$menuItemParent) {
		SERIA_HtmlFlash::error(_t('The menu item is a root'));
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/sitemenu/');
	}
	$siblings = $menuItemParent->getChildren();
	$prevKey = null;
	$moveDown = null;
	$moved = false;
	foreach ($siblings as $key => &$item) {
		if ($moveDown !== null) {
			/* Bubble down */
			$temp =& $siblings[$key];
			$siblings[$key] =& $siblings[$moveDown];
			$siblings[$moveDown] =& $temp;
			unset($temp);
			$moveDown = null;
			$moved = true;
			break;
		}
		if ($item->id == $menuItem->id) {
			if ($_GET['move'] == 'down')
				$moveDown = $key;
			else if ($_GET['move'] == 'up' && $prevKey !== null) {
				$temp =& $siblings[$key];
				$siblings[$key] =& $siblings[$prevKey];
				$siblings[$prevKey] =& $temp;
				unset($temp);
				$moved = true;
				break;
			}
		}
		$prevKey = $key;
	}
	unset($item);
	if (!$moved) {
		SERIA_HtmlFlash::error(_t('Invalid move operation'));
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/sitemenu/');
	}
	/*
	 * Redo ordering..
	 */
	$pos = 0;
	foreach ($siblings as &$item) {
		$item->position = $pos++;
		$item->save();
	}
	unset($item);
	SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/sitemenu/');
