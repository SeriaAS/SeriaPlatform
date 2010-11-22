<?php

class SERIA_ArticleCategory extends SERIA_HierarchyAccess
{
	var $isDeleted = false;

	static function getRoot()
	{
		return SERIA_ArticleCategory::createObject();
	}
	
	public function __call($name, $params)
	{
		if(function_exists("SERIA_ArticleCategory_".$name))
		{
			$params = array_merge(array($this), $params);
			return call_user_func_array("SERIA_ArticleCategory_".$name, $params);
		}
		else
			throw new Exception("Unknown method '$name'.");
	}

	/**
	 * Retrieve an article category by its name.
	 * 
	 * EXAMPLE:
	 * $category = SERIA_ArticleCategory::createObjectFromName("Public relations");
	 */
	static function createObjectFromName($name) {
		$db = SERIA_Base::db();
		$id = $db->query("SELECT id FROM ".SERIA_PREFIX."_article_categories WHERE name=".$db->quote($name))->fetch(PDO::FETCH_COLUMN,0);
		if(!$id)
		{
			throw new SERIA_Exception("No such category with name '".$name."'");
		}
		return SERIA_ArticleCategory::createObject($id);
	}

	static function createObject($id=false, $parent=false)
	{
		static $cache = array();
		if(!$id)
		{
			return new SERIA_ArticleCategory(false,true);
		}

		if(is_array($id))
		{
			// TODO: Make it use the array
			$id = $id["id"];
		}

		if(!empty($cache[$id]))
			return $cache[$id];

		$res = $cache[$id] = new SERIA_ArticleCategory($id, true, $parent);

		return $res;
	}


	static private function sqlCreate($id, $parent, $pos, $name)
	{
		SERIA_Base::db()->exec("INSERT INTO ".SERIA_PREFIX."_article_categories (id, parent_id, pos, name) VALUES (:id, :parent_id, :pos, :name)",
			array(
				'id' => $id,
				'parent_id' => $parent,
				'pos' => $pos,
				'name' => $name,
			)
		);
	}
	/**
	 * Creates a category
	 *
	 * @param SERIA_ArticleCategory $parentNode
	 * 	To create a top level category, use SERIA_ArticleCategory::getRoot()
	 * @param string $name
	 * @return SERIA_ArticleCategory
	 */
	static function create($parentNode, $name)
	{
		if(!($parent = $parentNode->getId()))
			$parent = "NULL";

		$pos = 1 + sizeof($parentNode->getChildren());

		$id = SERIA_Base::guid('category');
		try {
			self::sqlCreate($id, $parent, $pos, $name);
		} catch (PDOException $m_e) {
			/*
			 * Try to fix position numbers
			 */
			$rows = SERIA_Base::db()->query('SELECT id FROM '.SERIA_PREFIX.'_article_categories WHERE parent_id = '.intval($parent).' ORDER BY pos')->fetchAll();
			$sqlq = array();
			$fpos = 0;
			foreach ($rows as $row)
				$sqlq[] = 'UPDATE '.SERIA_PREFIX.'_article_categories SET pos = '.(++$fpos).' WHERE id = '.intval($row['id']);
			try {
				foreach ($sqlq as $sql)
					SERIA_Base::db()->exec($sql);
			} catch (Exception $e) {
				throw $m_e;
			}
			self::sqlCreate($id, $parent, $pos, $name);
		}

		return SERIA_ArticleCategory::createObject($id, $parent);
	}

	/**
		*	Note that you should use SERIA_ArticelCategory::createObject($id) to create category objects.
		*/
	function __construct($id=false, $fromCreateObject=false, $parent=false)
	{
		if(!$fromCreateObject)
		throw new SERIA_Exception("Use SERIA_ArticleCategory::createObject(\$id) to create category objects.");

		parent::__construct(SERIA_PREFIX."_article_categories", "id", "parent_id", "pos", $id);

		if($id!==false)
		{
			switch(SERIA_Base::viewMode())
			{
				case "public" : // check if this article is available for the public
					if(!$this->row["is_published"]) throw new SERIA_Exception("Not published.");
					break;
				case "admin" : // available no matter if it is published
					break;
				default : // unsupported view mode
					throw new SERIA_Exception("Unsupported view mode.");
			}
		}

		switch(SERIA_Base::viewMode())
		{
			case "public" :
				$this->where("is_published=1");
				break;
			case "admin" :
				break;
			default : throw new SERIA_Exception("Unsupported view mode.");
		}
	}

