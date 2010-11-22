<?php
	/**
	*	Class that is used for tracking visitors to the website.
	*/
	class SERIA_Visitor
	{
		private $cookie;
		private $visitor;
		private $visit;

		function __construct()
		{
			if($GLOBALS[SERIA_PREFIX."_VISITOR"])
				throw new SERIA_Exception("SERIA_Visitor object already initialized and is available in \$GLOBALS[".SERIA_PREFIX."_VISITOR]");

			$GLOBALS[SERIA_PREFIX."_VISITOR"] = $this;

			if($_SESSION[SERIA_PREFIX."_VISITOR"] && $_COOKIE[SERIA_PREFIX."_VISITOR"])
			{ // record data normally
				if(is_array($_SESSION[SERIA_PREFIX."_VISITOR"]))
				{ // record the previous hit, before recording this hit. All info about previous hit is in the array
				}
			}
			else
			{
				if(!$_COOKIE[SERIA_PREFIX."_VISITOR"])
				{ // never been here before, or cookies turned off
				}
				if(!$_SESSION[SERIA_PREFIX."_VISITOR"])
				{ // session not started, or cookies turned off
					if($_GET[SERIA_PREFIX."_AD"])
						$referrer = $_GET[SERIA_PREFIX."_AD"];
					else
						$referrer = $_SERVER["HTTP_REFERER"];

					$browser = $this->parseUserAgent();

					$_SESSION[SERIA_PREFIX."_VISITOR"] = array(
						"visitors.first_visit" => time(),
						"visitors.last_visit" => time(),
						"visitors.recurring_visits" => 0,
						"visitors.first_referrer" => $referrer,
						"visit.first_hit" => time(),
						"visit.last_hit" => time(),
						"visit.browser" => $browser["name"].$browser["version"],
						"ip" => $_SERVER["REMOTE_ADDR"],
					);
				}
			}
		}

		private function parseUserAgent($UA=false)
		{ // MSIE3, MSIE4, MSIE5, MSIE6, MSIE7, MSIE8, FF1, FF2, FF3 etc.
			if(!$UA)
				$UA = $_SERVER["HTTP_USER_AGENT"];

			$base = trim(substr($UA, 0, strpos($UA, "(")));
			$baseParts = explode("/", $base);

			// identify browser name
			if(stripos($UA, "Googlebot")!==false)
				$name = "GoogleBot";
			else if(stripos($UA, "Yahoo")!==false)
				$name = "YahooBot";
			else if(stripos($UA, "bot")!==false)
				$name = "OtherBot";
			else if(stripos($UA, "spider")!==false)
				$name = "OtherBot";
			else if(stripos($UA, "crawl")!==false)
				$name = "OtherBot";
			else if(stripos($UA, "MSIE")!==false)
				$name = "MSIE";
			else if(stripos($UA, "Firefox")!==false)
				$name = "Firefox";
			else if(stripos($UA, "Opera")!==false)
				$name = "Opera";
			else if(stripos($UA, "Safari")!==false)
				$name = "Safari";
			else
				$name = "Unknown";

			$ver = "";

			// identify browser major version
			if($name==="MSIE")
			{
				if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="1" && stripos($UA, "MSIE 2")!==false)
					$ver = 2;
				else if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="2" && stripos($UA, "MSIE 3")!==false)
					$ver = 3;
				else if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="4" && stripos($UA, "MSIE 4")!==false)
					$ver = 4;
				else if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="4" && stripos($UA, "MSIE 5.5")!==false)
					$ver = 5.5;
				else if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="4" && stripos($UA, "MSIE 5")!==false)
					$ver = 5;
				else if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="4" && stripos($UA, "MSIE 6")!==false)
					$ver = 6;
				else if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="4" && stripos($UA, "MSIE 7")!==false)
					$ver = 7;
				else if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="4" && stripos($UA, "MSIE 8")!==false)
					$ver = 8;
				else
					$ver = "?";
			}
			else if($name==="Firefox")
			{
				if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="5" && stripos($UA, "Firefox/1.5")!==false)
					$ver = 1.5;
				else if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="5" && stripos($UA, "Firefox/1")!==false)
					$ver = 1;
				else if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="5" && stripos($UA, "Firefox/2")!==false)
					$ver = 2;
				else if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="5" && stripos($UA, "Firefox/3")!==false)
					$ver = 3;
				else if($baseParts[0]=="Mozilla" && $baseParts[1][0]=="5" && stripos($UA, "Gecko/20")!==false)
					$ver = "Dev";
				else
					$ver = "?";
			}
			else if($name==="Opera")
			{
				if(stripos($UA, "Opera 5.")!==false)
					$ver = 5;
				else if(stripos($UA, "Opera 6.")!==false)
					$ver = 6;
				else if(stripos($UA, "Opera 7.")!==false || stripos($UA, "Opera/7")===0)
					$ver = 7;
				else if(stripos($UA, "Opera 8.")!==false || stripos($UA, "Opera/8")===0)
					$ver = 8;
				else if(stripos($UA, "Opera 9.")!==false || stripos($UA, "Opera/9")===0)
					$ver = 8;
				else
					$ver = "?";
			}
			else if($name==="Safari")
			{
				if(stripos($UA, "Version/3")!==false)
					$ver = 3;
				else
					$ver = "?";
			}

			if(stripos($UA, "linux")!==false)
				$os = "Linux";
			else if(stripos($UA, "windows")!==false)
				$os = "Windows";
			else if(stripos($UA, "apple")!==false || stripos($UA, "mac")!==false)
				$os = "Mac OS";
			else
				$os = "Other";


			return array(
				"name" => $name,
				"version" => $ver,
				"os" => $os,
			);
		}
	}
