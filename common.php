<?php
/**
 *	A simple include file used by all user interface pages for Seria Platform.
 *	This file need revising, since it contains references to applications that are outside of the
 *	global scope.
 *
 *	@package SeriaPlatform
 */

	// do not cache in admin mode
	require_once(dirname(__FILE__)."/../seria/main.php");
	$gui = new SERIA_Gui(_t("Seria Publisher"));
	
	$gui->topMenu(_t("Site"), "location.href=\"" . SERIA_HTTP_ROOT . "/seria\";", $active=="seria");
	
	if(SERIA_ARTICLE_TYPES && ((SERIA_Base::hasRight("create_article") || SERIA_Base::hasRight("publish_article") || SERIA_Base::hasRight("edit_others_articles"))))
		$gui->topMenu(_t("Articles"), "location.href=\"" . SERIA_HTTP_ROOT . "/seria/apps/publisher/articles.php\";", "articles");
		
	if(SERIA_ARTICLE_TYPES && (SERIA_Base::hasRight("edit_categories") || SERIA_Base::hasRight("publish_categories")))
		$gui->topMenu(_t("Categories"), "location.href=\"" . SERIA_HTTP_ROOT . "/seria/apps/publisher/categories.php\";", "categories");
		
	if(SERIA_Base::hasRight("edit_menu") && SERIA_SiteMenus::find_all()->count)
		$gui->topMenu(_t("Edit Menu"), "location.href=\"" . SERIA_HTTP_ROOT . "/seria/sitemenu/\";", "sitemenu");
		
	if(SERIA_Base::isAdministrator()) {
		$gui->topMenu(_t("Settings"), "location.href=\"" . SERIA_HTTP_ROOT . "/seria/apps/controlpanel/settings/\";", "settings");
	}
	
	if (SERIA_Base::isAdministrator()) {
		$gui->topMenu(_t('Status'), 'location.href="' . SERIA_HTTP_ROOT . '/seria/apps/controlpanel/status/";', 'status');
	}
