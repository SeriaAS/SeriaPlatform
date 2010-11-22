<?php

class SERIA_FileDirectory extends SERIA_HierarchyAccess
{
	static function getRoot()
	{
		return SERIA_FileDirectory::createObject();
	}
	
	static function createObject($id=false, $parent=false)
	{
		static $cache = array();
		if(!$id)
		{
			return new SERIA_FileDirectory(false,true);
		}

		if(is_array($id)) {
			// TODO: Make it use the array
			$id = $id["id"];
		}

		if($cache[$id])
			return $cache[$id];

		$res = $cache[$id] = new SERIA_FileDirectory($id, true, $parent);

		return $res;
	}

	static function sqlCreate($id, $parent, $pos, $name)
	{
		$fields = array(
			'id' => $id,
			'pos' => $pos,
			'name' => $name
		);
		if ($parent !== null)
			$fields['parent_id'] = $parent;
		SERIA_Base::db()->insert(
			'{filedirectory}',
			array_keys($fields),
			$fields
		);
	}

	static function createDirectory($dirname, $parent=false)
	{
		$parentNode = $parent;
		if(!$parent || !($parent = $parent->getId()))
			$parent = null;
		if (!$parentNode)
			$parentNode = self::createObject();

		$pos = 1 + sizeof($parentNode->getChildren());

		$id = SERIA_Base::guid('category');
		try {
			self::sqlCreate($id, $parent, $pos, $dirname);
		} catch (PDOException $m_e) {
			/*
			 * Try to fix position numbers
			 */
			$rows = SERIA_Base::db()->query('SELECT id FROM {filedirectory} WHERE parent_id = '.intval($parent).' ORDER BY pos')->fetchAll();
			$sqlq = array();
			$fpos = 0;
			foreach ($rows as $row)
				$sqlq[] = 'UPDATE {filedirectory} SET pos = '.(++$fpos).' WHERE id = '.intval($row['id']);
			try {
				foreach ($sqlq as $sql)
					SERIA_Base::db()->exec($sql);
			} catch (Exception $e) {
				throw $m_e;
			}
			self::sqlCreate($id, $parent, $pos, $dirname);
		}

		return SERIA_FileDirectory::createObject($id, $parent);
	}

	function __construct($id=false, $fromCreateObject=false, $parent=false)
	{
		if(!$fromCreateObject)
			throw new SERIA_Exception("Use SERIA_ArticleCategory::createObject(\$id) to create category objects.");
		parent::__construct('{filedirectory}', "id", "parent_id", "pos", $id);
	}

	static function getNodeById($id, $parent=false)
	{
		return SERIA_FileDirectory::createObject($id, $parent);
	}

	static function getNodeByRow($row, $parent=false)
	{
		return SERIA_FileDirectory::createObject($row, $parent);
	}

	public function moveFileArticleIdInto($file_article_id)
	{
		/* Remove from previous directory */
		SERIA_Base::db()->exec('DELETE FROM {filedirectory_file} WHERE file_article_id = :article_id', array('article_id' => $file_article_id));
		if ($this->getId()) {
			SERIA_Base::db()->insert('{filedirectory_file}', array('directory_id', 'file_article_id'), array(
				'directory_id' => $this->get('id'),
				'file_article_id' => $file_article_id
			));
		}
	}
	public function moveFileArticleInto(SERIA_FileArticle $file_article)
	{
		$this->moveFileArticleIdInto($file_article->get('id'));
	}

	public static function getDirectoryIdOfFileArticleId($file_article_id)
	{
		$rows = SERIA_Base::db()->query('SELECT directory_id FROM {filedirectory_file} WHERE file_article_id = :article_id', array('article_id' => $file_article_id))->fetchAll(PDO::FETCH_ASSOC);
		if (!$rows)
			return null;
		return $rows[0]['directory_id'];
	}
	public static function getDirectoryIdOfFileArticle($file_article)
	{
		return self::getDirectoryIdOfFileArticleId($file_article->get('id'));
	}
	public static function getDirectoryOfFileArticleId($file_article_id)
	{
		return self::createObject(self::getDirectoryIdOfFileArticleId($file_article_id));
	}
	public static function getDirectoryOfFileArticle($file_article)
	{
		return self::createObject(self::getDirectoryIdOfFileArticle($file_article));
	}
}