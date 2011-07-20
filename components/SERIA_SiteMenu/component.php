<?php

/**
 *
 * Legacy sitemenu code.
 *
 * @author Various Authors
 * @package SERIA_SiteMenu
 *
 */
class SERIA_SiteMenuManifest
{
	const SERIAL = 1;
	const NAME = 'seriasitemenu';

	public static $classPaths = array(
		'classes/*.class.php',
	);
	public static $database = array(
		'creates' => array(
		)
	);
	public static $menu = array(
	);
}

function SERIA_SiteMenuInit()
{
	SERIA_Router::instance()->addRoute('SERIA_SiteMenu', 'SERIA_SiteMenu pages', 'SERIA_SiteMenu_page', 'sitemenu/:page');
	SERIA_Hooks::listen(SERIA_Gui::EMBED_HOOK, 'SERIA_SiteMenu_gui');
}

function SERIA_SiteMenu_page($vars)
{
	if (isset($vars['page'])) {
		$page = SERIA_Sanitize::filename($vars['page']);
		if ($page == '.' || $page == '..')
			SERIA_Base::displayErrorPage(404, _t('Not found!'), _t('Page not found!'));
		$filename = dirname(__FILE__).'/sitemenu/'.$page.'.php';
		if (file_exists($filename)) {
			$tpl = new SERIA_MetaTemplate();
			die($tpl->parse($filename));
		} else
			SERIA_Base::displayErrorPage(404, _t('Not found!'), _t('Page not found!'));
	}
}

function SERIA_SiteMenu_gui(SERIA_Gui $gui)
{
	$gui->addMenuItem('controlpanel/other/sitemenu', _t("Site menu"), _t("Edit legacy site menu"), SERIA_HTTP_ROOT.'?route=sitemenu/index', SERIA_HTTP_ROOT.'/seria/components/SERIA_SiteMenu/icon.png');
}