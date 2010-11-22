<?php
	abstract class SERIA_Article_Template
	{
		protected $article;
		protected static $FIELDS;

		function __construct($article)
		{
			if(get_class($article)!="SERIA_Article") 
				throw new SERIA_Exception("Create this object trough \$cmsArticle->getTemplate().");
			$this->article = $article;
		}

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
		*		"image" => "http://imageurl/", 		optional
		*		"link" => "http://link/",		optional, and only it is constant (for example for a file). May be overridden by templates.
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
		*	Validate all data. Return an array of fieldname => error message or 
		*	false if no errors.
		*/
		function isInvalid()
		{
			return $this->article->isInvalid();
		}

		/**
		*	Extend this so that custom fields such as for example "content" is 
		*	added to $this->article->extendExtra(array($name => $value)) 
		*	(or stored in some other way), while other fields are passed to 
		*	$this->article->set($name, $value);
		*
		*	1. ALWAYS check if($this->article->isUpdateable()) before allowing 
		*	any changes.
		*
		*	2. NEVER perform database updates before $this->save() is called.
		*	(You CAN update $this->article->setExtra/extendExtra).
		*
		*	return $this;
		*/
		function set($name, $value)
		{
			static $fields = false;
			if($fields===false)
			{
				if(version_compare("5.3", PHP_VERSION, "<"))
				{ // late static binding method
					eval("\$fields = static::+$FIELDS;");
				}
				else
				{ // a little slower method
					eval("\$fields = ".$this->article->get("type")."Article::\$FIELDS;");
				}
			}

		        if(isset($fields[$name]))
			{
		                $this->article->extendExtra(array( $name => $value ));
			}
		        else
		                $this->article->set($name, $value);

			return $this;
		}

		/**
		*	Extend this function so that custom fields are retrieved from 
		*	$this->article->getExtra (or from somewhere). If the field is not
		*	stored, pass the request to $this->article->get($name);
		*/
		function get($name)
		{
			static $fields = false;
			if($fields===false)
			{
				if(version_compare("5.3", PHP_VERSION, "<"))
				{ // late static binding method
					eval("\$fields = static::+$FIELDS;");
				}
				else
				{ // a little slower method
					eval("\$fields = ".$this->article->get("type")."Article::\$FIELDS;");
				}
			}

		        if(isset($fields[$name]))
			{
				$extra = $this->article->getExtra();
				return $extra[$name];
			}
		        else
		                $this->article->get($name);
		}

		/**
		*	Checklist:
		*
		*	1.	Check that article is opened for updates: if($this->article->isUpdateable())
		*	2.	Validate all data, and also call if($this->article->isInvalid()).
		*	3.	If everything validates call $this->article->save() and save any extra data.
		*	4.	If everything is okay, return $this - else return false;
		*/
		function save()
		{
			if(!$this->article->isUpdateable())
				throw new SERIA_Exception("Article not opened for updates.");

			$localErrors = $this->isInvalid();
			$articleErrors = $this->article->isInvalid();

			if($localErrors || $articleErrors)
				throw new SERIA_Exception("Article does not validate.");

			if($this->article->save())
				return $this;

			return false;
		}

		function getTags()
		{
			return $this->article->getTags();
		}

		function addTag($tag)
		{
			if($this->article->addTag($tag))
				return $this;
			return false;
		}

		function removeTag($tag)
		{
			if($this->article->removeTag($tag))
				return $this;
			return false;
		}

		function allowUpdates()
		{
			if($this->article->allowUpdates())
				return $this;
			return false;
		}		
	}
