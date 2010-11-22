<?php
	/**
	 * SERIA_NamedObject allows references to objects to be serialized to a database.
	 *
	 * getObjectId returns a string that can be stored for example in a database
	 * createObject returns an instance of an object using the id part returned by getObjectId
	 */
	interface SERIA_NamedObject
	{
		/**
		 * Should return an array of classname, static function name and parameters
		 * that can be used to recreate the object, for example:
		 * return array("SERIA_Object","createObject",123);
		 * @return string
		 */
		function getObjectId();
	}
?>
