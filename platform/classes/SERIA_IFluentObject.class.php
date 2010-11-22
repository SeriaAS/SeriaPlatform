<?php
	/**
	*	Special interface indicating that the class supports creating objects by
	*	passing a database row to the createObject method and back to the database by
	*	calling toRow().
	*/
	interface SERIA_IFluentObject
	{
		/**
		*	Format:
		*
		*	array(
		*		'fieldname' => array( // None of these attributes are required
		*			'fieldtype' => [creator|text|textarea|checkbox|datepicker|email|etc],
		*				creator is a special fieldtype where the value is the ID of the logged in user or NULL if not required and no user is logged in
		*			'caption' => _t('A translated caption for this field'),
		*			'weight' => 0,						// Fields are sorted according to their weight
		*			'validator' => [SERIA_Validator object],
		*			'default' => 'some default value',
		*			'helptext' => 'Simple <strong>html</strong> text',	// Only use HTML if needed. Never use block level elements in the help text.
		*			'type' => [integer,varchar,date,datetime,number,blob,mediumblob,tinyint], basically any SQL datatype (optional)
		*		),
		*		...
		*	);
		*/
		public static function getFieldSpec(); // returns array() specifying rules for the columns. Access this trough SERIA_Fluent::getFieldSpec($className), and do not call it directly.
		public static function fluentSpec(); // returns array('table' => '{tablename}', 'primaryKey' => 'id')
		public static function fromDB($row); // returns object
		public function toDB(); // returns array
		public function getKey(); // returns the primary key for this row

		public function get($name); // returns the value from the field $name
		public function set($name, $value); // sets $field = $value, must NEVER save the value immediately - only when ->save is called

		public function isDeletable(); // returns true if the user is allowed to delete this object
		public function delete(); // deletes the object, as long as the user is allowed to
	}
