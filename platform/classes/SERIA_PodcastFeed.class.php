<?php
	class SERIA_PodcastFeed extends SERIA_Feed
	{
		protected $_subtitle;
		protected $_url;		
		protected $_explicit = "no";
		protected $_owner;
		protected $_categories;

		function generate()
		{
			$res = '<'.'?xml version="1.0" encoding="UTF-8"?'.'>
<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">
	<channel>
		<title>'.SERIA_Lib::xmlspecialchars($this->_title).'</title>';

			if($this->_link) $res .= '
		<link>'.SERIA_Lib::xmlspecialchars($this->_link).'</link>';
			else throw new SERIA_Exception("Link is not specified, but required for Podcast");

			if($this->_language) $res .= '				
		<language>'.SERIA_Lib::xmlspecialchars($this->_language).'</language>';

			if($this->_copyright)
			{ // check if the copyright is properly escaped (check for < and >)

				if(strpos($this->_copyright,">")===false && strpos($this->_copyright,"<")===false)
					$res .= '
		<copyright>'.$this->_copyright.'</copyright>';
				else
					$res .= '
		<copyright>'.SERIA_Lib::xmlspecialchars($this->_copyright).'</copyright>';
			}

			if($this->_description) $res .= '
		<description>'.SERIA_Lib::xmlspecialchars($this->_description).'</description>
		<itunes:summary>'.SERIA_Lib::xmlspecialchars($this->_description).'</itunes:summary>';
			else throw new SERIA_Exception("Description is not specified, but required for Podcast");

			if($this->_subtitle) $res .= '
		<itunes:subtitle>'.SERIA_Lib::xmlspecialchars($this->_subtitle).'</itunes:subtitle>';

			if($this->_image) $res .= '
		<itunes:image href="'.SERIA_Lib::xmlspecialchars($this->_image).'" />';

			if($this->_url) $res .= '
		<atom:link href="'.SERIA_Lib::xmlspecialchars($this->_url).'" rel="self" type="application/rss+xml" />';

			if($this->_owner) $res .= '
		<itunes:owner>
			<itunes:name>'.SERIA_Lib::xmlspecialchars($this->_owner["name"]).'</itunes:name>
			<itunes:email>'.SERIA_Lib::xmlspecialchars($this->_owner["email"]).'</itunes:email>
		</itunes:owner>';

			$res .= '
		<itunes:explicit>'.$this->_explicit.'</itunes:explicit>';

			if($this->_categories) 
			{
				foreach($this->_categories as $a => $b)
				{
					if(is_array($b))
					{
						$res .= '
		<itunes:category text="'.SERIA_Lib::xmlspecialchars($a).'">';
						foreach($b as $c => $d)
						{
							$res .= '
			<itunes:category text="'.SERIA_Lib::xmlspecialchars($d).'" />';
						}

						$res .= '
		</itunes:category>';
					}
					else
						$res .= '
		<itunes:category text="'.SERIA_Lib::xmlspecialchars($b).'" />';
				}
			}

			// add all items
			foreach($this->_articles as $a)
			{
				$res .= '
		<item>
			<title>'.SERIA_Lib::xmlspecialchars($a["title"]).'</title>
			<guid>'.SERIA_Lib::xmlspecialchars($a["guid"]).'</guid>
			<description>'.SERIA_Lib::xmlspecialchars($a["description"]).'</description>
			<itunes:summary>'.SERIA_Lib::xmlspecialchars($a["description"]).'</itunes:summary>';

				if(isset($a["link"]))
					$res .= '
			<link>'.SERIA_Lib::xmlspecialchars($a["link"]).'</link>';

				if(isset($a["author"]) && isset($a["author"]["name"]))
					$res .= '
			<itunes:author>'.SERIA_Lib::xmlspecialchars($a["author"]["name"]).'</itunes:author>';

				if(isset($a["author"]) && isset($a["author"]["email"]))
					$res .= '
			<author>'.SERIA_Lib::xmlspecialchars($a["author"]["email"].(isset($a["author"]["name"])?" (".$a["author"]["name"].")":"")).'</author>';

				if(isset($a["author"]))
					$res .= '
			<itunes:author>'.SERIA_Lib::xmlspecialchars($a["author"]).'</itunes:author>';

				if(isset($a["author"]))
					$res .= '
			<itunes:author>'.SERIA_Lib::xmlspecialchars($a["author"]).'</itunes:author>';

				if(isset($a["image"]))
				{
					$res .= '
			<media:content url="'.$a["image"].'" type="'.SERIA_Lib::getContentType($a["image"]).'" />';
				}

				if(isset($a["enclosure"]))
					$res .= '
			<enclosure url="'.SERIA_Lib::xmlspecialchars($a["enclosure"]["url"]).'" length="'.SERIA_Lib::xmlspecialchars($a["enclosure"]["length"]).'" type="'.SERIA_Lib::xmlspecialchars($a["enclosure"]["type"]).'" />';
				$res .= '
		</item>';
			}
			$res .= '
	</channel>
</rss>';

			return $res;
		}

		function output()
		{
			SERIA_Template::override("text/xml; charset=utf-8", $this->generate());
		}

		function subtitle($subtitle)
		{
			$this->_subtitle = $subtitle;
		}

		function url($url)
		{
			$this->_url = $url;
		}

		function explicit($explicit="yes")
		{
			$thie->_explicit = $explicit;
		}

		function owner($name, $email)
		{
			$this->_owner = array("name" => $name, "email" => $email);
		}

		function categories($categories)
		{
			$this->_categories = $categories;
		}
	}
