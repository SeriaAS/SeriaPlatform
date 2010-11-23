<?php

	class SERIA_PublisherApplication extends SERIA_Application
	{
		private $articleTypes = array();

		function getId() { return 'seria_publisher'; }
		function getHttpPath() { return SERIA_HTTP_ROOT.'/seria/apps/publisher'; }
		function getInstallationPath() { return dirname(dirname(__FILE__)); }
		function getName() { return _t('Seria Publisher'); }

		private $active = false;
		function setActive($state)
		{
			$this->active = $state;
		}

		function __construct()
		{
			$articleTypes = explode(',',trim(SERIA_ARTICLE_TYPES));
			foreach($articleTypes as $articleType)
				if($articleType!=='')
					$this->addArticleType($articleType);
		}

		// Add event listeners and hook into wherever
		function embed()
		{
			SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array($this, 'guiEmbed'));
			SERIA_Hooks::listen(SERIA_ROUTER_HOOK, array($this, 'router'), -100);
		}

		// hook for urls
		function router($url)
		{
			// if url exists, then we start building the page
		}

		// hook for adding icon to the user interface
		function guiEmbed($gui)
		{
			$gui->addMenuItem('publisher', $this->getName(), _t('Use Seria Publisher to publish content on your website.'), SERIA_HTTP_ROOT.'/seria/apps/publisher/', SERIA_HTTP_ROOT.'/seria/apps/publisher/icon.png', -100);

//		        if((sizeof(SERIA_Applications::getApplication('seria_publisher')->getArticleTypes())>0) && ((SERIA_Base::hasRight("create_article") || SERIA_Base::hasRight("publish_article") || SERIA_Base::hasRight("edit_others_articles"))))
			{
				$gui->addMenuItem('publisher/articles', _t("Articles"), _t("Manage the article database."), SERIA_HTTP_ROOT.'/seria/apps/publisher/articles.php');
				$gui->addMenuItem('publisher/articles/list', _t("List articles"), _t("Show all articles."), SERIA_HTTP_ROOT.'/seria/apps/publisher/articles.php');
				if(SERIA_Base::hasRight("create_article"))
					$gui->addMenuItem('publisher/articles/edit', _t("Create article"), _t("Create an article and store it into the article database."), SERIA_HTTP_ROOT.'/seria/apps/publisher/articles.php?id=');
			}
				if((sizeof(SERIA_Applications::getApplication('seria_publisher')->getArticleTypes())>0) && (SERIA_Base::hasRight("edit_categories") || SERIA_Base::hasRight("publish_categories"))) {
					$gui->addMenuItem('publisher/categories', _t('Categories'), _t('Manage the content categories for your website.'), SERIA_HTTP_ROOT.'/seria/apps/publisher/categories.php');
					$gui->addMenuItem('publisher/categories/new', _t('New category'), _t('Manage the content categories for your website.'), SERIA_HTTP_ROOT.'/seria/apps/publisher/categories.php');
				}
//		        if(SERIA_Base::hasRight("edit_menu") && SERIA_SiteMenus::find_all()->count)
//				$gui->addMenuItem('publisher/sitemenu', _t('Site menu'), _t('Edit the website menu'), SERIA_HTTP_ROOT.'/seria/apps/publisher/sitemenu/');
		}

		// API
		function addArticleType($name)
		{
			$this->articleTypes[$name] = $name;
		}
		function hasArticleType($name)
		{
			return isset($this->articleTypes[$name]);
		}
		function getArticleTypes()
		{
			return $this->articleTypes;
		}
	}
