<?php
	/**
	*	This class represents an URL on the website,
	*	with its attached article and access control and
	*	template information. It provides a hiearchical 
	*	structure with inheritence identified by the
	*	components of the url.
	*
	*	/about-us/employees is the employees page, which
	*	inherits some properties from /about-us such as
	*	the design template and access control lists.
	*
	* TODO:	The SERIA_Page class attaches an output filter
	*	to SERIA_Template, so that urls like {$PAGE(123)$}
	*	are dynamically rewritten to the proper URL.
	*/
	class SERIA_Page
	{
		private $id, $row;

		function createObject($id)
		{
			$db = SERIA_Base::db();
			$row = $db->query('SELECT * FROM seria_pages WHERE id='.$db->quote($id))->fetch(PDO::FETCH_ASSOC);
			return new SERIA_page($row);
		}

		function createObjectFromURL($url)
		{
			$db = SERIA_Base::db();
			if($path = parse_url($url, PHP_URL_PATH))
			{
				$pathComponents = explode('/', trim($path,'/'));
				$current = false;
				foreach($pathComponents as $component)
				{
					if($current === false)
					{
						$sql = 'SELECT * FROM '.SERIA_PREFIX.'_pages WHERE name='.$db->quote($component).' AND parentId IS NULL LIMIT 1'; // limit is to speed up query
					}
					else
					{
						$sql = 'SELECT * FROM '.SERIA_PREFIX.'_pages WHERE name='.$db->quote($component).' AND parentId='.$db->quote($current['id']).' LIMIT 1'; // limit is to speed up query
					}
					$current = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
				}
			}
			else
				throw new SERIA_Exception('Invalid URL '.$url);

			if(!$current)
				throw new SERIA_Exception(404); // url not found

			return new SERIA_Page($current);
		}

		function __construct($id=false, $parentPage=false)
		{
			if($id===false)
			{
				$this->id = false;
				$this->row = array();
			}
			else if(is_array($id))
			{
				$this->id = $id['id'];
				$this->row = $id;
			}
			else
			{
				$this->id = $id;
				$this->row = SERIA_Base::db()->query('SELECT * FROM '.SERIA_PREFIX.'_pages WHERE id='.SERIA_Base::db()->quote($id))->fetch(PDO::FETCH_ASSOC);
				if(!$this->row)
					throw new SERIA_Exception('Page not found');
			}
		}
	}
