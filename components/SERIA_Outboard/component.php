<?php
	SERIA_Base::addClassPath(SERIA_ROOT.'/seria/components/SERIA_Outboard/classes/*.class.php');

	function SERIA_OutboardInit()
	{
		SERIA_Router::instance()->addRoute('SERIA_Outboard', 'SERIA_Outboard pages', 'SERIA_Outboard_comments_show_template', 'outboard/comments/:template');
		SERIA_Hooks::listen(SERIA_Gui::EMBED_HOOK, 'SERIA_Outboard_guiEmbed');
	}

	function SERIA_Outboard_comments_show_template($vars)
	{
		$tpl = str_replace(array('/', '\\'), array('', ''), $vars['template']);
		$filename = dirname(__FILE__).'/cp/comments/'.$tpl.'.php';
		if (file_exists($filename)) {
			$tpl = new SERIA_MetaTemplate();
			echo $tpl->parse($filename);
			die();
		} else
			throw new SERIA_Exception('Not found', SERIA_Exception::NOT_FOUND);
	}

	function SERIA_Outboard_guiEmbed($gui)
	{
		$gui->addMenuItem('controlpanel/outboard', _t("Outboard"), _t("Outboard is special features that can be attached to any content type. Examples are comments, rating, voting and much more."), SERIA_HTTP_ROOT.'/seria/components/SERIA_Outboard/cp/', SERIA_HTTP_ROOT.'/seria/components/SERIA_Outboard/cp/icon.png');
		$gui->addMenuItem('controlpanel/outboard/comments', _t("Comments"), _t("Commenting feature."), SERIA_HTTP_ROOT.'/seria/components/SERIA_Outboard/cp/comments.php', SERIA_HTTP_ROOT.'/seria/components/SERIA_Outboard/cp/icon.png');
		$gui->addMenuItem('controlpanel/outboard/comments/approve', _t("Moderate comments"), _t("Manage and moderate comments."), SERIA_HTTP_ROOT.'?route=outboard/comments/moderate');
		$gui->addMenuItem('controlpanel/outboard/comments/browse', _t("Browse"), _t("Browse comments."), SERIA_HTTP_ROOT."/seria/components/SERIA_Outboard/cp/comments/browse.php");
//		$gui->addMenuItem('controlpanel/outboard/comments/edit', _t("Add comment"), _t("Add a new comment."), SERIA_HTTP_ROOT."/seria/components/SERIA_Outboard/cp/comments/edit.php");
		$gui->addMenuItem('controlpanel/outboard/ratings', _t("Ratings"), _t("Rating feature."), SERIA_HTTP_ROOT.'/seria/components/SERIA_Outboard/cp/ratings.php', SERIA_HTTP_ROOT.'/seria/components/SERIA_Outboard/cp/icon.png');
	}
