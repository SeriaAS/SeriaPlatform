<?php
	class SERIA_Exception extends Exception
	{
		public $extra = NULL;		// Holds extra information about this error - use HTML

		const NOT_FOUND = 404;		// NOT FOUND EXCEPTION CODE
		const ACCESS_DENIED = 401;	// ACCESS DENIED
		const INCORRECT_USAGE = 3;	// INCORRECT USAGE
		const NOT_IMPLEMENTED = 4;	// NOT IMPLEMENTED
		const NOT_READY = 5;		// NOT READY
		const DISABLED = 6;		// THE FEATURE IS NOT ENABLED IN THIS CASE
		const UNSUPPORTED = 7;		// THE OPERATION REQUIRED AN UNSUPPORTED FEATURE

		public function __construct($message=NULL, $code=NULL, $extra=NULL) {
			parent::__construct($message, $code);
			$this->extra = $extra;
		}
	}
