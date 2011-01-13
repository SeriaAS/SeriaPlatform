<?php
	class SERIA_Gui
	{
		private $_head = array();
		private $_title = "No title";
		private $_appName = 'Seria Platform';
		private $_exitButton = false;
		private $_helpButton = false;
		private $_topMenu = array();
		private $_sectionMenu = '';
		private $_contents = "No contents";
		private $_contentsFrame = false;
		private $_topMenuActive = false;
		private $_startMenu = array();
		private $_subMenu = array();
		private $_subMenuActive = false;
		private $_blocks = array();
		private $_secondaryBlocks = array();
		private $sectionMenu = array();

		private $_guiMenu = array();
		private $_guiMenuActive = false;
		private static $_guiMenuActiveHint = false;
		private $_isBuffering = false;
		private $_httpStatusCode = false;

		/**
		*	Hook for attaching to the user interface whenever needed.
		*	@deprecated Replaced by SERIA_GuiManifest::EMBED_HOOK
		*/
		const EMBED_HOOK = "SERIA_GuiManifest::EMBED_HOOK";

		static $instance;

		static function sGuiTag($tag, $filename)
		{
			$code .= '<'.'?php $this->gui = new SERIA_Gui();';
			if($tag->get('title'))
				$code .= '$this->gui->title('.SERIA_MetaTemplate::attributeToConstant($tag->get('title'), $filename).');';
			if($tag->get('httpStatusCode'))
				$code .= '$this->gui->httpStatusCode('.SERIA_MetaTemplate::embedTagAttributeAsString($tag->get('httpStatusCode')).');';
			if($tag->get('popup'))
				$code .= '$this->guiPopup = true;';
			else
				$code .= '$this->guiPopup = false;';
			$code .= 'ob_start();';
			$code .= '?'.'>';
			return $code;
		}

		static function sGuiTagClose($tag)
		{
			$code = '<'.'?php echo $this->gui->contents(ob_get_clean())->output($this->guiPopup);?'.'>';
			return $code;
		}

		/**
		*	Construct the SERIA_Gui class.
		*	@param string $title		The page title to use
		*	@param boolean $standalone	Do not invoke hooks and other callbacks that might invoke component requirements
		*/
		function __construct($title=false, $standalone=false)
		{
			$this->_standalone = $standalone;
			$this->_title = $title;
			if(!$this->_standalone) {
				SERIA_Hooks::dispatch(SERIA_GuiManifest::EMBED_HOOK, $this);
			}
		}

		static function addNotice($html)
		{
			return SERIA_HtmlFlash::notice($html);
		}

		static function addError($html)
		{
			return SERIA_HtmlFlash::error($html);
		}

		public function httpStatusCode($code, $message)
		{
			$this->_httpStatusCode = "HTTP/1.0 ".$code." ".$message;
		}

		function start()
		{
			$this->buffering(array($this, '_start'));
		}

		function _start($buffer)
		{
			$this->_isBuffering = false;
			$this->contents($buffer);
			return $this->output();
		}

		/**
		 *	Instructs SERIA_Gui to fetch output buffer into contents.
		 */
		function buffering($callback=NULL)
		{
			$this->_isBuffering = true;
			ob_start($callback);
		}

		/**
		*	Creates a new menu item inside the user interface	
		*/
		function addMenuItem($id, $title, $description, $url, $icon = false, $weight = 0)
		{
			if(isset($this->_guiMenu[$id]))
				SERIA_Base::debug('<strong>Replacing GUI menu item "'.$id.'" ('.$this->_guiMenu[$id]['title'].') with ('.$title.').</strong>');

			$this->_guiMenu[$id] = array('id' => $id, 'title' => $title, 'url' => $url, 'icon' => $icon, 'weight' => $weight);

			return $this;
		}

		/**
		*	Set which menu items is active
		*/
		function activeMenuItem($id)
		{
			$this->_guiMenuActive = $id;
			return $this;
		}

		/**
		*	Hint about which menu item should be active, in case the active menu item
		*	is not defined by $gui->activeMenuItem()
		*	@param string $id	The menu path
		*/
		public static function activeMenuItemHint($id)
		{
			self::$_guiMenuActiveHint = $id;
		}

		function markActiveMenuItems()
		{
			$parts = explode("/", $this->_guiMenuActive);
			$find = "";
			foreach($parts as $part)
			{
				$find .= $part;
				if(isset($this->_guiMenu[$find]))
				{
					$this->_guiMenu[$find]['active'] = true;
				}
				else unset($this->_guiMenu[$find]['active']);
				$find .= "/";
			}
			return $this;
		}

		function getMenuItem($id)
		{
			$this->markActiveMenuItems();

			if(isset($this->_guiMenu[$id]))
			{
				return $this->_guiMenu[$id];
			}
			return false;
		}

		function getMenuItems($startsWith=false)
		{
			$this->markActiveMenuItems();

			$result = array();
			if($startsWith === false)
			{ // fetch all menu items at the root level
				foreach($this->_guiMenu as $id => $item)
				{
					if(strpos($id, "/")===false)
					{
						$result[$id] = $item;
					}
				}
			}
			else
			{
				$l = strlen($startsWith);
				foreach($this->_guiMenu as $id => $item)
				{
					if((strlen($id)>$l) && (substr($id, 0, $l) === $startsWith) && (strpos(substr($id, $l+1), "/") === false))
					{
						$result[$id] = $item;	
					}
				}
			}
			return $result;
		}

		function getMenuItemsLevel($level=0)
		{
			$this->markActiveMenuItems();

			$current = $this->getMenuItems();
			for($i = 0; $i < $level; $i++)
			{
				$active = false;
				foreach($current as $id => $item)
				{
					if(isset($item['active']) && $item['active'])
					{
						$active = $id;
						break;
					}
				}
				if($active === false)
				{
					return false;
				}
				$current = $this->getMenuItems($active);
			}
			return $current;
		}

		function getActiveMenuItemLevel($level=0)
		{
			$this->markActiveMenuItems();

			$level = $this->getMenuItemsLevel($level);
			foreach($level as $id => $item)
				if(!empty($item['active']))
				{
					return $item;
				}
			return false;
		}

		function title($title)
		{
			$this->_title = $title;
			return $this;
		}

		function appName($appName)
		{
			$this->_appName = $appName;
			return $this;
		}

		function addToHead($id, $data)
		{
			$this->_head[$id] = $data;
			return $this;
		}

		function contents($contents)
		{
			$this->_contents = $contents;
			return $this;
		}

		function contentsFrame($url)
		{
			$this->_contentsFrame = $url;
			return $this;
		}

		function addBlock($caption, $contents, $weight = 0)
		{
			$this->_blocks[] = array('caption' => $caption, 'contents' => $contents, 'weight' => $weight);
		}

		function addSecondaryBlock($caption, $contents, $weight = 0)
		{
			$this->_secondaryBlocks[] = array('caption' => $caption, 'contents' => $contents, 'weight' => $weight);
		}

		function sectionMenu($menu)
		{
			$this->_sectionMenu = $menu;
		}
		
		public function createSectionMenu($title) {
			$sectionMenu = new SERIA_GUI_SectionMenu($this, $title);
			$this->sectionMenu[] = $sectionMenu;
			return $sectionMenu;
		}
		
		public function addHtmlToSectionMenu($html) {
			$this->_sectionMenu .= $html;
		}

		function startMenu($caption, $icon, $onclick, $targetOffset=false)
		{
			if($pos===false)
				$pos = sizeof($this->_startMenu);
			$newMenuItem = array(
				"caption" => $caption,
				"icon" => $icon,
				"onclick" => $onclick,
			);

			$menu = array();
			foreach($this->_startMenu as $offset => $menuItem)
			{
				if($offset===$targetOffset)
				{
					$menu[] = $newMenuItem;
					$targetOffset = false;
				}
				$menu[] = $menuItem;
			}
			if($targetOffset!==false)
				$menu[] = $newMenuItem;
			$this->_startMenu = $menu;

			return $this;
		}
		
		function topMenu($caption, $onclick, $id=false)
		{
			$added = false;
			if(sizeof($this->_topMenu)===0)
			{
				$this->_topMenu = array();
				$added = true;
			}

			if($id===false)
				$id = sizeof($this->_topMenu);
			
			$this->_topMenu[] = array(
				"caption" => $caption,
				"onclick" => $onclick,
				"id" => $id,
			);
			
			if($added)
				$this->setActiveTopMenu($id);
			
			return $this;
		}

		function subMenu($caption, $onclick, $id=false)
		{
			$this->_subMenu[] = array(
				"caption" => $caption,
				"onclick" => $onclick,
				"id" => $id,
			);
			return $this;
		}
		
		function setActiveTopMenu($id)
		{
			$this->_topMenuActive = $id;
			return $this;
		}

		function exitButton($caption, $onclick)
		{
			$this->_exitButton = array(
				"caption" => $caption,
				"onclick" => $onclick,
			);
			return $this;
		}

		function helpButton($onclick)
		{
			$this->_helpButton = array(
				"caption" => _t("Help"),
				"onclick" => $onclick,
			);
			return $this;
		}

		function output($isPopup=false)
		{
/**
	UNSURE ABOUT THIS: Starting output buffering, then ending it immediately after seems weird, so I commented it out.
			// WATCH OUT FOR PROBLEMS WITH THIS!
			if (!defined('SERIA_GUI_NO_GZIP') || !SERIA_GUI_NO_GZIP)
				ob_start('ob_gzhandler');
			else
				ob_start();
*/
			if($this->_isBuffering)
			{
				$this->_contents = ob_get_contents();
				ob_end_clean();
			}
			if($this->_guiMenuActive === false || !isset($this->_guiMenu[$this->_guiMenuActive]))
			{
				if(self::$_guiMenuActiveHint)
					$this->_guiMenuActive = self::$_guiMenuActiveHint;
				else
					$this->_guiMenuActive = "controlpanel";
				SERIA_Base::debug('<strong>Active page not specified using $gui->activeMenuItem("application/icon/path")</strong>');
			}
/*
			$applicationIcons = SERIA_Hooks::dispatch('seria_gui_application_icons');
			foreach($applicationIcons as $key => $spec)
				if(!isset($spec['weight']))
					$applicationIcons[$key]['weight'] = 0;
			usort($applicationIcons, create_function('$a,$b','if($a["weight"]==$b["weight"]) return 0; return ($a["weight"]<$b["weight"]?-1:1);'));
*/

			foreach ($this->sectionMenu as $menu) {
				if ($menuCounter++) {
					$this->addHtmlToSectionMenu('<br />');
				}
				$menu->render();
			}
			
/*

Since logically, the user interface belongs to the templates, responsibility for jquery have been moved to the template file.

			SERIA_ScriptLoader::loadScript('jQuery');
			SERIA_ScriptLoader::loadScript("jQuery-ui");
			SERIA_ScriptLoader::loadScript("jQuery-ui-draggable");
			SERIA_ScriptLoader::loadScript("jQuery-ui-droppable");
			SERIA_ScriptLoader::loadScript('jQuery-treeview');
			SERIA_ScriptLoader::loadScript('jQuery-tablesorter');
			SERIA_ScriptLoader::loadScript('platform-widgets');
			SERIA_ScriptLoader::loadScript('Timer');
			SERIA_ScriptLoader::loadScript('jQuery-ui-tabs');
*/			
			$javascripts = array(
				/*SERIA_HTTP_ROOT.PATH_TO_SCRIPT_GOES_HERE,*/
			);

			$stylesheets = array(
				/*SERIA_HTTP_ROOT.PATH_TO_CSS_GOES_HERE,*/
			);
			
			foreach ($javascripts as $javascript) {
				SERIA_Template::jsInclude($javascript);
			}
			foreach ($stylesheets as $stylesheet) {
				SERIA_Template::cssInclude($stylesheet);
			}
			
			if($this->_head) foreach($this->_head as $k => $v)
			{
				SERIA_Template::head($k, $v);
			}
			
//$helpButton = array("caption" => "", "onclick" => "");
//$exitButton = array("caption" => "", "onclick" => "");
//$topMenu[] = array("caption" => "", "onclick" => "");
//$sectionMenu = html for sidebar
//$contentsFrame = URL for iframe

			foreach($this->_topMenu as $i => $t)
			{
				$this->_topMenu[$i]["active"] = $t["id"]===$this->_topMenuActive;
			}
			$vars = array(
				'gui' => $this,
				'helpButton' => $this->_helpButton,
				'exitButton' => $this->_exitButton,
				'topMenu' => $this->_topMenu,
				'subMenu' => $this->_subMenu,
				'blocks' => $this->_blocks,
				'secondaryBlocks' => $this->_secondaryBlocks,
				'startMenu' => $this->_startMenu,
				'contentsFrame' => $this->_contentsFrame,
				'isPopup' => $isPopup,
			);
			/*
			 * Maintain bacwards compatibility with sectionMenu sites (NUPA,Rapporter)
			 */
			if ($this->_sectionMenu)
				$vars['sectionMenu'] = $this->_sectionMenu;
			
			if($this->_title === false)
				throw new SERIA_Exception('Title not set for SERIA_GUI object.');
			SERIA_Template::title($this->_title.' - '.$this->_appName);
			SERIA_Template::contents($this->_contents);
			if(defined('SERIA_CLI_TEMPLATE') && empty($_SERVER['HTTP_HOST']) && empty($_ENV['HTTP_HOST']))
			{
				$t = SERIA_Template::parse(SERIA_CLI_TEMPLATE, $vars);
			}
			else
				$t = SERIA_Template::parse(SERIA_GUI_TEMPLATE, $vars);

			if($this->_httpStatusCode!==false)
				header($this->_httpStatusCode);
			return $t;
		}
	}

/*
	$gui = new SERIA_Gui("Welcome", "Logout", "alert(123)");
	$gui->helpButton("Help", "alert(234)");
	$gui->topMenu("Customers", "alert('Frode')");
	$gui->topMenu("Employees", "alert(2)");
	$gui->contents("
<h1>Seria Platform</h1>
<p>The contents of this page have not been set.</p>
");
	echo $gui->output();

*/
