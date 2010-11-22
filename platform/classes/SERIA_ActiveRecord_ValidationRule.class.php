<?php
	class SERIA_ActiveRecord_ValidationRule {
		public $field;
		public $type;
		public $message;
		public $params;
		public $record;
		
		public function check($value) {
			if ($value === null) {
				$value = '';
			}
			
			if ($this->allowEmpty && ($value === '')) {
				return true;
			}
			
			switch ($this->type) {
				case 'required':
					
					if (is_int($value)) {
						return true;
					}
					
					if (is_string($value)) {
						if (strlen($value) > 0) {
							return true;
						}
					}
					
					return false;
					break;
				case 'numeric':
					if ($value === '') {
						return true;
					}
					return is_numeric($value);
					break;
				case 'alphanumeric':
					if ($value === '') {
						return true;
					}
					return preg_match('/^[a-z0-9]+\z/i', $value);
					break;
				case 'alpha':
					if ($value === '') {
						return true;
					}
					return preg_match('/^[a-z]+\z/i', $value);
					break;
					break;
				case 'lengthBetween':
					if ($value === '') {
						return true;
					}
					if ((strlen($value) >= $this->params[0]) && (strlen($value) <= $this->params[1])) {
						return true;
					}
					break;
				case 'lengthMin':
					if ($value === '') {
						return true;
					}
					if (strlen($value) >= $this->params) {
						return true;
					}
					break;
				case 'lengthMax':
					if ($value === '') {
						return true;
					}
					if (strlen($value) <= $this->params) {
						return true;
					}
					break;
				case 'length':
					if ($value === '') {
						return true;
					}
					if (strlen($value) == $this->params) {
						return true;
					}
					break;
				case 'regex':
					if ($value === '') {
						return true;
					}
					
					if (preg_match($this->params, $value)) {
						return true;
					}
					break;
				case 'email':
					if ($value === '') {
						return true;
					}
					if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
						return true;
					}
					break;
				case 'url':
					if ($value === '') {
						return true;
					}
					if (filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
						return true;
					}
					break;
				case 'hostname':
					if ($value === '') {
						return true;
					}
					if (strpos($value, '/') !== false) {
						return false;
					}
					if (filter_var('http://' . $value . '/', FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
						return true;
					}
					break;
				case 'postCode':
					if ($value === '') {
						return true;
					}
					if (strlen($value) == 4) {
						if (is_numeric($value)) {
							return true;
						}
					}
					break;
				case 'phone_no':
					if ($value === '') {
						return true;
					}
					
					if (strlen($value) == 8) {
						if (is_numeric($value)) {
							return true;
						}
					}
					break;
				case 'unique':
					$namespace = $this->params;
					$criterias = array($this->field => $value);
					$records = $this->record->find_all('criterias', $criterias);
					$foundRecords = array();
					foreach ($records as $record) {
						if (($this->record->{$record->primaryKey} > 0) && ($record->{$record->primaryKey} == $this->record->{$record->primaryKey})) {
						} else {
							 $foundRecords[] = $record;
						}
					}
					if ($foundRecords == 0) {
						return true;
					}
					
					foreach ($foundRecords as $record) {
						if ($record->$namespace == $this->record->$namespace) {
							return false;
						}
					}
					
					return true;
					break;
				case 'compare':
					if ((string) $this->record->{$this->params} === (string) $value) {
						return true;
					}
					break;
				case 'inset':
					if (in_array($value, $this->params)) {
						return true;
					}
					break;
				case 'valueMin':
					if ($value >= $this->params) {
						return true;
					}
					break;
				case 'valueMax':
					if ($value <= $this->params) {
						return true;
					}
					break;
				case 'valueRange':
					if ($value >= $this->params[0] && $this->value <= $params[1]) {
						return true;
					}
					break;
			}
			
			return false;
		}
	}
?>