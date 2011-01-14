<?php
	class SERIA_DateMetaField implements SERIA_IMetaField
	{
		protected $_dateTime;

		/**
		*	Create a datetime object for SERIA_Meta
		*	@param mixed $timestamp		Unix timestamp or SERIA_DateTime object
		*/
		function __construct($timestamp)
		{
			if(is_object($timestamp))
				$this->_dateTime = $timestamp;
			else {
				$this->_dateTime = new SERIA_DateTime($timestamp);
			}
		}

		/**
		 *
		 * Get the SERIA_DateTime object.
		 * @return SERIA_DateTime
		 */
		public function getDateTimeObject()
		{
			return $this->_dateTime;
		}

		public static function renderFormField($name, $value, array $params=NULL, $hasError=false)
		{
			if($value)
			{
				if(!is_object($value) && $value !== NULL)
					$value = new SERIA_DateTime($value);
				if($value===NULL)
					$formValue = "";
				else
					$formValue = $value->getDateTimeObject()->renderUserDate();
			}
			$code = '<input type="text" name="'.htmlspecialchars($name).'" id="'.htmlspecialchars($name).'" value="'.htmlspecialchars($formValue).'"'.($hasError?' class="ui-state-error-text"':'').'>';
			return $code;
		}

		public static function createFromUser($value)
		{
			return new SERIA_DateMetaField(SERIA_DateTime::parseUserDateTime($value));
		}

		public static function createFromDb($value)
		{
			return new SERIA_DateMetaField(strtotime($value));
		}

		public function __toString()
		{
			return $this->_dateTime->renderUserDate();
		}

		public function toDb()
		{
			return $this->_dateTime->render('Y-m-d');
		}

		public static function MetaField()
		{
			return array(
				'type' => 'date',
				'validator' => new SERIA_Validator(array()),
				"class" => 'SERIA_DateMetaField',
			);
		}
	}
