<?php

class SERIA_FileQueryArticleArray implements ArrayAccess,Countable,Iterator {
	protected $fileArticles;
	protected $offset;
	protected $length;

	function __construct($fileIds)
	{
		$this->fileArticles = array();
		foreach ($fileIds as $id)
			$this->fileArticles[] = array($id, false);
	}

	/* ArrayAccess */
	public function offsetExists($offset)
	{
		return isset($this->fileArticles[$offset]);
	}
	public function offsetGet($offset)
	{
		if ($this->fileArticles[$offset][1] === false && $this->fileArticles[$offset][0] !== false)
			$this->fileArticles[$offset][1] = SERIA_Article::createObjectFromId($this->fileArticles[$offset][0]);
		return $this->fileArticles[$offset][1];
	}
	public function offsetSet($offset, $value)
	{
		$this->fileArticles[$offset] = array(false, $value);
	}
	public function offsetUnset($offset)
	{
		unset($this->fileArticles[$offset]);
	}

	/* Countable */
	public function count()
	{
		return count($this->fileArticles);
	}

	/* Iterator */
	protected $it_position = 0;
	public function current()
	{
		return $this[$this->it_position];
	}
	public function key()
	{
		return $this->it_position;
	}
	public function next()
	{
		$this->it_position++;
	}
	public function rewind()
	{
		$this->it_position = 0;
	}
	public function valid()
	{
		return isset($this[$this->it_position]);
	}
}

class SERIA_FileQuery
{
	protected $directoryId=false;
	protected $storage = array();
	protected $terminated = false; /* storage has true EOF */
	protected $fetchedTo = 0;
	protected $order = false;

	function __construct($directoryId=false)
	{
		$this->directoryId = $directoryId;
	}

	public function inDirectoryId($directoryId)
	{
		$this->directoryId = $directoryId;
	}
	public function inDirectory(SERIA_FileDirectory $directory)
	{
		$this->inDirectoryId($directory->get('id'));
	}
	function order($order)
	{
		$this->order = $order;
		return $this;
	}

	protected function getWhere()
	{
		$sqlparams = array();
		$requirements = array();
		$ftquery = '+'.SERIA_7Bit::word('@type:SERIA_File');
		if ($this->directoryId !== false) {
			/* Directory search: Easy! */
			$sqlparams['directory_id'] = $this->directoryId; 
			$requirements[] = 'id IN (SELECT DISTINCT file_article_id FROM {filedirectory_file} WHERE directory_id = :directory_id)';
		} else {
			/* Search for all file_articles without a directory: Bad! */
			/* TODO -
			 * This should be switched to a faster algorithm later, because
			 * having to fetch everything and exclude files that is contained
			 * in a directory is too bad. We have to hook onto upload and maintain
			 * a list of toplevel files as soon as possible.
			 */
			$requirements[] = '(NOT id IN(SELECT DISTINCT file_article_id FROM {filedirectory_file}))';
		}
		$requirements[] = "id IN (SELECT id FROM ".SERIA_PREFIX."_articles_fts WHERE MATCH (ft) AGAINST (:ftquery IN BOOLEAN MODE))";
		$sqlparams['ftquery'] = $ftquery;
		return array(implode(' AND ', $requirements), $sqlparams);
	}

	protected function getFilesLowlevel($skip,$limit)
	{
		$where = $this->getWhere();
		$sqlparams = $where[1];
		$where = $where[0];
		$sql = 'SELECT id FROM {articles} WHERE '.$where;
		if ($this->order !== false)
			$sql .= ' ORDER BY '.$this->order;
		if ($limit === false) {
			if ($skip)
				$sql .= ' LIMIT '.$skip.',18446744073709551615';
		} else
			$sql .= ' LIMIT '.$skip.','.$limit;
		$ids_p = SERIA_Base::db()->query($sql, $sqlparams)->fetchAll(PDO::FETCH_ASSOC);
		$ids = array();
		$offset = $skip;
		foreach ($ids_p as $id) {
			$ids[] = $id['id'];
			$this->storage[$offset] = $id['id'];
			$offset++;
		}
		if ($this->fetchedTo < $offset)
			$this->fetchedTo = $offset;
		if (count($ids) && $limit === false)
			$this->terminated = true;
		return $ids;
	}
	public function getFileArticleIds($skip=0,$limit=false)
	{
		/* Check if cache is empty */
		if (!count($storage))
			return $this->getFilesLowlevel($skip,$limit);
		/* Check if cache is continous */
		if ($limit !== false)
			$upper = $skip + $limit;
		$ids = array();
		for ($offset = $skip; $offset < $this->fetchedTo && ($limit === false || $offset < $upper); $offset++) {
			if (!isset($this->storage[$offset])) {
				/* Noncontinously stored */
				return $this->getFilesLowLevel($skip,$limit);
			}
			$ids[] = $this->storage[$offset];
		}
		/* Check if cache is terminated */
		if (($limit === false || $this->fetchedTo < $upper) && !$this->terminated) {
			$addids = $this->getFilesLowLevel($this->fetchedTo,$limit !== false ? $upper-$this->fetchedTo : false);
			foreach ($addids as $id)
				$ids[] = $id;
		}
		return $ids;
	}
	public function getFileArticles($skip=0,$limit=false)
	{
		return new SERIA_FileQueryArticleArray($this->getFileArticleIds($skip,$limit)); 
	}
}