<?php
	class SERIA_ValidationException extends SERIA_Exception
	{
		private $validationErrors=false;
		function __construct($message, $errors)
		{       
			$this->validationErrors = $errors;
			parent::__construct($message);
		}

		/**
		 * Returns an array of errors ($fieldName => $errorText).
		 *
		 * @return array
		 */
		function getValidationErrors()
		{
			return $this->validationErrors;
		}
		/**
		 * Returns an array of error messages ($fieldName => $errorHTMLCode).
		 *
		 * @return array
		 */
		function getValidationMessages()
		{
			$res = array();
                       // return $validationErrors;
			foreach($this->validationErrors as $k => $e)
				$res[$k] = "<div class='fieldError'>".htmlspecialchars($e)."</div>";

			return $res;
		}
	}
