<?php
/*

TODO:

2.	Articles may depend on other articles. Make a method that ensures if the main article is deleted, then the depending articles are deleted.
3.	Articles may have tags. It must be possible to quickly count the occurrances of a tag.
*/


	abstract class SERIA_Article extends SERIA_EventDispatcher
	{
	
		/**
		* Support some form of prototyping - developers can define functions named
		* SERIA_Article_<methodname>($self, $param1, $param2[, $param3]) and it will
		* magically be called when $articleObject-><methodname> is called. Example:
		* 
		* function NewsArticle_echoname($self)
		* {
		* 	echo $self->getTypeName();
		* } 
		* 
		* $newsArticleObject->echoname() // echoes out the type name
		*/
		public function __call($name, $params)
		{
			if(function_exists("SERIA_Article_".$name))
			{
				$params = array_merge(array($this), $params);
				return call_user_func_array("SERIA_Article_".$name, $params);
			}
			else
				throw new Exception("Unknown method '$name'.");
		}
		
		// Used when saving. Events is thrown if one of following properties is set by publish() or unpublish()
		private $publishEvent = false;
		private $unpublishEvent = false;
		
		// Return a string with the name of this article type.
		abstract static function getTypeName();
		// Return a string with a description of this article type.
		abstract static function getTypeDescription();

		// Developers: Change this to match class name, without "Article" suffix. For example News here matches NewsArticle class.
		protected $type = "";

		/**
		*	Developers: Add field names that are to be saved in $this->extra. Associative array with type specified by string.
		*
		*	Example:
		*	array(
		*		"department" => array("it","economy","sales"),	// value must be one of "id", "economy" or "sales", add NULL to array if you allow this value to be empty
		*		"salary" => "float,required",			// value must be float and is required
		*		"birthdate" => "datetime",			// value must be timestamp
		*		"image" => "file",				// value must be a file id (referencing a SERIA_File object)
		*	)
		*/
		protected $fields;

		/**
		*	array(
		*		"department" => "dep:",		// Makes the field "department" searchable by using the prefix "dep:word" or just "word" in the query
		*	),
		*/
		protected $fulltextFields;
		
		protected $publishDelay = 0;

		/**
		*	Abstract functions for embedding this article type into SERIA Platform GUI
		*/

		/**
		*	This function is used to generate structured data such as an RSS feed
		*	or similar out of this article. The return format is an array containing
		*	at least "title" and "description".
		*
		*	$query may be provided as a string of words which you can use to
		*	customize the description.
		*
		*	return array(
		*		"guid" => "Globally unique ID",		required
		*		"title" => "Article title", 		required
		*		"description" => "Article description", required, MAY contain HTML but not recommended.
		*		"author" => array(			optional
		*			"name" => "John Doe",		optional, the name of the author
		*			"email" => "john@doe.com",	optional, the authors email address
		*		),
		*		"image" => "http://imageurl/", 		optional
		*		"link" => "http://link/",		optional, and only if it is constant (for example for a file). May be overridden by templates.
		*		"enclosure" => array(			optional, a link to a multimedia item such as music with required info
		*			"url" => "http://url/",		url where the multimedia is located
		*			"length" => bytes,		length in bytes where the multimedia is located
		*			"type" => "audio/mpeg",		mime type of the multimedia
		*		),
		*		"media:group":				array of "media:content"-items, see below (see also http://search.yahoo.com/mrss)
		*							It allows grouping of <media:content> elements that are effectively the same content, yet different representations.   For instance: the same song recorded in both the WAV and MP3 format. It's an optional element that must only be used for this purpose
		*		"media:content" => array(
		*			"url" => "http://url/",
		*			"fileSize"
		*			"type"				mime type
		*			"medium"			image | audio | video | document | executable
		*			"height"
		*			"width"
		*			"isDefault"			determines if this is the default object that should be used for the <media:group>. There should only be one default object per <media:group>. It is an optional attribute.
		*		),
		*/
		abstract function getAbstract();

		/**
		*	This function returns HTML used to generate the form part for this article type.
		*
		*	1. Do not return XHTML and do not include <form> tags. Field names must begin with $prefix.
		*	2. Do not handle categories and tags in your form as they will be handled by the master form handler.
		*/
		abstract function getForm($prefix=false, $validationErrors=array());
		
		/**
		 * Returns an array of links that will be visible in the article GUI
		 *
		 * @param string $caption
		 * @param string $onclick
		 */
		function getAdminLinks() {
			// $caption, $onclick
			return array();
		}
	
		/**
		*	Returns article fields
		*/
		function getFields() {
			return $this->fields;
		}

		/**
		*	Update all fields
		*/
		protected function updateFields() {

			$a = SERIA_Article::createObject(substr(get_class($this),0,-7));
			$this->fields = $a->getFields();
		}

		/**
		*	Handles post data and other bulk updates to the database. It receives data in the 
		*	same form as a general $_POST associative array. Optional parameter $prefix is
		*	used to filter fields. Example:
		*
		*	->receiveData(array(
		*		"form_name" => "John Hanson",
		*	), "form_");
		*
		*	This will parse all fields starting with "form_". It will ofcourse remove the prefix
		*	before trying to save the data.
		*
		*	If the field does not exist, it will not attempt to set it so you can safely
		*	have extra fields in your form - and they will be ignored here.
		*/
		function receiveData($data, $prefix="")
		{
			$changes = 0;
			$l = strlen($prefix);
			foreach($data as $key => $value)
			{
				if($prefix==="" || strpos($key, $prefix)===0)
				{
					$key = substr($key, $l);

					try {
						$this->set($key, $value);
						$changes++;
					}
					catch(SERIA_Exception $e)
					{
						// since forms often contain extra fields that must not be saved, we ignore errors here
					}
				}
			}

			return $changes;
		}

                /**
                *       Find related articles (by comparing words with other articles) and sorting by relevance.
                *
                *       @param int $relevanceFactor
                *               A number larger than 1 that specifies minimum relevancy factor, 6 or larger is recommended.
		*	@param int $limit
		*		The maximum number of articles to return
                *
                *       @returns array
                */
                function findSimilar($relevanceFactor=0.5, $limit=10)
                {
			$fts = $this->get("title")." ";
			foreach($this->fulltextFields as $field => $prefix)
				$fts .= $this->get($field)." ";

			$ftsWords = explode(" ", $fts);
			$fts = "";
			foreach($ftsWords as $word)
				if($word[0]!="@")
					$fts .= $word." ";

			$requiredWords = "";
			switch(SERIA_Base::viewMode())
			{
				case "public" : // only allow this article if it is published
					$requiredWords .= "+".SERIA_7Bit::word("@is_published")." ";
					break;
				case "admin" : // full access
					break;
				default : 
					throw new SERIA_Exception("Unknown view mode");
			}
			$requiredWords .= "+".SERIA_7Bit::word("@type:".$this->type)." ";

			$db = SERIA_Base::db();
			$sql = "SELECT id,MATCH(ft) AGAINST (".$db->quote($fts).") AS relevance FROM ".SERIA_PREFIX."_articles_fts WHERE MATCH(ft) AGAINST (".$db->quote($fts).")>=$relevanceFactor AND MATCH(ft) AGAINST (".$db->quote($requiredWords)." IN BOOLEAN MODE) AND id<>".$this->get("id")." ORDER BY relevance DESC LIMIT $limit";
			$cache = new SERIA_Cache('SArticleSimilar');
			if(!($rows = $cache->get($sql)))
			{
				$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
				$cache->set($sql, $rows, 600);
			}

			$res = array();
			foreach($rows as $row)
			{
				$res[$row["id"]] = SERIA_Article::createObject($this->type, $row["id"]);
				$res[$row["id"]]->findSimilarRelevance = $row["relevance"];
			}

			return $res;
                }

		/**
		*	Voting for an article.
		*/
		function vote($score) {
			$rating = $this->row["rating"];
			$votes = $this->row["rating_counter"];
			$totalScore = $rating * $votes + $score;
			$votes++;
			$rating = $totalScore / $votes;
			$this->row["rating"] = $rating;
			$this->row["rating_counter"] = $votes;
			$this->isDirty = true;

			$isWritable = $this->isWritable;
			$this->isWritable = true;
			$this->save();
			$this->isWritable = $isWritable;

		}

		/**
		*	Check that all required values are set, and check that they have correct type.
		*
		*	Developers: If you override this function, you must always remember to call
		*	parent::validateData() before you start validating
		*/
		function validateData()
		{
			// accumulate all errors into this array with $fieldName => $errorMessage
			$errors = array();

			// validate all fields that are going directly into the database table

			if($this->row["content_language"]===false)
				$errors["content_language"] = _t("Required.");
			if($e = SERIA_IsInvalid::name($this->row["author_name"], false))
				$errors["author_name"] = $e;
			if($e = SERIA_IsInvalid::email($this->row["author_email"], false))
				$errors["author_email"] = $e;
			if($e = SERIA_IsInvalid::number($this->row["start_date"], false, false, false, true))
				$errors["start_date"] = $e;
			if($e = SERIA_IsInvalid::number($this->row["end_date"], false, false, false, true))
				$errors["end_date"] = $e;
			if($e = SERIA_IsInvalid::number($this->row["event_date"], false, false, false, true))
				$errors["event_date"] = $e;

			if($e = SERIA_IsInvalid::real($this->row["rating"], true))
				$errors["rating"] = $e;
			if($e = SERIA_IsInvalid::real($this->row["score"], true))
				$errors["score"] = $e;

			if($e = SERIA_IsInvalid::integer($this->row["votes"], true))
				$errors["votes"] = $e;

			if($e = SERIA_IsInvalid::real($this->row["price"], true))
				$errors["price"] = $e;

			if($e = SERIA_IsInvalid::integer($this->row["views"], true))
				$errors["views"] = $e;

			if($e = SERIA_IsInvalid::name($this->row["title"], true))
				$errors["title"] = $e;


			// validate all fields according to definitions in $this->fields
			foreach($this->fields as $key => $type)
			{
				try
				{
					$types = array_flip(explode(",", $type));
					if(isset($types["array"]))
					{
						foreach($this->row as $name => $value)
						{
							if(($split = strpos($name, "|"))!==false)
								if(substr($name,0,$split)==$key)
									SERIA_Article::validateByType($value, $type);
						}
					} 
					else
						SERIA_Article::validateByType($this->extra[$key], $type);
				}
				catch (SERIA_ValidationException $e)
				{
					$errors[$key] = $e->getMessage();
				}
			}

			if(sizeof($errors)>0)
				throw new SERIA_ValidationException(_t("Validation errors."), $errors);

			return $this;
		}

		protected
			$isWritable = false,
			$row = array(),
			$originalRow = array(),		// keeps the original values of $row, to check if values have changed
			$originalExtra = array(),	// keeps the original values of $extra, to check if values have changed
			$categories = false,
			$extra = false,
			$isDirty = false,
			$isDirtyCategories = false;
			
		/* Methods for standarized data query from search */
		
		protected $titleField = false;
		protected $descriptionField = false;
		
		public function getTitle() {
			$data = $this->getAbstract();
			return $data['title'];
		}
		
		public function getDescription() {
			$data = $this->getAbstract();
			return $data['description'];
		}
		
		public function __get($var) {
			if ($var == 'db') {
				return SERIA_Base::db();
			}
		}
		public function __set($var, $value) {
		}

		/**
		*	Loads an article without knowing its type
		*/
		static function createObjectFromId($id)
		{
			$id = intval($id);
			if(!$id)
				throw new SERIA_Exception("Illegal article id.");

			$row = SERIA_Base::db()->query("SELECT id, type FROM ".SERIA_PREFIX."_articles WHERE id=".SERIA_Base::db()->quote($id))->fetch(PDO::FETCH_ASSOC);
			if (!$row)
				throw new SERIA_NotFoundException('No article found with ID: '.$id);
			return SERIA_Article::createObject($row["type"], $row["id"]);
		}
		
		public function getObjectId() {
			return array(get_class($this), 'createObjectFromId', /*force-string:*/''.$this->get('id'));
		}

		/**
		*	Creates a new object and caches it in memory
		* 
		*  @return SERIA_Article
		*/
		static function createObject($type,$id=false)
		{
			// Named object request, fetch object type from database
/*			if (is_numeric($type) && ($id === false)) {
				$id = $type;
				$query = 'SELECT type FROM ' . SERIA_PREFIX . '_articles WHERE id=' . $id;
				$queryResult = SERIA_Base::db()->query($query)->fetchAll(PDO::FETCH_NUM);
				$type = $queryResult[0][0];
				if (!$type) {
					throw new SERIA_Exception('Article not found');
				}
			} 
*/

			$row = false;

			$className = $type."Article";

			if(!class_exists($className))
				throw new SERIA_Exception("No such article type '$type' ($className).");

			if($id === false)
				return new $className(false, true);

			if(is_array($id))
			{
				$row = $id;
				$id = $row["id"];
			}

			if($row)
			{
				$o = new $className($row, true);
			}
			else
			{
				// try cache
				if($o = self::getFromCache($className, $id))
					return $o;
				$o = new $className($id, true);
				$o->saveToCache();
			}

			return $o;
		}

		protected function saveToCache()
		{
			if(!$this->row['id']) return;
			$cache = new SERIA_Cache('SArticleObjects');
			$cache->set(get_class($this).'|'.$this->row['id'], $this, 600);
		}

		protected static function getFromCache($className, $id)
		{
			$cache = new SERIA_Cache('SArticleObjects');
			$object = $cache->get($className.'|'.$id);
			return $object;
		}

		protected function clearFromCache()
		{
			if(!$this->row['id']) return;
			$cache = new SERIA_Cache('SArticleObjects');
			return $cache->set(get_class($this).'|'.$this->row['id']);
		}

		/**
		*	Constructor
		*/
		function __construct($id=false, $fromCreateObject=false)
		{
			if(!$fromCreateObject)
				throw new SERIA_Exception("Must use SERIA_Article::createObject to create objects.");

			if($this->type=="")
				throw new SERIA_Exception("Variable \$this->type not overridden. See SERIA_Article.class.php for documentation.");

			$this->db = SERIA_Base::db();

			if($id)
			{ // create object from existing data array
				if(is_array($id))
				{
					$r = $id;
					$id = $r["id"];
				}
				else
				{
					$rs = $this->db->query("SELECT * FROM ".SERIA_PREFIX."_articles WHERE id=".$this->db->quote($id)." AND type=".$this->db->quote($this->type));
					if(!($r = $rs->fetch(PDO::FETCH_ASSOC)))
						throw new SERIA_Exception("Article '".$id."' with the type '".$this->type."' does not exist.");

					$rs->closeCursor();
					unset($rs);
				}
				/*
				 * Preserve NULL values..
				 */
/*
				$nv = array();
				foreach ($r as $name => $val) {
					if (!$val)
						$nv[] = 'IF('.$this->db->quote($name).' IS NOT NULL, 1, 0) AS '.$this->db->quote($name);
				}
				if (count($nv) > 0) {
					$nv = $this->db->query('SELECT '.implode(',', $nv).' FROM '.SERIA_PREFIX.'_articles WHERE id='.$this->db->quote($id)." AND type=".$this->db->quote($this->type))->fetchAll();
					$nv = $nv[0];
					foreach ($nv as $name => $val) {
						if (!$val)
							$r[$name] = NULL;
					}
				}
*/				
				switch(SERIA_Base::viewMode())
				{
					case "public" : // only allow this article if it is published
						if(!$r["is_published"])
							throw new SERIA_Exception("Not published.");
						break;
					case "admin" : // full access
						break;
					default : 
						throw new SERIA_Exception("Unknown view mode");
				}

				$this->isWritable = false;
				$this->row = $r;
				if($this->row["start_date"])
					$this->row["start_date"] = strtotime($this->row["start_date"]);
				if($this->row["end_date"])
					$this->row["end_date"] = strtotime($this->row["end_date"]);
				$this->row["created_date"] = strtotime($this->row["created_date"]);
				$this->row["altered_date"] = strtotime($this->row["altered_date"]);
				$this->row["published_date"] = strtotime($this->row["published_date"]);
				$this->row["event_date"] = strtotime($this->row["event_date"]);
				$this->extra = unserialize($r["extra"]);
			}
			else
			{ // creating a new object

				/*
				 * Administrator can be tempoarily granted.
				 */
				if(!SERIA_Base::hasRight("create_article") && !SERIA_Base::isAdministrator())
					throw new SERIA_Exception("Access denied creating article. Requires privilege create_article.");

				$this->row["id"] = false;
				$this->row["type"] = $type;
				$this->row["content_id"] = false;
				$this->row["content_language"] = "default";
				if (SERIA_Base::user()) {
					$this->row["author_name"] = SERIA_Base::user()->get("display_name");
					$this->row["author_email"] = SERIA_Base::user()->get("email");
					$this->row["author_id"] = SERIA_Base::user()->get("id");
				} else {
					$this->row["author_name"] = NULL;
					$this->row["author_email"] = NULL;
					$this->row["author_id"] = NULL;
				}
				$this->row["start_date"] = false;
				$this->row["end_date"] = false;
				$this->row["created_date"] = time();
				$this->row["altered_date"] = $this->row["created_date"];
				$this->row["published_date"] = false;
				$this->row["event_date"] = false;
				$this->row["rating"] = 0;
				$this->row["rating_counter"] = 0;
				$this->row["score"] = 0;
				$this->row["votes"] = 0;
				$this->row["price"] = 0;
				$this->row["views"] = 0;
				$this->row["is_published"] = false;
				$this->row['pending_publish'] = true;
				$this->row["title"] = false;
				$this->row["extra"] = NULL;
				$this->row["ft"] = "";
				$this->row["ft_changed_ts"] = false;
				$this->row["tags"] = "";
				$this->row["notes"] = "";
				$this->extra = array();

				$this->isWritable = true;
				$this->originalRow = $this->row;
				$this->originalExtra = $this->extra;
			}
		}

		function getContextMenu()
		{
			if(SERIA_Base::user() === false)
				return "";

			$items = array();
			if($this->getAuthor()==SERIA_Base::user() || SERIA_Base::hasRight("edit_others_articles"))
				$items[] = "Edit article <strong>".htmlspecialchars(str_replace(':', '&#58;', $this->get("title")))."</strong>:top.SERIA.editArticle(".$this->get("id").");";
			else if(SERIA_Base::hasRight("create_article") || SERIA_Base::hasRight("publish_article") || SERIA_Base::hasRight("edit_others_articles"))
				$items[] = "View article <strong>". htmlspecialchars(str_replace(':', '&#58;',$this->get("title")))."</strong>:top.SERIA.editArticle(".$this->get("id").");";

			if(sizeof($items))
				return " mnu=\"".implode("|", $items)."\" ";

			return "";
		}


		function getAuthor()
		{
			if ($this->get("author_id") !== NULL)
				return SERIA_User::createObject($this->get("author_id"));
			else
				return false;
		}

		/**
		*	Add a tag to this article
		*/
		function addTag($tag)
		{
			if(!$this->isWritable)
				throw new SERIA_Exception("Article is not writable. Use ->writable() to enable.");

			$tag = mb_strtolower(trim($tag), "UTF-8");

			$tags = array();

			$lines = explode("\n", $this->row["tags"]);
			foreach($lines as $line)
			{
				$line = trim($line);
				if(trim($line)!=$tag)
					$tags[] = $line;
			}
			$tags[] = $tag;
			$this->row["tags"] = trim(implode("\n", $tags));
			$this->isDirty = true;
		}

		function removeTag($tag)
		{
			if(!$this->isWritable)
				throw new SERIA_Exception("Article is not writable. Use ->writable() to enable.");

			$tag = mb_strtolower(trim($tag), "UTF-8");

			$tags = array();

			$lines = explode("\n", $this->row["tags"]);
			foreach($lines as $line)
				if($line!=$tag)
					$tags[] = $line;
			$this->row["tags"] = implode("\n", $tags);
			$this->isDirty = true;
		}

		function getTags()
		{
			return explode("\n", $this->row["tags"]);
		}
		
		/* Finds all files for this article */
		public function getFileIds() {
			$fileIds = array();
			foreach ($this->fields as $fieldName => $types) {
				$types = explode(",", $types);
				if (in_array("file", $types)) {
					$fileId = (int) $this->get($fieldName);
					if($fileId)
					{
						$fileIds[] = $fileId;
					}
				}
			}
			return $fileIds;
		}
		
		function getFiles()
		{
			$files = array();
			$fileIds = $this->getFileIds();
			$files = SERIA_File::createObjects($fileIds);
			return $files;
		}

		/**
		*	Add this article to category
		*
		*	@param $category	SERIA_ArticleCategory
		*/
		function addToCategory($category)
		{
			if(!$this->isWritable)
				throw new SERIA_Exception("Article is not writable. Use ->writable() to enable.");

			if($this->categories===false)
				$this->loadCategories();

			$id = $category->getId();
			if(!$this->categories[$id])
			{
				$this->categories[$id] = $category;
				$this->isDirtyCategories = true;
			}
		}

		function removeAllCategories()
		{
			if(!$this->isWritable)
				throw new SERIA_Exception("Article is not writable. Use ->writable() to enable.");

			if(sizeof($this->categories) > 0) {
				$this->categories = array();
				$this->isDirtyCategories = true;
			}
		}

		function removeFromCategory($category)
		{
			if(!$this->isWritable)
				throw new SERIA_Exception("Article is not writable. Use ->writable() to enable.");

			if($this->categories===false)
				$this->loadCategories();

			$id = $category->getId();
			if($this->categories[$id])
			{
				unset($this->categories[$id]);
				$this->isDirtyCategories = true;
			}
		}

		function getCategories()
		{
			if($this->categories===false)
				$this->loadCategories();

			return $this->categories;
		}

		protected function loadCategories()
		{
			$this->categories = array();

			if(!$this->row["id"])
			{
				$this->isDirtyCategories = true;
				return NULL;
			}

			$cats = $this->db->query("SELECT * FROM ".SERIA_PREFIX."_article_categories LEFT JOIN ".SERIA_PREFIX."_articles_categories ON ".SERIA_PREFIX."_article_categories.id=".SERIA_PREFIX."_articles_categories.category_id WHERE ".SERIA_PREFIX."_articles_categories.article_id=".$this->row["id"])->fetchAll(PDO::FETCH_ASSOC);
			foreach($cats as $key => $row)
			{
				try 
				{
					$this->categories[$row["id"]] = SERIA_ArticleCategory::getNodeByRow($row);
				}
				catch (SERIA_Exception $e)
				{
					if($e->getMessage()=="Not published.")
						unset($this->categories[$row["id"]]);
					else
						throw $e;
				}
			}
			$this->isDirtyCategories = false;
		}

		protected function saveCategories()
		{
			
			if($this->categories===false)
				throw new Exception("Categories should always be loaded - if not then isDirtyCategories should be false...");
			if(!$this->row["id"])
				throw new Exception("Can't save categories without article id in \$this->row. Bug somewhere in SERIA_Article.");

			$existingCategories = $this->db->query("SELECT category_id FROM ".SERIA_PREFIX."_articles_categories WHERE article_id=".$this->db->quote($this->row["id"]))->fetchAll(PDO::FETCH_COLUMN, 0);
			$categoriesToRemove = array();

			foreach($existingCategories as $key => $existingCategoryId)
			{
				$found = false;
				
				foreach($this->categories as $newCategoryId => $category) {
					if($newCategoryId==$existingCategoryId)
						$found=true;
				}
				if(!$found)
				{
					$categoriesToRemove[] = $existingCategoryId;
				}
			}
			
			$categoriesToAdd = array();
			foreach($this->categories as $newCategoryId => $category)
			{
				$found = false;
				foreach($existingCategories as $existingCategoryId) {
					if($newCategoryId==$existingCategoryId)
						$found = true;
				}

				if(!$found)
					$categoriesToAdd[] = $newCategoryId;
			}

			foreach($categoriesToRemove as $catId)
				$this->db->exec("DELETE FROM ".SERIA_PREFIX."_articles_categories WHERE article_id=".$this->db->quote($this->row["id"])." AND category_id=".$this->db->quote($catId));

			foreach($categoriesToAdd as $catId) {
				$this->db->exec("INSERT INTO ".SERIA_PREFIX."_articles_categories (article_id, category_id) VALUES (".$this->db->quote($this->row["id"]).",".$this->db->quote($catId).")");
			}
		}

		/**
		*	Set any editable value within the article type.
		*/
		function set($name, $value)
		{
			if(!$this->isWritable)
				throw new SERIA_Exception("Article is not writable. Use ->writable() to enable.");

			switch($name)
			{
				// not editable fields
				case "id" : case "type" : case "content_id" : case "author_id" : case "created_date" : case "altered_date" : case "ft" : case "ft_changed_ts" : 
					throw new SERIA_Exception(_t("The field '%FIELDNAME%' can't be changed.", array("FIELDNAME" => $name)));

				// set published_time if not already set
				case "is_published" :
					if ($value) {
						if(!$this->row["published_date"])
							$this->row["published_date"] = time();
						if (!$this->row[$name])
							$this->publishEvent = true;
					}
					$this->row[$name] = $value;
					break;
				case "pending_publish":
					$this->row['pending_publish'] = $value;
					break;
				case 'publish_delay_time':
					$this->row['publish_delay_time'] = $value;
					break;

				// set published_time = value if not already set
				case "start_date" : 
					if(!$this->row["published_date"])
						$this->row["published_date"] = $value;
					$this->row[$name] = $value;
					break;

				// editable fields stored in table
				case "content_language" : case "notes" : case "author_name" : case "author_email" : case "end_date" : case "published_date" : case "rating" : case "score" : case "votes" : case "price" : case "views" : case "title" : case "event_date" :
					$this->row[$name] = $value;
					break;

				// non system fields
				default : 
					// check that field has been declared
					if(isset($this->fields[$name]))
					{
						$this->extra[$name] = $value;
					}
					else
					{ // may be an array field
						if(($split = strpos($name, "|"))!==false)
						{
							$fieldName = substr($name, 0, $split);
							if(isset($this->fields[$fieldName]))
								$this->extra[$name] = $value;
						}
						throw new SERIA_Exception(_t("The field '%FIELDNAME%' is unknown (not declared in \$this->fields).", array("FIELDNAME" => $name)));
					}
			}

			$this->row["altered_date"] = time();
			$this->isDirty = true;
		}

		/**
		*	Return any value from the article type
		*/
		function get($name)
		{
			switch($name)
			{
				// variables passed directly trough
				case "id":
				case "notes":
				case "type":
				case "content_id":
				case "content_language":
				case "author_name":
				case "author_email":
				case "author_id":
				case "start_date":
				case "end_date":
				case "created_date":
				case "altered_date":
				case "published_date":
				case "event_date":
				case "rating":
				case "score":
				case "votes":
				case "price":
				case "views":
				case "title":
				case "rating_counter":
				case 'publish_delay_time':
					return $this->row[$name];
					break;
					
				// boolean variables
				case "is_published" :
					return ($this->row[$name] > 0) ? true : false;
					break;
				case 'pending_publish':
					return $this->row['pending_publish'] ? true : false;
					break;

				// variables from extra fields
				default :
					$tryNo = 1;
					while(true) {
						if(!$this->extra)
							$this->extra = unserialize($this->row["extra"]);
						if($this->fields[$name])
						{
							return $this->extra[$name];
						}
						else
						{ // may be an array field
							if(($split = strpos($name, "|"))!==false)
							{
								$fieldName = substr($name, 0, $split);
								if($this->fields[$fieldName])
									return $this->extra[$field];
							} else {
								if ($tryNo == 1) {
									$this->updateFields();
								} else {
									throw new SERIA_Exception("Unknown field name '$name'.");
								}
							}
						}
						$tryNo++;
					}
			}
		}

		/**
		*	Build fulltext field
		*
		*	This function add special words for these fields:
		*
		*	Field:			Example:
		*	content_language	@content_language:english
		*	author_id		@author_id:23423
		*	author_email		@author_email:freddie@example.com
		*	is_published		@is_published (added only if is_publised is true)
		*
		*	It also adds fulltext for these fields:
		*
		*	author_name		freddie borli @author_name:freddie @author_name:borli
		*	title			my article @title:my @title:article
		*
		*/
		protected function buildFulltext()
		{
			// Add default keywords from $this->row

			$res =	SERIA_7Bit::word("@type:".$this->type)." ".
				SERIA_7Bit::word("@content_language:".$this->row["content_language"])." ".
				SERIA_7Bit::word("@author_id:".$this->row["author_id"])." ".
				($this->row["author_email"] ? SERIA_7Bit::word("@author_email:".$this->row["author_email"]):"")." ".
				($this->row["is_published"] ? SERIA_7Bit::word("@is_published")." " : "").
				SERIA_7Bit::dbPrepare($this->row["author_name"])." ".
				SERIA_7Bit::dbPrepare($this->row["author_name"], "@author_name:")." ".
				SERIA_7Bit::dbPrepare($this->row["title"])." ".
				SERIA_7Bit::dbPrepare($this->row["title"], "@title:")." ";

			//Add keywords for this articles category locations

			if($this->categories===false)
				$this->loadCategories();

			foreach($this->categories as $catId => $category)
			{
				$res .= SERIA_7Bit::word("@category_id:".$category->getId())." ";
				$res .= SERIA_7Bit::dbPrepare($category->get("name"), "@category:")." ";
			}

			//Add keywords for this articles tags
			$tags = $this->getTags();
			foreach($tags as $tag)
				$res .= SERIA_7Bit::word("@tag:".$tag)." ";
				
			foreach($this->fulltextFields as $name => $prefix)
			{
				$type = $this->fields[$name];
				$types = array_flip(explode(",", $type));
				if(isset($types["array"]))
				{ // has multiple values, must find each entry in $this->row
					foreach($this->row as $fieldName => $value)
					{
						if(($split = strpos($fieldName, "|"))!==false)
						{
							if(substr($fieldName,0,$split)==$name)
							{
								$res .= SERIA_7Bit::dbPrepare($value)." ".
									SERIA_7Bit::dbPrepare($value, $prefix.":")." ";
							}
						}
					}
				}
				else
				{
					$res .= SERIA_7Bit::dbPrepare($this->get($name))." ".
						SERIA_7Bit::dbPrepare($this->get($name), $prefix.":")." ";
				}
			}
			
			$files = $this->getFiles();
			if (sizeof($files) > 0) {
				// Add files: keyword to index if article has files
				$res .= SERIA_7Bit::word("@has_files") . " ";
			}
			
			$imageExtensions = array("jpg", "png", "tiff", "gif");
			$videoExtensions = array("avi", "wmv");
			$translateExtensions = array(
				'jpeg' => 'jpg'
			);
			
			$imageInArticle = false;
			
			foreach ($files as $file) {
				// Add keywords for each file
				
				// File extension
				$fileExtension = trim(strtolower(array_pop(explode(".", $file->get('filename')))));
				
				// Translate extensions. Used for file types with duplicate extensions like jpg/jpeg
				foreach ($translateExtensions as $from => $to) {
					if ($fileExtension == $from) {
						$fileExtension = $to;
					}
				}
				
				$res .= SERIA_7Bit::word("@file_extension:" . $fileExtension) . " ";
				
				if (in_array($fileExtension, $imageExtensions)) {
					$imageInArticle = true;
				}
				if (in_array($fileExtension, $videoExtensions)) {
					$videoInArticle = true;
				}
			}
			
			if ($imageInArticle) {
				$res .= SERIA_7Bit::word("@file_image") . " ";
			}
			if ($videoInArticle) {
				$res .= SERIA_7Bit::word("@file_video") . " ";
			}
			
			return trim($res);
		}
		
		public function addUrlGenerator($key, $description, $baseUrl, $staticParams = array(), $specialParams = array()) {
			return SERIA_ArticleUrlGenerator::createGenerator($this->type, $key, $description, $baseUrl, $staticParams, $specialParams);
		}
		
		public function getUrl($keys) {
			if (!is_array($keys)) {
				$keys = array($keys);
			}
			
			$generators = SERIA_ArticleUrlGenerators::find_all_by_key($keys, array('criterias' => array('articletype' => $this->type), 'include' => array('Params')));
			foreach ($keys as $key) {
				foreach ($generators as $generator) {
					if ($generator->key == $key) {
						return $generator->createPartialUrlForArticle($this);
					}
				}
			}
			
			return false;
		}

		/**
		*	Deletes this article and all associated files and articles
		*/
		function delete()
		{
			$this->clearFromCache();
			$this->throwEvent("DELETE");
		
			$db = SERIA_Base::db();
			foreach($this->fields as $fieldName => $type)
			{
				$types = array_flip(explode(",", $type));
				if(isset($types["file"]))
				{ // this field is a file pointer. Update the file it points to.
					$fileId = $this->get($fieldName);
					if($fileId)
					{
						$file = SERIA_File::createObject($fileId);
						$file->decreaseReferrers();
					}
				}
			}
			$db->exec("DELETE FROM seria_articles WHERE id=".$this->get("id"));
			$db->exec("DELETE FROM seria_articles_fts WHERE id=".$this->get("id"));
			
			return true;
		}

		/**
		*	Validates all data and tries to save the article to the database.
		*/
		function save()
		{
			if(!$this->isWritable)
				throw new SERIA_Exception("Article is not writable. Use ->writable() to enable.");
			if(!$this->isDirty)
				throw new SERIA_Exception("Article has not changed.");

			$this->validateData();

			$this->clearFromCache();

			if($this->row["id"])
			{ // update an existing article
				$data = $this->prepareForDB();
				$sql = "UPDATE ".SERIA_PREFIX."_articles SET ";
				$parts = array();
				unset($data["id"]);
				foreach($data as $k => $v)
					$parts[] = $k."=".$v;
				$sql .= implode(",", $parts);
				$sql .= " WHERE id=".$this->row["id"];

				if($this->isDirtyCategories)
					$this->saveCategories();


				$this->db->exec("UPDATE ".SERIA_PREFIX."_articles_fts SET ft=".$data["ft"]." WHERE id=".$this->db->quote($this->row["id"]));
				$res = $this->db->exec($sql);
			}
			else
			{ // insert a new article
				$this->row["id"] = SERIA_Base::guid('article');
				if(!$this->row["content_id"])
					$this->row["content_id"] = $this->row["id"];
				$data = $this->prepareForDB();

				$sql = "INSERT INTO ".SERIA_PREFIX."_articles (";
				$keyParts = array();
				$valueParts = array();
				foreach($data as $k => $v)
				{
					$keyParts[] = $k;
					$valueParts[] = $v;
				}

				$sql .= implode(",", $keyParts).") VALUES (";
				$sql .= implode(",", $valueParts).")";

				$res = $this->db->exec($sql);

				if($this->isDirtyCategories)
					$this->saveCategories();

				$this->db->exec("INSERT INTO ".SERIA_PREFIX."_articles_fts (id, ft) VALUES (".$this->db->quote($this->row["id"]).",".$data["ft"].")");

				$this->isDirty = false;
			}

			// update table *_files counter for files referenced by this article
			foreach($this->fields as $fieldName => $fieldDef)
			{
				if(!is_array($fieldDef))
				{
					$defs = array_flip(explode(",", $fieldDef));
					if(isset($defs["file"]))
					{ // this is a file id field
						if(isset($defs["array"]))
						{ // this is a multiple files field, update for all files
							foreach($this->row as $subFieldName => $value)
							{
								if(strpos($subFieldName, $fieldName."|")===0)
								{
									if($this->originalExtra[$subFieldName])
									{
										SERIA_File::createObject($this->originalExtra[$subFieldName])->decreaseReferrers();
									}

									if($this->extra[$subFieldName])
									{
										SERIA_File::createObject($this->extra[$subFieldName])->increaseReferrers();
									}
								}
							}
						}
						else
						{ // this is a single file field
							if($this->originalExtra[$fieldName] != $this->extra[$fieldName])
							{ // value has changed. decrease counter for old file, increase counter for new file

								if($this->originalExtra[$fieldName])
								{ // there was an old file
									SERIA_File::createObject($this->originalExtra[$fieldName])->decreaseReferrers();
								}

								if($this->extra[$fieldName])
								{ // there is a new file
									SERIA_File::createObject($this->extra[$fieldName])->increaseReferrers();
								}
							}
						}							
					}
				}
			}
			$this->originalRow = $this->row;
			$this->originalExtra = $this->extra;
			
			if ($this->publishEvent) {
				$this->throwEvent('PUBLISH');
			}
			if ($this->unpublishEvent) {
				$this->throwEvent('UNPUBLISH');
			}
			
			
			return $res;
		}

		/**
		*	Synchronize _fts table
		*/
		
		// This method should be overridden by child class if required to delay publishing
		protected function isPublishable() {
			if ((!$this->get('pending_publish')) && ($this->publishDelay > 0)) {
				return false;
			} elseif ($this->get('pending_publish')) {
				$delayTime = $this->get('publish_delay_time');
				if (!$delayTime) {
					$delayTime = time() + $this->publishDelay;
					$this->set('publish_delay_time', $delayTime);
				}
				if ($delayTime <= time()) {
					return true;
				}
			}
			return false;
		}
		
		public function publish() {
			if ($this->isPublishable()) {
				$this->publishEvent = true;
				$this->unpublishEvent = false;
				$this->set('is_published', 1);
				$this->set('pending_publish', 0);
				return true;
			} elseif (!$this->get('pending_publish') && (!$this->get('is_published'))) {
				$this->set('publish_delay_time', time() + $this->publishDelay);
				$this->set('pending_publish', 1);
			}
			return false;
		}
		public function unpublish() {
			$this->unpublishEvent = true;
			$this->publishEvent = false;
			$this->set('is_published', 0);
			$this->set('pending_publish', 0);
		}
		
		public function updatePublishStatus() {
			if ($this->get('pending_publish') && !$this->get('is_published')) {
				$this->publish();
			}
		}
		
		public function getPublishStatus() {
			return ($this->get('is_published') || $this->get('pending_publish'));
		}
		
		static function synchronizeFTS()
		{
			$db = SERIA_Base::db();
			// delete from _articles_fts all deleted articles from _articles
			$db->exec("DELETE FROM ".SERIA_PREFIX."_articles_fts WHERE id NOT IN (SELECT id FROM ".SERIA_PREFIX."_articles)");

			// identify all _articles that are not in _articles_fts
			$missing = $db->query("SELECT id,ft FROM ".SERIA_PREFIX."_articles WHERE id NOT IN (SELECT id FROM ".SERIA_PREFIX."_articles_fts)")->fetchAll(PDO::FETCH_ASSOC);
			foreach($missing as $data)
				$db->query("INSERT INTO ".SERIA_PREFIX."_articles_fts (id, ft) VALUES (".$db->quote($data["id"]).",".$db->quote($data["ft"]).")");
		}

		/**
		*	Try to set the article in writable mode. Will throw a SERIA_Exception if it is unable to do so.
		*/
		function writable($force = false)
		{
			// only allow articles to be edited if the following is met:

			// Use $force from maintain only!
			if (!$force) {
				// 1. If somebody else owns the article, you must have edit_others_articles
				if(!SERIA_Base::isElevated() && (SERIA_Base::user()===false || ($this->get("author_id") !== SERIA_Base::user()->get("id") && !SERIA_Base::hasRight("edit_others_articles"))))
				{
					throw new SERIA_Exception("Unable to edit articles owned by others. Requires privilege edit_others_articles.", 2);
				}
	
				// 2. If the article is published, then you can't edit it unless you have publish_article
				if($this->row["is_published"] && !SERIA_Base::hasRight("publish_article"))
				{
					throw new SERIA_Exception("Article is published, can't edit it without having privilege publish_article.", 1);
				}
			}

//TODO: Check that this article is not locked for updates by another user.

			$this->isWritable = true;
			// keep original values from $this->row, so we can check if values have changed.
			$this->originalRow = $this->row;
			$this->originalExtra = $this->extra;
		}

		/**
		*	Add timing statistics to the article
		*/
		function addTimingStatistics($caption, $seconds)
		{
			if(!$this->row["id"])
				throw new SERIA_Exception("Article has not been saved, unable to add statistics.");

			$db = $this->db;

			return $db->exec("INSERT INTO ".SERIA_PREFIX."_timing_statistics (articleId, caption, timing_seconds, event_date) VALUES(".$db->quote($this->row["id"]).",".$db->quote($caption).",".($seconds !== false ? $db->quote($seconds) : "NULL").", NOW())");
		}

		/**
		*	Count this visitor as a view
		*/
		function countView()
		{
			if(!$this->row["id"])
				throw new SERIA_Exception("Article has not been saved, unable to count views.");

			$db = $this->db;

			//TODO: translate to a more understanding string
			$userAgent = $_SERVER["HTTP_USER_AGENT"];
			$ip = $_SERVER["REMOTE_ADDR"];

			$eventTS = mktime();
			$db->exec("INSERT INTO ".SERIA_PREFIX."_statistics (articleId, event_date, event_weekday, event_hour, browser, ip) VALUES(".$db->quote($this->row["id"]).",".$db->quote(date("Y-m-d H:i", $eventTS)).",".$db->quote(date("N", $eventTS)).",".$db->quote(date("H",$eventTS)).",".$db->quote($userAgent).",".$db->quote($ip).")", NULL, true);

			return $this->db->exec("UPDATE ".SERIA_PREFIX."_articles SET views=views+1 WHERE id=".$this->db->quote($this->row["id"]), NULL, true);
		}
		
		/**
		*	Get timing statistics of article
		*/
		function getTimingStatistics($type, $fromDate=false, $toDate=false)
		{
			if(!$this->row["id"])
				throw new SERIA_Exception("Article has not been saved, unable to get statistics.");

			$db = $this->db;
			$rows = $this->db->query("SELECT timing_seconds FROM ".SERIA_PREFIX."_timing_statistics WHERE articleId=".$db->quote($this->row["id"]).($fromDate ? " AND event_date >= ".$db->quote($fromDate) : "").($toDate ? " AND event_date < ".$db->quote($toDate) : "")." ORDER BY timing_seconds")->fetchAll(PDO::FETCH_ASSOC);

			$result = array();
			foreach($rows as $row) 
				$result[] = $row["timing_seconds"];
			return $result;
		}

		/**
		*	Get total views of article
		*/
		function getStatisticsHour($fromDate=false, $toDate=false)
		{
			if(!$this->row["id"])
				throw new SERIA_Exception("Article has not been saved, unable to get statistics.");

			$db = $this->db;
			$rows = $this->db->query("SELECT COUNT(*) AS count, event_hour FROM ".SERIA_PREFIX."_statistics WHERE articleId=".$db->quote($this->row["id"]).($fromDate ? " AND event_date >= ".$db->quote($fromDate) : "").($toDate ? " AND event_date < ".$db->quote($toDate) : "")." GROUP BY event_hour")->fetchAll(PDO::FETCH_ASSOC);
			$result = array();
			foreach($rows as $row) 
				$result[$row["event_hour"]] = $row["count"];
			return $result;
		}

		/**
		*	Get total views of article
		*/
		function getStatisticsWeekday($fromDate=false, $toDate=false)
		{
			if(!$this->row["id"])
				throw new SERIA_Exception("Article has not been saved, unable to get statistics.");

			$db = $this->db;
			$rows = $this->db->query("SELECT COUNT(*) AS count, event_weekday FROM ".SERIA_PREFIX."_statistics WHERE articleId=".$db->quote($this->row["id"]).($fromDate ? " AND event_date >= ".$db->quote($fromDate) : "").($toDate ? " AND event_date < ".$db->quote($toDate) : "")." GROUP BY event_weekday")->fetchAll(PDO::FETCH_ASSOC);
			$result = array();
			foreach($rows as $row) 
				$result[$row["event_weekday"]] = $row["count"];
			return $result;
		}

		/**
		*	Get total views of article
		*/
		function getViews($fromDate=false, $toDate=false, $groupByDate=false)
		{
			if(!$this->row["id"])
				throw new SERIA_Exception("Article has not been saved, unable to get views.");

			$db = $this->db;
			if($groupByDate) {
				$rows = $this->db->query("SELECT COUNT(*) AS count, SUBSTR(event_date,1,10) AS e_date FROM ".SERIA_PREFIX."_statistics WHERE articleId=".$db->quote($this->row["id"]).($fromDate ? " AND event_date >= ".$db->quote($fromDate) : "").($toDate ? " AND event_date < ".$db->quote($toDate) : "").($groupByDate ? " GROUP BY e_date" : ""))->fetchAll(PDO::FETCH_ASSOC);
				$result = array();
				foreach($rows as $row)
					$result[$row["e_date"]] = $row["count"];

				return $result;
			} else {
				return $this->db->query("SELECT COUNT(*) FROM ".SERIA_PREFIX."_statistics WHERE articleId=".$db->quote($this->row["id"]).($fromDate ? " AND event_date >= ".$db->quote($fromDate) : "").($toDate ? " AND event_date < ".$db->quote($toDate) : ""))->fetch(PDO::FETCH_COLUMN,0);
			}
		}

		/**
		*	This prepares $this->row and $this->extra for saving to the database. It assumes everything validates perfectly and
		*	that all required fields are set.
		*
		*	1. It sets values that is strict false to NULL.
		*	2. It skips fields that should not be updated/set.
		*/
		private function prepareForDB()
		{
			$res = array();
			$this->row["type"] = $this->type;
			foreach($this->row as $key => $value)
			{
				switch($key)
				{
					case "start_date" : case "end_date" : case "created_date" : case "altered_date" : case "published_date" : case "event_date" :
						if($value === false || is_null($value))
							$res[$key] = "NULL";
						else
							$res[$key] = $this->db->quote(date("Y-m-d H:i:s", $value)); 
						break;
					case "extra" :
						$res[$key] = $this->db->quote(serialize($this->extra));
						break;
					case "ft" :
						$res[$key] = $this->db->quote($this->buildFulltext());
						break;
					case "ft_changed_ts" :
						if(!$value)
							$res[$key] = $this->db->quote(date("Y-m-d H:i:s"));
						break;
					default :
						if($value === false)
						{
							$res[$key] = 0;
						}
						else if($value === true)
						{
							$res[$key] = 1;
						}
						else if(is_null($value))
						{
							$res[$key] = "NULL";
						}
						else
						{
							$res[$key] = $this->db->quote($value);
						}
						break;
				}
			}
			return $res;
		}

		/**
		*	Validates values by parsing $def (definition)
		*
		*	$def must have this form:   type[,keyword[,keyword[,...]]] where
		*
		*	type is one of:
		*
		*	primarykey
		*	integer
		*	float
		*	datetime		// accepts both ISO dates yyyy-mm-dd hh:ii:ss and unix timestamps. NOTE! $value is converted to timestamp
		*	string
		*	email
		*   url
		*	file			// must be false if no file is stored, or an integer referring to a row in the _files database table
		*	image			As above, but will check if file is a valid image
		*
		*	$def can also be an array in which case the following applies:
		*
		*	1. If $value is an array, then it is assumed that $def is an array of definitions (to allow for example an unspecified number of for example file attachments)
		*	2. If $value is not an array, then $def is assumed to be an enum type; $value must be one of the values in the $def array
		*
		*	keyword is currently
		*
		*	required		// (cannot also be array) checks that the value is not NULL. false is allowed.
		*	array			// (cannot also be required) allows saving multiple values (fieldName|1, fieldName|2 etc..)
		*/
		static function validateByType(&$value, $def)
		{
			$db = SERIA_Base::db();
			if(is_array($def))
			{
				if($e = SERIA_IsInvalid::oneOf($value, $def))
					throw new SERIA_ValidationException(_t("Invalid value."), array());
			}
			else 
			{
				$parts = explode(",", $def);
				$type = $parts[0];
				$required = false;
				$array = false;
				if(in_array("required", $parts))
				{
					if(in_array("array", $parts))
						throw new SERIA_Exception("Field has both 'required' and 'array' keywords.");
					$required = true;
				}
				else if(in_array("array", $parts))
				{
					$array = true;
				}

				switch($type)
				{
					case "file":
						if(is_numeric($value) && $value)
						{
							if(!($db->query("SELECT COUNT(id) FROM ".SERIA_PREFIX."_files WHERE id=".$db->quote($value))->fetch(PDO::FETCH_COLUMN,0)))
								throw new SERIA_ValidationException(_t("File is missing."), array());
							
							if (in_array('image', $parts)) {
								try {
									$file = SERIA_File::createObject((int) $value);
								} catch (Exception $exception) {
									
								}
								if (!is_object($file)) {
									throw new SERIA_ValidationException(_t('File does not exist'), array());
								}
								if (!$file->isImage()) {
									throw new SERIA_ValidationException(_t('File is not an image'), array());
								}
							}
						}
						else if($required)
						{
							$value = intval($value);
							throw new SERIA_ValidationException(_t("Required."), array());
						}
						
						break;
					case "float" : 
						if($e = SERIA_IsInvalid::number($value, $required, false, false, false))
							throw new SERIA_ValidationException(_t("Value must be a number."), array());
						if($value!=="") $value = floatval($value);
						break;
					case "integer" :
						if($e = SERIA_IsInvalid::number($value, $required, false, false, true))
							throw new SERIA_ValidationException(_t("Value must be an integer."), array());
						if($value!=="") $value = intval($value);
						break;
					case "string" :
						if($required && trim($value)=="")
							throw new SERIA_ValidationException(_t("Required."), array());
						break;
					case "datetime" :
						if($e = SERIA_IsInvalid::isoDate($value, $required))
						{
							// this is not an ISO date format, assume unix timestamp.
							if($ee = SERIA_IsInvalid::number($value, $required, false, false, true))
							{ // this is not an integer either
								throw new SERIA_ValidationException(_t("Invalid value. Must be proper date or timestamp."), array());
							}
							else
								throw new SERIA_ValidationException($e, array());
						}
						else
						{ // this is a proper ISO date
//							$value = strtotime($value);
						}
						break;
					case "boolean" :
						$value = $value ? 1 : 0;
						break;
					case "email" : 
						if($e = SERIA_IsInvalid::eMail($value, $required))
							throw new SERIA_ValidationException($e,array());
						break;
					case "url" :
						if($e = SERIA_IsInvalid::url($value, $required))
							throw new SERIA_ValidationException($e, array());
						break;
					case "primarykey" : 
						if(is_null($value))
						{ // should be false, not null
							throw new SERIA_ValidationException(_t("Invalid ID for this article."),array());
						}
						break;
					default : 
						throw new SERIA_Exception("Field type '".$type."' is unsupported.");
				}
			}
		}
		
		static function getAvailableArticleTypes()
		{
			$configArticleTypes = array();
			if (SERIA_ARTICLE_TYPES && SERIA_ARTICLE_TYPES != "SERIA_ARTICLE_TYPES")
				$configArticleTypes = explode(",", SERIA_ARTICLE_TYPES);
			$articleTypes = SERIA_Applications::getApplication('seria_publisher')->getArticleTypes();
			foreach ($configArticleTypes as $at)
				$articleTypes[] = $at;
			/* Remove dupes */
			$articleTypes = array_keys(array_flip($articleTypes));
			foreach($articleTypes as $k => $articleType)
			{
				$articleTypes[$k] = $articleType = trim($articleType);
				if(!class_exists($articleTypes[$k]."Article"))
					throw new Exception("Class '".$articleType."Article' not found, but specified in configuration value SERIA_ARTICLE_TYPES.");
				else if(!is_subclass_of($articleType."Article", "SERIA_Article"))
					throw new Exception("Class '".$articleType."Article' defined as article type in SERIA_ARTICLE_TYPES but does not extend SERIA_Article.");
			}
			
			return $articleTypes;
		}
		static function hasArticleTypes() {
			return sizeof(SERIA_Applications::getApplication('seria_publisher')->getArticleTypes())>0;
		}

		function toArray()
		{
			$res = array();
			foreach($this->row as $key => $val)
				$res[$key] = $val;
			unset($res["extra"]);
			foreach($this->extra as $key => $val)
				$res[$key] = $val;
			return $res;
		}

		function contentHash()
		{ // return a hash value that can be used to check if the article has been edited by somebody else
			$array = $this->row;
			unset($array['views']);
			return md5(serialize($array));
		}

	}
