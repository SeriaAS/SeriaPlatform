<?php
	abstract class SERIA_Feed
	{
		protected $_articles;		// array of article abstracts (as defined in SERIA_Article::getAbstract)
		protected $_title;
		protected $_description;
		protected $_logoURL;
		protected $_link;
		protected $_language;		// for example en-us
		protected $_copyright;		// for example &#x2117; &amp; &#xA9; 2005 John Doe &amp; Family
		

		/**
		*	Should output the feed using SERIA_Template::override();
		*/
		abstract function output();

		/**
		*	Should return the feed contents
		*/
		abstract function generate();

		/**
		*	Constructor expects an array of articles, perhaps fetched from an ArticleQuery::page(), and 
		*	optionally an articleProcessor callback which will return an array corresponding to the 
		*	SERIA_Article::getAbstract() function. The $articleProcessor will recieve an article object
		*	
		*/
		function __construct($articleObjects, $articleProcessor=false)
		{
			$articles = array();
			if($articleProcessor)
			{
				foreach($articleObjects as $article)
				{
					if($a = $articleProcessor($article))
						$articles[] = $a;
				}
			}
			else
			{
				foreach($articleObjects as $article)
				{
					$articles[] = $article->getAbstract();
				}
			}
			$this->_articles = $articles;
		}

		/**
		*	The name of the feed
		*/
		function title($title)
		{
			$this->_title = $title;
		}

		/**
		*	A descriptive text for this feed
		*/
		function description($description)
		{
			$this->_description = $description;
		}

		/**
		*	The logo used to represent this feed in for example iTunes and others
		*/
		function logo($logoURL)
		{
			$this->_logoURL = $logoURL;
		}

		/**
		*	The URL that represents this feed on the website
		*/
		function link($link)
		{
			$this->_link = $link;
		}

		/**
		*	Language code
		*/
		function language($language)
		{
			$this->_language = $language;
		}

		/**
		*	Copyright notice string (properly escaped using entities)
		*/
		function copyright($copyright)
		{
			$this->_copyright = $copyright;
		}
	}
