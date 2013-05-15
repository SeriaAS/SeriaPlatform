<?php

	/**
	 *
	 *
	 * @author Frode Boerli
	 * @package SERIA_Outboard
	 *
	 */
	class SERIA_OutboardManifest
	{
		const SERIAL = 1;
		const NAME = 'SERIA_Outboard';
	
		public static $classPaths = array(
			'classes/*.class.php',
			'sapi/*.class.php'
		);
		public static $dependencies = array(
			'SAPI'
		);
	}

	function SERIA_OutboardInit()
	{
		SERIA_Router::instance()->addRoute('SERIA_Outboard', 'SERIA_Outboard pages', 'SERIA_Outboard_comments_show_template', 'outboard/comments/:template');
		SERIA_Hooks::listen(SERIA_Gui::EMBED_HOOK, 'SERIA_Outboard_guiEmbed');
		SERIA_Hooks::listen(SERIA_User::DELETE_HOOK, array('SERIA_Comment', 'deleteUserHook'));
		SERIA_Hooks::listen(SERIA_User::DELETE_HOOK, array('SERIA_CommentLog', 'deleteUserHook'));
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
		$gui->addMenuItem('controlpanel/outboard/comments/browse', _t("Browse"), _t("Browse comments."), SERIA_HTTP_ROOT."?route=outboard/comments/browse");
//		$gui->addMenuItem('controlpanel/outboard/comments/edit', _t("Add comment"), _t("Add a new comment."), SERIA_HTTP_ROOT."/seria/components/SERIA_Outboard/cp/comments/edit.php");
		$gui->addMenuItem('controlpanel/outboard/ratings', _t("Ratings"), _t("Rating feature."), SERIA_HTTP_ROOT.'/seria/components/SERIA_Outboard/cp/ratings.php', SERIA_HTTP_ROOT.'/seria/components/SERIA_Outboard/cp/icon.png');
	}
