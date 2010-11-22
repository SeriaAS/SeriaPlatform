<?php
	/**
	*	Interface defining how to get and set values in an object.
	*/
	interface SERIA_IFluentAccess
	{
		public function get($name); // 
		public function set($name, $value); //
	}
