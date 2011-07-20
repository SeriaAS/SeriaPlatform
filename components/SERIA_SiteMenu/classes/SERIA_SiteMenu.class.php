<?php
	class SERIA_SiteMenu extends SERIA_ActiveRecord {
		public $tableName = '_sitemenu';
		public $useGuid = true;
		public $usePrefix = true;
		protected $guidKey = 'menu';
		
		public $hasOne = array(
			'SERIA_SiteMenuRelation:ParentRelation' => 'child_id',
			'SERIA_SiteMenuArticle:Article' => 'sitemenu_id',
			'SERIA_SiteMenuUrl:Url' => 'sitemenu_id'
		);
		public $hasMany = array(
			'SERIA_SiteMenuRelation:ChildrenRelations' => 'parent_id'
		);

		public $customColumns = array(
			'parentIdFromForm'
		);

		/*
		 * Allow creation of more than one sitemenu
		 * See:
		 *   validationRules(),
		 *   getMenu()
		 */
		protected $menuRootCreation = false;

		/*
		 * START Model code
		 */

		public function validationRules() {
			if (!$this->menuRootCreation) { /* Allow creation of more than one sitemenu */
				$allowedElements = array_keys(SERIA_SiteMenus::find_all()->toKeyArray()) + array('');
				$this->addRule('parentIdFromForm', 'inset', _t('Parent id must be an existing menu element'), $allowedElements);
			}
		}

		/*
		 * END Model code
		 */

		public static function createById($id)
		{
			static $cache = array();

			if (!isset($cache[$id])) {
				$cache[$id] = SERIA_SiteMenus::find_first_by_id($id);
			}

			return $cache[$id];
		}

		/*
		 * START Tree methods
		 */

		public static function getRootElements() {
			$menus = SERIA_SiteMenus::find_all();
			$result = array();
			foreach ($menus as $menu) {
				if ($menu->isRoot()) {
					$result[] = $menu;
				}
			}
			return $result;
		}

		/**
		 * Returns true if this is a root element (has no parent).
		 * @return bool
		 */
		public function isRoot() {
			if ($this->getParent()) {
				return false;
			}
			
			return true;
		}

		/**
		 * Get children menu elements from this parent
		 * @return SERIA_ActiveRecordSet
		 */
		public function getChildren() {
			$children = array();
			$rows = SERIA_Base::db()->query('SELECT child_id FROM {sitemenu_relation} WHERE parent_id = ? AND child_id != ?', array($this->id, $this->id))->fetchAll(PDO::FETCH_NUM);
			foreach ($rows as $row)
				$children[] = self::createById($row[0]);

			// Sort children
			$sortData = array();
			foreach ($children as $child) {
				$changed = false;
				while (isset($sortData[$child->position])) {
					$child->position++;
					$changed = true;
				}
				// If the order was changed, save the record so this does not have to be done anymore.
				if ($changed) {
					$child->save();
				}

				$sortData[$child->position] = $child;
			}

			if ($sortData)
				ksort($sortData);
				
			return $sortData;
		}

		public function getParentId()
		{
			$pi = SERIA_Base::db()->query('SELECT parent_id FROM {sitemenu_relation} WHERE child_id = :child_id', array('child_id' => $this->id))->fetch(PDO::FETCH_NUM);
			if ($pi && $pi[0])
				return $pi[0];
			else
				return null;
		}
		/**
		 * Get parent from this child.
		 * @return SERIA_SiteMenu
		 */
		public function getParent() {
			$parent_id = $this->getParentId();
			if ($parent_id)
				return SERIA_SiteMenu::createById($parent_id);
				
			return false;
		}

		/**
		 * Add child to this element.
		 * @param $child SERIA_SiteMenu children object to add.
		 * @return bool
		 */
		public function addChild(SERIA_SiteMenu $child) {
			// Remove any existing parent relation
			if ($child->ParentRelation) {
				$child->ParentRelation->delete();
			}
				
			// Create a new parent relation
			$relation = new SERIA_SiteMenuRelation();
			$relation->parent_id = $this->id;
			$relation->child_id = $child->id;
			if ($relation->save()) {
				return true;
			}
				
			return false;
		}

		/**
		 * Creates or returns saved menu element.
		 * @param $name string Menu GUID
		 * @return SERIA_SiteMenu
		 */
		public static function getMenu($name='default') {
			$menu = SERIA_SiteMenus::find_first_by_name($name);
			if ($menu) {
				return $menu;
			} else {
				$menu = new SERIA_SiteMenu();
				$menu->name = $name;
				$menu->position = 0;
				$menu->relationtype = 'dummy';
				$menu->ispublished = true;
				$menu->menuRootCreation = true; /* Allow creation of more than one sitemenu */
				$menu->save();
				return $menu;
			}
		}
		
		/**
		 * Delete a menu item with all its children
		 */
		public function deleteWithChildren() {
			foreach ($this->getChildren() as $child) {
				$child->deleteWithChildren();
			}
			$this->delete();
		}

		/*
		 * END Tree methods
		 */

		/*
		 * START GUI methods
		 */

		public function getContextMenu() {
			if (SERIA_Base::isAdministrator()) {
				if (!$this->isRoot()) {
					$tag = 'mnu="';
					$tag .= _t('Add menu item') . ':top.location.href=\'' . SERIA_HTTP_ROOT . '/seria/sitemenu/additem.php?parent_id=' . $this->id . '\'';
					$tag .= '|' . _t('Edit menu item') . ':top.location.href=\'' . SERIA_HTTP_ROOT . '/seria/sitemenu/additem.php?edit=' . $this->id . '\'';
					$tag .= '|' . _t('Delete menu item') . ':top.location.href=\'' . SERIA_HTTP_ROOT . '/seria/sitemenu/deleteitem.php?id=' . $this->id . '\'';
					$parent = $this->getParent();
					$siblings = $parent->getChildren();
					/*
					 * Check that this element is not alone
					 */
					$moveUp = false;
					$moveDown = false;
					$hasSiblings = false;
					$hit = false;
					foreach ($siblings as $sibl) {
						if ($sibl->id != $this->id) {
							if ($hit)
								$moveDown = true;
							$hasSiblings = true;
						} else {
							if ($hasSiblings)
								$moveUp = true;
							$hit = true;
						}
					}
					if ($hasSiblings) {
						if ($moveUp)
							$tag .= '|' . _t('Move menu item up') . ':top.location.href=\''.SERIA_HTTP_ROOT.'/seria/sitemenu/move.php?move=up&id='.$this->id.'\'';
						if ($moveDown)
							$tag .= '|' . _t('Move menu item down') . ':top.location.href=\''.SERIA_HTTP_ROOT.'/seria/sitemenu/move.php?move=down&id='.$this->id.'\'';
					}
					$tag .= '"';

					return $tag;
				}
			}
				
			return '';
		}

		/**
		 * Create one dimentional array of elements for publishing in <select> box.
		 * @param @editElement SERIA_MenuItem Current element user is editing. Set this to prevent elements own children tree to be visible in select list
		 * @return array
		 */
		public static function getSelectBoxTree($editElement, $element = null, $elements = null, $level = 0) {
			if (!is_array($elements)) {
				$elements = array(0 => 'Root element');
			}
				
			if ($element) {
				$menuObjects = $element->getChildren();
			} else {
				$menuObjects = self::getRootElements();
			}
				
			$level++;
			foreach ($menuObjects as $menu) {
				// This check will prevent ability to move an element into its own children tree
				if (!is_object($editElement) || !$editElement->id || ($menu->id != $editElement->id)) {
					if ($menu->title) {
						$title = $menu->title;
					} else {
						$title = $menu->name;
					}
						
					$elements[$menu->id] = str_repeat('-', $level) . ' ' . $title;
						
					if (sizeof($menu->getChildren())) {
						foreach (self::getSelectBoxTree($editElement, $menu, $elements, $level) as $key => $value) {
							$elements[$key] = $value;
						}
					}
				}
			}
			$level--;
				
			return $elements;
		}

		public function getURL() {
			$link = '';
			if (($this->relationtype == 'url') && $this->Url->id) {
				$link = $this->Url->url;
			} elseif (($this->relationtype == 'article') && $this->Article->id) {
				$link = $this->Article->getUrl();
			}
			
			if ($link) {
				if (!strpos($link, '://'))
					return SERIA_HTTP_ROOT . $link;
				else
					return $link;
			} else
				return false;
		}
		public function renderMenuItem() {
			$linkTitle = htmlspecialchars($this->title);

			$html = '';

			$url = $this->getURL();
			if ($url) {
				$html .= '<a href="' . $url . '">' . $linkTitle . '</a>';
			} else {
				$html .= $linkTitle;
			}

			return $html;
		}

		/**
		 * Find out by looking at $_SERVER['REQUEST_URI'] wether this item is active (written by Jan-Espen)
		 *
		 * @return boolean
		 */
		public function isActive()
		{
			$url = htmlspecialchars_decode($this->getURL());
			$root_url = SERIA_HTTP_ROOT;
			$root_len = strlen($root_url);
			if (substr($url, 0, $root_len) == $root_url)
				$url = substr($url, $root_len);
			if ($url[0] != '/')
				$url = '/'.$url;
			$url = str_replace('//', '/', $url);
			$serv = str_replace('//', '/', $_SERVER['REQUEST_URI']);
			return ($url == $serv);
		}

		/**
		 * Returns true if any child of this node isActive().
		 *
		 * @param $descend Whether it will descend into child trees (default true)
		 * @return unknown_type
		 */
		public function hasActiveChild($descend=true)
		{
			$children = $this->getChildren();

			foreach ($children as $child) {
				if ($child->isActive() || ($descend && $child->hasActiveChild()))
					return true;
			}
			return false;
		}

		public function renderTree($options = array()) {
			$maxItems = $options['maxItems'];
			$maxLevels =& $options['maxLevels'];

			if (!is_int($maxLevels) && ($maxLevels !== null)) {
				$maxLevels = (int) $maxLevels;
			}

			$liClasses = array();

			if (--$maxLevels === 0) {
				return;
			}
			
			if (!$this->ispublished) {
				return '';
			}

			$liClasses[] = 'sitemenuItem';

			if (sizeof($this->getChildren())) {
				$liClasses[] = 'sitemenuItemWithChildren';
			} else {
				$liClasses[] = 'sitemenuItemWithoutChildren';
			}

			$hasActiveChild = false;
			$isActive = $this->isActive();
			if ($isActive)
				$liClasses[] = 'sitemenuItemActive';
			else if ($this->hasActiveChild()) {
				$liClasses[] = 'sitemenuItemHasActiveChild';
				$hasActiveChild = true;
			}

			if ($this->ParentRelation->parent_id) {
				$html .=  '<li class="' . implode(' ', $liClasses) . '" ' . $this->getContextMenu() . '>';
				$html .= $this->renderMenuItem();
				$html .= '</li>';
			}

			$children = $this->getChildren();
			if (sizeof($children)) {
				$ulClasses = array('sitemenu');
				if ($isActive)
					$ulClasses[] = 'sitemenuSubmenuActive';
				else if ($hasActiveChild)
					$ulClasses[] = 'sitemenuSubmenuHasActiveChild';
				$html .= '<ul class="' . implode(' ', $ulClasses) . '">';
				foreach ($children as $child) {
					$html .= $child->renderTree($options);
				}
				$html .= '</ul>';
			}

			return $html;
		}

		public function renderThisLevel($options = array()) {
			$options['maxLevels'] = 1;
			return $this->renderTree($options);
		}


		/*
		 * END GUI methods
		 */

		/*
		 * START Event methods
		 */

		public function afterSave() {
			// Attach to correct object from value in parentIdFromForm
			if ($this->parentIdFromForm) {
				$parent = SERIA_SiteMenus::find($this->parentIdFromForm);
				if ($parent) {
					$parent->addChild($this);
				}
			}
		}

		public function afterGet() {
			if ($this->ParentRelation->id) {
				$this->parentIdFromForm = $this->ParentRelation->parent_id;
			}
		}

		public function event() {}
	}
?>