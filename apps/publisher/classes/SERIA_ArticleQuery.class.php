<?php

	/**
	*	Create object to perform searches on the Articles database.
	*/
	class SERIA_ArticleQuery
	{
		protected $query = "";
		protected $queryWords;
		protected $isPublished = false;
		protected $order = false;
		protected $type;
		protected $idIs = false;
		protected $titleIs = false;
		protected $whereTree = false;
		protected $sql = array();
		protected $dateAfter = array();
		protected $dateBefore = array();
		protected $excludedTypes = array();

		function __construct($type=false)
		{
			$this->type = $type;
			switch(SERIA_Base::viewMode())
			{
				case "public" : // allow only published articles to be returned
					$this->isPublished();
					break;
				case "admin" : // allow anything
					break;
				default : 
					throw new SERIA_Exception("Unknown view mode.");
			}
		}

		private static function int_and($obj1, $obj2)
		{
			if (!isset($obj1->type))
				throw new Exception('Extraction of protected values fails!');
			if ($obj1->type !== $obj2->type)
				throw new Exception('Both queries must be of same type!');
			$result = new SERIA_ArticleQuery($obj1->type);
			$res1 = $obj1->generateWhereParts();
			$res2 = $obj2->generateWhereParts();
			if ($res1 === false) {
				$res1 = $res2;
				$res2 = false;
			}
			if ($res2 !== false) {
				$res1 = array(
					'op' => 'AND',
					$res1, $res2
				);
			}
			$result->whereTree = $res1;
			return $result;
		}
		private static function int_or($obj1, $obj2)
		{
			if (!isset($obj1->type))
				throw new Exception('Extraction of protected values fails!');
			if ($obj1->type !== $obj2->type)
				throw new Exception('Both queries must be of same type!');
			$result = new SERIA_ArticleQuery($obj1->type);
			$res1 = $obj1->generateWhereParts();
			$res2 = $obj2->generateWhereParts();
			if ($res1 === false) {
				$res1 = $res2;
				$res2 = false;
			}
			if ($res2 !== false) {
				$res1 = array(
					'op' => 'OR',
					$res1, $res2
				);
			}
			$result->whereTree = $res1;
			return $result;
		}

		/**
		 * Generates a new query object for articles that matches all of the supplied queries.
		 *
		 * @param array $objs
		 * @return SERIA_ArticleQuery
		 */
		public static function _and($objs)
		{
			$prim = false;
			foreach ($objs as $obj) {
				if ($obj === null)
					continue;
				if ($prim === false) {
					$prim = $obj;
					continue;
				}
				$prim = self::int_and($prim, $obj);
			}
			return $prim;
		}
		/**
		 * Generates a new query object for articles that matches at least one of the supplied queries.
		 *
		 * @param array $objs
		 * @return SERIA_ArticleQuery
		 */
		public static function _or($objs)
		{
			if (!count($objs))
				return false;
			$obj1 = $objs[0];
			if (!isset($obj1->type))
				throw new Exception('Extraction of protected values fails!');
			$result = new SERIA_ArticleQuery($obj1->type);
			$parts = array('op' => 'OR');
			foreach ($objs as $obj) {
				if ($obj->type !== $obj1->type)
					throw new Exception('All queries must be of same type!');
				$parts[] = $obj->generateWhereParts();
			}
			$result->whereTree = $parts;
			return $result;
		}

		/**
		*	Will return a complete Media RSS-feed (http://search.yahoo.com/mrss/) containing the search results
		*/
		function outputMRSS($feedTitle, $start=0, $length=10, $url=false, $description=false, $linkCallback=false)
		{
			$res = '<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
	<channel>
		<title><![CDATA['.$feedTitle.']]></title>';

			if($url) $res .= '
		<link>'.htmlspecialchars($url).'</link>';

			$res .= '
		<description><![CDATA['.($description?$description:"").']]></description>';

			$articles = $this->page($start, $length);

			foreach($articles as $article)
			{
				$res .= '
		<item>';
				if($linkCallback) $res .= '
			<link>'.htmlspecialchars($linkCallback($article)).'</link>';

				$data = $article->getAbstract();

				$res .= '
			<guid>'.htmlspecialchars($data["guid"]).'</guid>
			<title>'.htmlspecialchars($data["title"]).'</title>
			<description>'.htmlspecialchars($data["description"]).'</description>';

				if($data["image"])
				{
					$dotPos = strrpos($data["image"], ".");
					$ext = strtolower(substr($data["image"], $dotPos+1));
					$mimeType = false;
					switch($ext)
					{
						case "jpg" : case "jpeg" : $mimeType = "image/jpeg"; break;
						case "gif" : $mimeType = "image/gif"; break;
						case "png" : $mimeType = "image/png"; break;
					}
					if($mimeType)
					{
						$res .= '
			<enclosure url="'.htmlspecialchars($data["image"]).'" type="'.$mimeType.'" length="" />';
					}
				}

				if($data["enclosure"])
				{
					$res .= '
			<enclosure url="'.htmlspecialchars($data["enclosure"]["url"]).'" length="'.htmlspecialchars($data["enclosure"]["length"]).'" type="'.htmlspecialchars($data["enclosure"]["type"]).'" />';
				}

				if($data["enclosures"])
				{
					foreach($data["enclosures"] as $e)
						$res .= '
			<enclosure url="'.htmlspecialchars($e["url"]).'" length="'.htmlspecialchars($e["length"]).'" type="'.htmlspecialchars($e["type"]).'" />';

				}

				if($data["media:group"])
				{
					$res .= '
			<media:group>';

					foreach($data["media:group"] as $mg)
					{
						$attrs = array();
						if($mg["url"]) $attrs[] = "url=\"".htmlspecialchars($mg["url"])."\"";
						if($mg["fileSize"]) $attrs[] = "fileSize=\"".htmlspecialchars($mg["fileSize"])."\"";
						if($mg["type"]) $attrs[] = "type=\"".htmlspecialchars($mg["type"])."\"";
						if($mg["medium"]) $attrs[] = "medium=\"".htmlspecialchars($mg["medium"])."\"";
						if($mg["isDefault"]) $attrs[] = "isDefault=\"true\"";
						if($mg["expression"]) $attrs[] = "expression=\"".htmlspecialchars($mg["expression"])."\"";
						if($mg["bitrate"]) $attrs[] = "bitrate=\"".htmlspecialchars($mg["bitrate"])."\"";
						if($mg["framerate"]) $attrs[] = "framerate=\"".htmlspecialchars($mg["framerate"])."\"";
						if($mg["samplingrate"]) $attrs[] = "samplingrate=\"".htmlspecialchars($mg["samplingrate"])."\"";
						if($mg["channels"]) $attrs[] = "channels=\"".htmlspecialchars($mg["channels"])."\"";
						if($mg["duration"]) $attrs[] = "duration=\"".htmlspecialchars($mg["duration"])."\"";
						if($mg["height"]) $attrs[] = "height=\"".htmlspecialchars($mg["height"])."\"";
						if($mg["width"]) $attrs[] = "width=\"".htmlspecialchars($mg["width"])."\"";
						if($mg["lang"]) $attrs[] = "lang=\"".htmlspecialchars($mg["lang"])."\"";
						$res .= '
				<media:content ".implode(" ", $attrs)." />';
					}

					$res .= '
			</media:group>';
				}

				if($data["media:content"])
				{
					$mg = $data["media:content"];
					$attrs = array();
					if($mg["url"]) $attrs[] = "url=\"".htmlspecialchars($mg["url"])."\"";
					if($mg["fileSize"]) $attrs[] = "fileSize=\"".htmlspecialchars($mg["fileSize"])."\"";
					if($mg["type"]) $attrs[] = "type=\"".htmlspecialchars($mg["type"])."\"";
					if($mg["medium"]) $attrs[] = "medium=\"".htmlspecialchars($mg["medium"])."\"";
					if($mg["isDefault"]) $attrs[] = "isDefault=\"true\"";
					if($mg["expression"]) $attrs[] = "expression=\"".htmlspecialchars($mg["expression"])."\"";
					if($mg["bitrate"]) $attrs[] = "bitrate=\"".htmlspecialchars($mg["bitrate"])."\"";
					if($mg["framerate"]) $attrs[] = "framerate=\"".htmlspecialchars($mg["framerate"])."\"";
					if($mg["samplingrate"]) $attrs[] = "samplingrate=\"".htmlspecialchars($mg["samplingrate"])."\"";
					if($mg["channels"]) $attrs[] = "channels=\"".htmlspecialchars($mg["channels"])."\"";
					if($mg["duration"]) $attrs[] = "duration=\"".htmlspecialchars($mg["duration"])."\"";
					if($mg["height"]) $attrs[] = "height=\"".htmlspecialchars($mg["height"])."\"";
					if($mg["width"]) $attrs[] = "width=\"".htmlspecialchars($mg["width"])."\"";
					if($mg["lang"]) $attrs[] = "lang=\"".htmlspecialchars($mg["lang"])."\"";
					$res .= '
			<media:content ".implode(" ", $attrs)." />';
				}

				$res .= '
		</item>';
			}
			$res .= '
	</channel>
</rss>';

			return $res;
		}

		function excludeType($articleType)
		{
			$this->excludedTypes[] = $articleType;
		}

		/**
		*	Prepares each word for database query. If $prefix is specified, each word is prefixed with $prefix
		*/
		function where($words, $prefix=false)
		{
			$words = trim($words);
			if($words=="") return;
			$parts = explode(" ", $words);
			foreach($parts as $word)
			{
				$this->query .= " ";
				if($word[0]!="+" && $word[0]!="-")
				{
					$this->query .= "+";
				}
				else
				{
					$this->query .= $word[0];
					$word = ltrim($word, "+-");
				}
				if($prefix) $word = $prefix.$word;
				$this->query .= SERIA_7Bit::word($word);
			}
			$this->query = trim($this->query);
			return $this;
		}

		function idIs($id) {
			$this->idIs = $id;
			return $this;
		}

		function titleIs($title)
		{
			$this->titleIs = $title;
			return $this;
		}

		function inCategory($category)
		{
			$this->where($category->get("id"), "@category_id:");
			return $this;
		}

		function inCategoryId($categoryId)
		{
			$this->where($categoryId, "@category_id:");
			return $this;
		}

		function isPublished()
		{
			$this->isPublished = 1;
			return $this;
		}

		function isNotPublished()
		{
			$this->isPublished = 0;
			return $this;
		}

		/*
		 * WARNING: SQL relying search functions might disable search accelerators!
		 */
		public function addSql($sql)
		{
			$this->sql[] = $sql;
		}
		public function startDateAfter($tm)
		{
			$this->dateAfter['start_date'] = $tm;
		}
		public function startDateBefore($tm)
		{
			$this->dateBefore['start_date'] = $tm;
		}

		function order($order)
		{
			$this->order = $order;
			return $this;
		}

		function digestWhereTree($whereTree)
		{
			$sql = false;
			$op = 'AND';
			if (isset($whereTree['op'])) {
				$op = $whereTree['op'];
				unset($whereTree['op']);
			}
			$sql = array();
			foreach ($whereTree as $nam => $val) {
				if (is_array($val))
					$val = self::digestWhereTree($val);
				$sql[] = $val;
			}
			if (count($sql) == 0)
				return false;
			return '('.implode(' '.$op.' ', $sql).')';
		}

		function generateWhereParts()
		{
			$db = SERIA_Base::db();
			$whereParts = array();

			$query = "";

			if($this->query)
			{
				$queryParts = explode(" ",trim($this->query));
				foreach($queryParts as $part)
				{
					if($part[0]!="-" && $part[0]!="+")
						$part = "+".$part;
					$query .= " ".$part;
				}
			}
			if($this->type)
				$query .= " +".SERIA_7Bit::word("@type:".$this->type);
			
			$query = trim($query);
			if($query)
			{
				if($this->isPublished!==false)
				{
					if($this->isPublished)
						$query .= " +".SERIA_7Bit::word("@is_published");
					else
						$query .= " -".SERIA_7Bit::word("@is_published");
				}
				$whereParts[] = "id IN (SELECT id FROM ".SERIA_PREFIX."_articles_fts WHERE MATCH (ft) AGAINST (".$db->quote(trim($query))." IN BOOLEAN MODE))";

			}
			else
			{
				if($this->isPublished!==false)
				{
					$whereParts[] = "is_published=".$db->quote($this->isPublished);
				}
			}

			if(sizeof($this->excludedTypes))
				foreach($this->excludedTypes as $et)
					$whereParts[] = "type<>".$db->quote($et);

			if($this->idIs !== false) {
				$whereParts[] = "id = ".$db->quote($this->idIs);
			}
			if ($this->titleIs !== false) {
				$whereParts[] = "title = ".$db->quote($this->titleIs);
			}

			if ($this->whereTree !== false) {
				$digest = self::digestWhereTree($this->whereTree);
				if ($digest !== false)
					$whereParts[] = $digest;
			}

			foreach ($this->sql as $sql)
				$whereParts[] = $sql;

			foreach ($this->dateAfter as $field => $tm)
				$whereParts[] = $field.' > '.$db->quote(date('Y-m-d H:i:s', $tm));
			foreach ($this->dateBefore as $field => $tm)
				$whereParts[] = $field.' < '.$db->quote(date('Y-m-d H:i:s', $tm));

			return $whereParts;
		}
		function generateWhere()
		{
			$whereParts = $this->generateWhereParts();

			if (count($whereParts) == 0)
				return '';

			return " WHERE ".implode(" AND ", $whereParts);
		}

		function count() { return $this->totalArticles(); }

		function totalArticles()
		{
			$db = SERIA_Base::db();

			$sql = "SELECT COUNT(id) AS 'total' FROM ".SERIA_PREFIX."_articles".$this->generateWhere();
			$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
			return $rows[0]['total'];
		}

		function page($start, $length)
		{
			$db = SERIA_Base::db();

//			if(SERIA_Base::user() && !SERIA_Base::isAdministrator() && !SERIA_Base::hasRight('view_others_articles'))
//			{
//				$this->where('@author_id:'.SERIA_Base::user()->get("id"));
//			}

			$sql = "SELECT id,type FROM ".SERIA_PREFIX."_articles".$this->generateWhere();
			if($this->order)
				$sql .= " ORDER BY ".$this->order;

			$sql .= " LIMIT $start, $length";
			$cache = new SERIA_Cache('SArtQuery');
			if(!($rows = $cache->get(md5($sql))))
			{
				$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
				$cache->set(md5($sql), $rows, 60); // only cache for five minutes
			}


			$res = array();

			foreach($rows as $row)
			{
				try
				{
					$res[] = SERIA_Article::createObject($row["type"], $row['id']);
				} catch (SERIA_Exception $e) {
				}
			}
				
			return $res;
		}
	}
