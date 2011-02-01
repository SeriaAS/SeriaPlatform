<?php
	/**
	*	This interface is used to provide special data types such as
	*	dates and currency with special functions and meanings for storage
	*	within MetaObjects and display withing SERIA_MetaGrid SERIA_ActionForm and more.
	*
	*	For example, when 
	*/
	interface SERIA_IMetaField
	{
		/**
		*	Return the value for display. For example a date should be
		*	returned as a string according to the user time zone and date format.
		*	A currency should be rendered correctly for the current user locale.
		*	This string might be displayed in tables and in other ways.
		*
		*	@return string
		*/
		function __toString();

		/**
		*	Create an instance from user provided data. Throw SERIA_ValidationException if the
		*	data does not validate.
		*
		*	@param mixed $value	Array or single value depending on what the renderFormField submits.
		*	@return object
		*/
		public static function createFromUser($value);

		/**
		*	Render a form field for display within a <form>. $fieldName may be rendered as an array
		*	like this <input type='text' name='$fieldName[a]'><input type='text' name='$fieldName[b]'>
		*	if you need to use more input fields to provide your functionality.
		*
		*	@param string $fieldName	The name of the form field.
		*	@return string $html
		*/
		public static function renderFormField($fieldName, $value, array $params=NULL, $hasError=false);

		/**
		*	Create an instance from data stored in the database. Format depends on the datatype stored in the database
		*	but is always a string.
		*
		*	@param string $value	The value as returned from the database
		*	@return object
		*/
		public static function createFromDb($value);

		/**
		*	Return the value that should be inserted in the database. Format depends on the datatype stored in the
		*	database, but is always a string.
		*
		*	@return string
		*/
		public function toDbFieldValue();

		/**
		*	Return an array of information for SERIA_Meta. It is returned as an array to provide for additional
		*	information in the future. Currently only the type to store in the database is provided.
		*	Format:
		*	
		*	array(
		*		'type' => 'datetime',		// sql datatype
		*	);
		*
		*	@return array
		*/
		public static function MetaField();
	}
