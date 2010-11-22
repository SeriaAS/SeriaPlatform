<?php
	class SERIA_CDNHttpPath extends SERIA_HttpPath
	{
		protected $originHost, $originPath;

		function __construct($host, $path)
		{
			parent::__construct($host, $path);
			list($host, $path) = seriacdn_rewrite_path($host, $path);
			$this->originHost = $host;
			$this->originPath = $path;
		}

		function output()
		{
			SERIA_Template::disable();
			return $this->originHost.'-'.$this->originPath;
		}
	}