	function getContextMenu()
	{
		if(SERIA_Base::user() === false)
			return "";

		$items = array();
		if(SERIA_Base::hasRight("edit_categories"))
		$items[] = "Edit category <strong>".htmlspecialchars($this->get("name"))."</strong>:top.SERIA.editArticleCategory(".$this->get("id").");";

		if(sizeof($items))
		return " mnu=\"".implode("|", $items)."\" ";

		return "";

		return " mnu=\"Edit category <strong>".htmlspecialchars($this->get("name"))."</strong>:top.SERIA.editArticleCategory(".$this->get("id").");\" ";
	}

	function getArray()
	{
		return $this->row;
	}

	function set($field,$value)
	{
		if(!array_key_exists($field, $this->row))
		throw new SERIA_Exception(_t("Unknown field '$field'."));

		if($field == "image_id")
		{
			if(!$value && $this->get("image_id"))
			{ // deleting image
					SERIA_File::createObject($this->get("image_id"))->decreaseReferrers();
			}
			else if($this->get("image_id") == $value)
			{ // no change
			}
			else 
			{ // change
				if($this->get("image_id"))
					SERIA_File::createObject($this->get("image_id"))->decreaseReferrers();
				if($value)
					SERIA_File::createObject($value)->increaseReferrers();
			}
		}
		
		$this->row[$field]=$value;
		return $this;
	}

	function save()
	{
		$db = SERIA_Base::db();
		if($this->isDeleted)
		throw new SERIA_Exception("This category has been deleted.");

		$sqlSet = array();
		foreach($this->row as $f => $n)
		{
			if($n===false || is_null($n))
				$sqlSet[] = $f."=NULL";
			else
				$sqlSet[] = $f."=".$db->quote($n);
		}
		
		return $db->exec("UPDATE ".SERIA_PREFIX."_article_categories SET
				".implode(",", $sqlSet)."
				WHERE id=".$this->getId());
	}

	function delete()
	{
		// this relies on using innodb database, or any database that supports cascading delete
		$this->isDeleted = true;
		return SERIA_Base::db()->exec("DELETE FROM ".SERIA_PREFIX."_article_categories WHERE id=".$this->getId());
	}

	static function getNodeById($id, $parent=false)
	{
		return SERIA_ArticleCategory::createObject($id, $parent);
	}

	static function getNodeByRow($row, $parent=false)
	{
		return SERIA_ArticleCategory::createObject($row, $parent);
	}

	function getArticles($type=false) // returns SERIA_ArticleQuery
	{
		$articles = new SERIA_ArticleQuery($type);

		$articles->inCategory($this);

		return $articles;
	}
	static function getCategories($parentCategoryId=false) {
		$db = SERIA_Base::db();
		$categories = array();
		$categoryRows = $db->query("SELECT id FROM ".SERIA_PREFIX."_article_categories WHERE ".($parentCategoryId ? "parent_id=".$db->quote($parentCategoryId)." AND " : "")."is_published=1 ORDER BY weight DESC")->fetchAll(PDO::FETCH_ASSOC);
		foreach($categoryRows as $categoryRow) {
			$categories[] = SERIA_ArticleCategory::createObject($categoryRow["id"]);
		}
		return $categories;
	}
	
	public static function getAllCategories() {
		$db = SERIA_Base::db();
		$categories = array();
		$categoryRows = $db->query("SELECT id FROM ".SERIA_PREFIX."_article_categories")->fetchAll(PDO::FETCH_ASSOC);
		foreach($categoryRows as $categoryRow) {
			$categories[] = SERIA_ArticleCategory::createObject($categoryRow["id"]);
		}
		return $categories;
		
	}
}

