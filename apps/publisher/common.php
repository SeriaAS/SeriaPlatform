<?php
	// do not cache in admin mode
	require_once(dirname(__FILE__)."/../../../seria/main.php");
	$gui = new SERIA_Gui(_t('Seria Publisher'));
	$gui->title(_t('Seria Publisher'));	

//	$seriaPublisher->setActive(true);
//	$gui->appName($seriaPublisher->getName());
	
/*
	$gui->topMenu(_t("Site"), "location.href=\"" . SERIA_HTTP_ROOT . "/seria\";", $active="seria");
	if((sizeof(SERIA_Applications::getApplication('seria_publisher')->getArticleTypes())>0) && ((SERIA_Base::hasRight("create_article") || SERIA_Base::hasRight("publish_article") || SERIA_Base::hasRight("edit_others_articles"))))
		$gui->topMenu(_t("Articles"), "location.href=\"" . SERIA_HTTP_ROOT . "/seria/apps/publisher/articles.php\";", "articles");
		
	if((sizeof(SERIA_Applications::getApplication('seria_publisher')->getArticleTypes())>0) && (SERIA_Base::hasRight("edit_categories") || SERIA_Base::hasRight("publish_categories")))
		$gui->topMenu(_t("Categories"), "location.href=\"" . SERIA_HTTP_ROOT . "/seria/apps/publisher/categories.php\";", "categories");
		
	if(SERIA_Base::hasRight("edit_menu") && SERIA_SiteMenus::find_all()->count)
		$gui->topMenu(_t("Edit Menu"), "location.href=\"" . SERIA_HTTP_ROOT . "/seria/sitemenu/\";", "sitemenu");
*/
