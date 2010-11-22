<?php
	/**
	*	An object of this class represents a path on the website.
	*/
	abstract class SERIA_HttpPath
	{
		protected $host, $path;

		function __construct($host, $path)
		{
			$this->host = $host;
			$this->path = $path;
		}

		abstract function output();

		public function render()
		{
			echo $this->output();
		}
	}
