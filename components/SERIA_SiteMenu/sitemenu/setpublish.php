<?php
	require('common.php');
	$id = $_GET['id'];
	$publish = (int) (bool) $_GET['publish'];
	
	SERIA_Base::pageRequires('admin');
	
	$menuItem = SERIA_SiteMenus::find($id);
	if (!$menuItem) {
		SERIA_HtmlFlash::error(_t('The requested menu item was not found'));
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '?route=sitemenu/index');
	}
	
	$optionsMenu->addLink('<< Cancel', SERIA_HTTP_ROOT . '?route=sitemenu/index');
	
	try {
		$menuItem->ispublished = $publish;
		$menuItem->save();
		if ($publish) {
			SERIA_HtmlFlash::notice(_t('The requested menu item was successfully published'));
		} else {
			SERIA_HtmlFlash::notice(_t('The requested menu item was successfully unpublished'));
		}
	} catch (Exception $exception) {
		SERIA_HtmlFlash::error(_t('The requested menu publish status could not be updated: %ERROR%', array('ERROR' => $exception->getMessage())));
	}
		
	SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '?route=sitemenu/index');
?>
<?php require_once('common_tail.php'); ?>

