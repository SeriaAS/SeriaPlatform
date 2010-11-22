<?php

	interface SERIA_FormFactory
	{
		function getFormSpecification();
		function get($fieldName);
		function set($fieldName, $value);
		function save();
		function getForm($name=false);
	}
