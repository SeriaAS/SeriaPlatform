<?php
	class SERIA_Model {
		public $columnNames = array();
		public $columnValues = array();
		public $columnTypes = array();
		
		protected $lockedFields = array();
		
		protected $validationRules = array();
		protected $validationErrors = array();
		protected $customValidationRules = array();
		
		protected $customColumns = null;
		protected $validationResult = null;
		
		public function __construct($data = null) {
			$this->runEvent('create', array());
			
			$columns = array_merge($this->getColumns(), $this->getCustomColumns());
			foreach ($columns as $column) {
				$this->columnValues[$column] = '';
			}
			
			
			if ($data) {
				$this->setValues($data);
			}
			
			$this->runEvent('setLocks');
			$this->runEvent('afterCreate');
		}
		
		public function setValues($values) {
			foreach ($values as $key => $value) {
				if ($key != $this->primaryKey) {
					$this->$key = $value;
				}
			}
		}
		
		protected function getCustomColumns() {
			if (is_array($this->customColumns)) {
				return $this->customColumns;
			} else {
				return array();
			}
		}
		
		public function runEvent($event, $arguments=array()) {
			if (method_exists($this, $event)) {
				$this->$event($arguments);
			}
		}
		
		public function getColumns() {
			return array();
		}
		
		public function __set($var, $value) {
			$this->columnValues[$var] = $value;
		}
			
		public function __get($var) {
			return $this->columnValues[$var];
		}
		
		// This method read data from post and merges into current object
		public function fromPost() {
			
			if (isset($_POST[get_class($this)])) {
				if (is_array($data = $_POST[get_class($this)])) {
					$this->setValues($data);
					
					$this->runEvent("afterPost");
					return true;
				}
			}
			
			return false;
		}
		
		// Add custom field for dynamically creating field if needed for extra form fields with validation
		public function addCustomField($fieldName) {
			$this->customColumns[] = $fieldName;
			$this->columnValues[$fieldName] = '';
		}
		
		// Validation code:
		
		protected function addRule($field, $type, $message, $params = null) {
			$this->_addRule($field, $type, $message, $params, false);
		}
		
		public function addCustomRule($field, $type, $message, $params = null) {
			$this->_addRule($field, $type, $message, $params, true);
		}
		
		public function _addRule($field, $type, $message, $params, $custom) {
			$rule = new SERIA_ActiveRecord_ValidationRule();
			
			if ($type[0] == '_') {
				$type = substr($type, 1, strlen($type)-1);
				$rule->allowEmpty = true;
			}
			
			$rule->type = $type;
			$rule->field = $field;
			$rule->message = $message;
			$rule->params = $params;
			$rule->record = $this;
			
			if ($custom) {
				$this->customValidationRules[] = $rule;
			} else {
				$this->validationRules[] = $rule;
			}
		}
		
		// Lock field for change. This field will be ignored in a save()-request on the object if the object is existing
		public function lockField($field) {
			if (is_array($field)) {
				$fields = $field;
				foreach ($fields as $field) {
					$this->lockField($field);
				}
			} else {
				$this->lockedFields[] = $field;
				return true;
			}
		}
		public function unlockField($field) {
			if (is_array($field)) {
				$fields = $field;
				foreach ($fields as $field) {
					$this->unlockField($field);
				}
			} else {
				foreach ($this->lockedFields as $fieldId => $fieldValue) {
					if ($fieldValue == $field) {
						unset($this->lockedFields[$fieldId]);
					}
				}
				
				return true;
			}
		}
		
		public function isValid() {
			if ($this->validationResult !== null) {
				return $this->validationResult;
			} else {
				$this->validate();
				
				return $this->validationResult = (sizeof($this->getErrors()) == 0);
			}
		}
		
		public function resetValidation() {
			$this->validationResult = null;
			$this->validationRules = array();
			$this->runEvent('validationRules');
			
			foreach ($this->customValidationRules as $rule) {
				$this->validationRules[] = $rule;
			}
		}
		
		public function addError($field, $message) {
			$this->validationErrors[$field][] = $message;
		}

		public function validate() {
			$this->runEvent('beforeValidation');
			
			$this->resetValidation();
			
			if (!is_array($this->validationRules)) {
				return true;
			}
			
			foreach ($this->validationRules as $rule) {
				if (!$rule->check($this->{$rule->field})) {
					$this->addError($rule->field, $rule->message);
				}
			}
		}
		
		public function getErrors() {
			return $this->validationErrors;
		}
		
		public function fieldHasError($name) {
			$errors = $this->getErrors();
			return isset($errors[$name]) && sizeof($errors[$name]);
		}
		
		
		// Html/View helpers code
		
		public function getErrorHtml() {
			$errors = $this->getErrors();
			if (sizeof($errors) == 0) {
				return '';
			}
			
			$html = '<div class="formErrorBox">';
			$html .= '  <ul>';
			foreach ($errors as $fieldErrors) {
				foreach ($fieldErrors as $error) {
					$html .= '<li>';
					$html .= htmlspecialchars($error);
					$html .= '</li>';
				}
			}
			$html .= '  </ul>';
			$html .= '</div>';
			
			return $html;
		}
		
		public function getTableRowCssClass() {
			return '';
		}
	}
?>