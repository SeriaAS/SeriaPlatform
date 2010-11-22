<?php
	interface SERIA_CloudServiceProvider
	{
		/**
		*	Returns metadata about each account/provider
		*	@return array array('serviceName'=>, 'accountName'=>, 'editUrl'=>)
		*/
		function getInfo();
	}
