<?php
	class SERIA_HtmlForm {
		protected $model = false;
		protected $modelName = 'form';
		protected $locale = false;
		
		// $model is a ActiveRecord or compatible object or string for model-less forms
		public function __construct($model = false, SERIA_Locale $locale = NULL) {
			if($locale === NULL)
				$this->locale = SERIA_Locale::getLocale();
			else
				$this->locale = $locale;
				
			if (is_object($model)) {
				$this->model = $model;
				$this->modelName = get_class($model);
			}
			
			if (is_string($model)) {
				$this->modelName = $model;
			}
		}
		
		public function start($properties = array()) {
			$method = '';
			$onsubmit = '';
			$data = '';

			if (isset($properties['method'])) {
				$method = $properties['method'];
			}
			if (!$method) {
				$method = 'post';
			} elseif ($method == 'async') {
				$handler = $properties['handler'];
				$callback = $properties['callback'];
				
				static $varCounter = 0;
				$this->jsSubmitCallbackName = 'seria_htmlFormPost' . $varCounter++;
				
				$method = 'post';
				$onsubmit = '' . $this->jsSubmitCallbackName . '(); return false';
				
				$fields = '';
				foreach ($this->model->columnNames as $name) {
					$fieldId = '$(\'#' . $this->modelName . '_' . $name . '\').val()';
					$fields .= 'if (' . $fieldId . ') { fields[' . SERIA_Lib::toJSON($this->modelName . '[' . $name . ']') . '] = ' . $fieldId . "; }";
				}
				
				$data .= '
					<script type="text/javascript">
						function ' . $this->jsSubmitCallbackName . '() {
							var fields = {};
							
							' . $fields . '
							
							return $.post(\'' . $handler . '\', fields, ' . $callback . ')
						}
					</script>';
			}
			
			return $data . '<form action="" method="' . $method . '" onsubmit="' . $onsubmit . '">';
		}
		
		public function errors() {
			if (is_object($this->model)) {
				return $this->model->getErrorHtml();
			}
			
			return '';
		}
		
		public function text($name, $properties = array()) {
			$class = 'textField';
			if ($this->model) {
				if ($this->model->fieldHasError($name)) {
					$class .= ' fieldWithError';
				}
			}
			
			if (!$fieldType = $properties['fieldType']) {
				$fieldType = 'text';
			}
			
			if($properties['class'])
				$class .= " ".$properties['class'];
						
			$id = $this->modelName . '_' . $name;
			
			$fieldName = $this->modelName . '[' . $name . ']';
			
			$modelProperties = '';
			if ($this->model) {
				
				$value = $this->model->$name;
				if(isset($this->model->columnTypes[$name]))
				{
					switch($this->model->columnTypes[$name]) 
					{
						case "DATE" : $value = $this->locale->sqlToString($this->model->$name);
							break;
					}
				}
				$value = str_replace('"', '&quot;', $value);
					
				$modelProperties = ' value="' . $value . '" ';
				
			}
			
			switch ($fieldType) {
				case 'textarea':
					$html = '<textarea class="' . $class . '" id="' . $id . '" name="' . $fieldName . '">' . htmlspecialchars($this->model->$name) . '</textarea>';
					break;
				default:
					$html = '<input type="' . $fieldType . '" ' . $modelProperties . ' class="' . $class . '" id="' . $id . '" name="' . $fieldName . '">';
					break;
			}
			return $html;
		}
		
		public function textarea($name) {
			return $this->text($name, array('fieldType' => 'textarea'));
		}
		
		public function password($name) {
			return $this->text($name, array('fieldType' => 'password'));
		}
		
		public function label($field, $text) {
			$id = $this->modelName . '_' . $field;
			
			$html = '<label for="' . $id . '">' . $text . '</label>';
			
			return $html;
		}
		
		public function select($name, $options = array()) {
			$class = 'selectField';
			if ($this->model) {
				if ($this->model->fieldHasError($name)) {
					$class .= ' fieldWithError';
				}
			}
			
			if (is_object($options)) {
				if (get_class($options) == 'SERIA_ActiveRecordSet') {
					$options = $options->toKeyArray();
				}
			}
			
			$id = $this->modelName . '_' . $name;
			
			$fieldName = $this->modelName . '[' . $name . ']';
			
			$modelProperties = '';
			if ($this->model) {
				$modelProperties = ' value="' . str_replace('"', '&quot;', $this->model->$name) . '" ';
			}
			
			$html  = '<select class="' . $class . '" id="' . $id . '" name="' . $fieldName . '">';
			foreach ($options as $value => $title) {
				$option = '';
				if ($this->model)
				{
					if($this->model->$name == $value) 
					{
						$option = 'selected="selected"';
					}
					if(isset($this->model->columnTypes[$name]))
					{
						switch($this->model->columnTypes[$name])
						{
							case "DATE" : $value = $this->locale->sqlToString($value);
								break;
						}
					}
				}
				$html .= '	<option value="' . $value . '" ' . $option . '>' . $title . '</option>';
			}
			$html .= '</select>';
			
			return $html;
		}
		
		public function checkbox($name) {
			$class = 'checkboxField';
			
			$selected = '';
			if ($this->model) {
				if ($this->model->$name) {
					$selected = 'checked="checked"';
				}
			}
			
			$id = $this->modelName . '_' . $name;
			
			$fieldName = $this->modelName . '[' . $name . ']';
			
			$html  = '<input type="hidden" value="0" id="hidden_' . $id . '" name="' . $fieldName . '" ' . $selected . ' />'; 
			$html .= '<input type="checkbox" value="1" id="' . $id . '" name="' . $fieldName . '" ' . $selected . ' />'; 
			
			return $html;
		}
		
		public function submit($title) {
			return '<input type="submit" class="submitButton" value="' . $title . '">';
		}
		
		public function hidden($name, $value) {
			$fieldName = $this->modelName . '[' . $name . ']';
			return '<input type="hidden" value="' . $value . '" id="hidden_' . $name . '" name="' . $fieldName . '" />'; 
		}
		
		public function end() {
			return '</form>';
		}
	}
?>