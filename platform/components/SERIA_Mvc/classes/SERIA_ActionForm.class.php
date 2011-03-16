<?php

	class SERIA_ActionForm
	{
		public $id;
		public $success = false;
		public $error = false;
		public $errors = false;
		public $_recursive = false;

		protected $_action;
		protected $_method = "post";
		protected $_spec; 
		protected $_data = array();
		protected $_prefix = '';
		protected $_state;
		protected $_errorTemplate = '%MESSAGE%';
		protected $_noError = '';

		/**
		*	Construct a form object for allowing users to perform actions.
		*
		*	@param mixed $p1	Either form specification identical in format to the 'fields' section of the array returned by SERIA_Meta::Meta(),
		*				or a SERIA_MetaObject classname
		*	@param array $p2=false	Array of fieldnames to import, required when importing from an object.
		*/
		function __construct($id, $p1=NULL, array $p2=NULL)
		{
			if(!is_string($id))
				throw new SERIA_Exception('Expecting a form ID as first parameter.');
			$this->id = $id;
			if($p1!==NULL && $p2!==NULL)
				$this->importFields($p1, $p2);

			if(SERIA_DEBUG)
			{
				$contents = '<div>Complete form:<pre class="code">';
				$code = 'if($form->success) 
	header("Location: ".SERIA_HTTP_ROOT."/path/to/somewhere");
$form->begin()."<table><thead>';
				foreach($p2 as $fieldName)
				{
					$code .= '
	<tr><th>".$form->label("'.$fieldName.'")."</th><td>".$form->field("'.$fieldName.'")."</td></tr>';
				}
				$code .= '
</thead><tfoot>
	<tr><td colspan=\'2\'>".$form->submit(_t("Save"))."</td></tr>
</tfoot></table>".$form->end();
';

				$contents .= nl2br(htmlspecialchars($code)).'</pre></div>';

				SERIA_Base::develHelp($id, 'SERIA_ActionForm('.$id.')', $contents);
			}
		}

		/**
		*	Get a value from the current form
		*/
		function get($name)
		{
			if($this->_method === "post")
				$src = $_POST;
			else
				$src = $_GET;

			if(isset($src[$this->_prefix.'bool']) && isset($src[$this->_prefix.'bool'][$name]))
				return isset($src[$this->_prefix.$name]);

			if(isset($src[$this->_prefix.$name])) {
				if (isset($this->_spec[$name]) && isset($this->_spec[$name]['class'])) {
					if (!in_array('SERIA_IMetaField', class_implements($this->_spec[$name]['class'])))
						throw new SERIA_Exception('Class is not a SERA_IMetaField (interface).');
					return call_user_func(array($this->_spec[$name]['class'], 'createFromUser'), $src[$this->_prefix.$name]);
				} else
					return $src[$this->_prefix.$name];
			}
			if(isset($this->_data[$name]))
				return $this->_data[$name];

			return NULL;
		}

		/**
		*	Checks if there exists post data for this form
		*/
		function hasData()
		{
			if($this->_method === "post" && isset($_POST['SAFI']))
				$id = $_POST['SAFI'];
			else if($this->_method === 'get' && isset($_GET['SAFI']))
				$id = $_GET['SAFI'];
			else $id = NULL;
			return $this->id == $id;
		}

		/**
		*	Checks if the specified field has an error and returns true/false
		*	@return boolean
		*/
		public function hasError($name)
		{
			return ($this->errors!==false && isset($this->errors[$name]));
		}

		/**
		*	Prefix all field names with this string
		*	@param string $prefix		Prefix for field names
		*	@return SERIA_ActionForm
		*/
		public function prefix($prefix)
		{
			$this->_prefix = $prefix;
			return $this;
		}

		/**
		*	Add state information to the form, which is submitted trough hidden fields
		*	@param array $state	Associative array of key-value pairs to submit with the form
		*	@return SERIA_ActionForm
		*/
		public function state(array $state)
		{
			$this->_state = $state;
			return $this;
		}

		/**
		*	Method for sending data (post or get)
		*	@param string $method		POST or SEND
		*	@return SERIA_ActionForm
		*/
		public function method($method)
		{
			$this->_method = strtolower($method);
			return $this;
		}

		/**
		*	Set the target URL when submitting this form.
		*	@param string $url	URL to submit form to
		*	@return SERIA_ActionForm
		*/
		public function action($url)
		{
			$this->_action = $url;
			return $this;
		}

		/**
		*	Import field specifications from a SERIA_MetaObject 
		*
		*	@param mixed $instance			An instance of a SERIA_MetaObject, or a classname
		*	@param array $fields			An array of field names to import from the SERIA_MetaObject
		*	@return SERIA_ActionForm
		*/
		public function importFields($instance, array $fields)
		{
			$spec = SERIA_Meta::_getSpec($instance);

			if($this->_spec === NULL)
				$this->_spec = array();

			foreach($fields as $field)
			{
				if(isset($spec['fields'][$field]))
				{
					$this->_spec[$field] = $spec['fields'][$field];
					if(is_object($instance))
						$this->_data[$field] = $instance->get($field);
					else
						$this->_data[$field] = NULL;
				}
				else
				{
					throw new SERIA_Exception('No such field "'.$field.'" in meta object "'.get_class($instance).'".');
				}
			}
			return $this;
		}

		/**
		*	Add a field to the form.
		*	@param string $name		The name of the field to add.
		*	@param array $spec		Specification according to the format in the ['fields'][$fieldName] of the SERIA_Meta::Meta()-method
		*	@return string
		*/
		public function addField($name, $spec, $value=NULL)
		{
			if($spec instanceof SERIA_MetaObject)
			{
				$spec = SERIA_Meta::_getSpec($spec);
				$spec = $spec['fields'][$name];
			}

			if($this->_spec === NULL)
				$this->_spec = array();
			$this->_spec[$name] = $spec;
			if($value!==NULL) $this->_data[$name] = $value;
			return $this;
		}

		/**
		*	Return the default field type for the specified field
		*	@param $name
		*/
		public function field($name, array $attributes=NULL)
		{
			$legal = array('style','class','title');
			foreach($attributes as $key => $val)
			{
				if(!in_array($key, $legal))
					unset($attributes[$key]);
			}
			if(!isset($this->_spec[$name]))
				throw new SERIA_Exception('No such field "'.$name.'".');

			// no field type defined
//			if(!isset($this->_spec[$name]['fieldtype'])) {

			if(is_callable(array($this, $this->_spec[$name]['fieldtype'])))
			{
				return call_user_func(array($this, $this->_spec[$name]['fieldtype']), $name, $attributes);
			}

			if (isset($this->_spec[$name]['class'])) {
				if (in_array('SERIA_IMetaField', class_implements($this->_spec[$name]['class']))) {
					$value = $this->get($name);
					if ($value && !in_array('SERIA_IMetaField', class_implements($value)))
					{
						throw new SERIA_Exception('The object is not a SERIA_IMetaField (interface). The spec has a valid class.');
					}
					return call_user_func(array($this->_spec[$name]['class'], 'renderFormField'), $this->_prefix.$name, $value, $attributes, $this->hasError($name));
				}
				else
				{
					throw new SERIA_Exception('There is no fieldtype specified for the field "'.$name.'" and the class "'.$this->_spec[$name]['class'].'" does not implement the SERIA_IMetaField interface.');
				}
			}

			return call_user_func(array($this, $this->_spec[$name]['fieldtype']), $name, $attributes);
		}

		/**
		*	Begins the output of a form, returns html code <form action=$action method=$method name=$name>
		*	State information is injected immediately after the <form>-tag
		*
		*	@param string $name	Form name
		*	@param string $action	URL 
		*	@return string
		*/
		public function begin($name=NULL, array $attributes=NULL) {

			if(empty($this->_action))
				$this->_action = SERIA_Url::current();

			$res = self::renderTag('form', $attributes, array(
				'id' => !empty($name) ? $this->_prefix.$name : NULL,
				'enctype' => 'multipart/form-data',
				'method' => $this->_method,
				'action' => $this->_action,
				'name' => $this->_prefix.$name,
				'class' => 'SERIA_Form SERIA_ActionForm'.($this->errors?' errors':''),
				'accept-charset' => 'UTF-8',
			));
			$res .= "<input type='hidden' name='SAFI' value=\"".$this->id."\">";
			if($this->_state)
			{
				foreach($this->_state as $key => $value)
					$res .= self::renderTag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
			}

			if($this->errors)
			{
				$script = '<script type="text/javascript">/*SERIA_ActionForm makes validation errors available for scripting*/jQuery(function(){';
				foreach($this->errors as $key => $error)
				{
					$script .= "jQuery('form.SERIA_ActionForm #".$this->_prefix.$key."').data('actionFormError', \"".htmlspecialchars($error, ENT_COMPAT)."\");";
				}
				$script .= '});</script>';

				SERIA_Template::headEnd($this->_prefix.'errors', $script);

			}
			return $res;
		}

		/**
		*	Returns the end tag for the form
		*	@return string
		*/
		public function end() {
			return '</form>';
		}

		/**
		*	Return HTML for a submit button
		*/
		public function submit($caption, array $attributes=NULL) {
			return self::renderTag('input', $attributes, array(
				'type' => 'submit',
				'value' => $caption,
				'class' => 'submit'.($this->errors?' errors':''),
			));
		}

		/**
		*	Return HTML for a label element for the corresponding field
		*/
		public function label($name, $caption=false, array $attributes=NULL)
		{
			if(!isset($this->_spec[$name]))
			{
				throw new SERIA_Exception('No such field "'.$name.'". Did you add it to the SERIA_ActionForm constructor?');
			}

			$info = $this->_spec[$name];

			if($caption===false)
			{
				if(!isset($this->_spec[$name]['caption']))
					throw new SERIA_Exception('No caption defined for field "'.$name.'".');
				$caption = $this->_spec[$name]['caption'];
			}


			return self::renderTag('label', $attributes, array(
				'for' => $this->_prefix.$name,
				'class' => ($this->hasError($name)?'ui-state-error-text':''),
			), $caption);
		}

		public function select($name, array $attributes=NULL, $values=NULL)
		{
			$currentValue = ($this->get($name) ? $this->get($name) : NULL);
			$options = array("<option></option>");
			if($values!==NULL)
			{
				foreach($values as $key => $value)
				{
					if(is_object($value) && is_a($value, 'SERIA_MetaObject'))
						$value = $value->__toString();
					$options[] = '<option value="'.htmlspecialchars($key).'"'.($currentValue!==NULL && $key==$currentValue?' selected="selected"':'').'>'.htmlspecialchars($value).'</option>';
				}
			}
			else if(!isset($this->_spec[$name]['values']))
			{
				throw new SERIA_Exception('No values defined for field "'.$name.'".');
			}
			else if(is_array($this->_spec[$name]['values']) || (is_object($this->_spec[$name]['values']) && is_a($this->_spec[$name]['values'], 'SERIA_Dictionary')))
			{
				foreach($this->_spec[$name]['values'] as $key => $value)
				{
					if(is_object($value) && is_a($value, 'SERIA_MetaObject'))
					{
						$value = $value->__toString();
					}
					$options[] = '<option value="'.htmlspecialchars($key).'"'.($currentValue!==NULL && $key==$currentValue?' selected="selected"':'').'>'.htmlspecialchars($value).'</option>';
				}
			}
			else if(is_object($this->_spec[$name]['values']) && is_a($this->_spec[$name]['values'], 'SERIA_FluentQuery'))
			{
				foreach($this->_spec[$name]['values'] as $key => $value)
				{
					$key = $value->FluentBackdoor('get_key');
					$value = $value->__toString();
					$options[] = '<option value="'.htmlspecialchars($key).'"'.($currentValue!==NULL && $key==$currentValue?' selected="selected"':'').'>'.htmlspecialchars($value).'</option>';
				}
			}

			return self::renderTag('select', $attributes, array(
				'id' => $this->_prefix.$name,
				'name' => $this->_prefix.$name,
				'class' => 'select'.($this->hasError($name)?' ui-state-error':''),
			), implode("", $options));
		}

		public function checkbox($name, array $attributes=NULL)
		{
			return self::renderTag('input', array('type'=>'hidden','name'=>$this->_prefix.'bool['.$name.']','value'=>'1')).self::renderTag('input', $attributes, array(
				'type' => 'checkbox',
				'id' => $this->_prefix.$name,
				'name' => $this->_prefix.$name,
				'value' => '1',
				'checked' => ($this->get($name)?'checked':false),
				'class' => 'checkbox'.($this->hasError($name)?' ui-state-error':''),
			));
		}

		public function text($name, array $attributes=NULL)
		{
			return self::renderTag('input', $attributes, array(
				'type' => 'text',
				'id' => $this->_prefix.$name,
				'name' => $this->_prefix.$name,
				'value' => ($this->get($name) ? $this->get($name) : ''),
				'class' => 'text'.($this->hasError($name)?' ui-state-error':''),
			));
		}

		public function datetime($name, array $attributes=NULL)
		{

			return self::renderTag('input', $attributes, array(
				'type' => 'text',
				'id' => $this->_prefix.$name,
				'name' => $this->_prefix.$name,
				'value' => ($this->get($name) ? $this->get($name) : ''),
				'class' => 'datetime'.($this->hasError($name)?' ui-state-error':''),
			));
		}

		public function email($name, array $attributes=NULL)
		{
			return self::renderTag('input', $attributes, array(
				'type' => 'text',
				'id' => $this->_prefix.$name,
				'name' => $this->_prefix.$name,
				'value' => ($this->get($name) ? $this->get($name) : ''),
				'class' => 'email'.($this->hasError($name)?' ui-state-error':''),
			));
		}

		public function password($name, array $attributes=NULL)
		{
			return self::renderTag('input', $attributes, array(
				'type' => 'password',
				'id' => $this->_prefix.$name,
				'name' => $this->_prefix.$name,
				'value' => ($this->get($name)? $this->validationData !== NULL && isset($this->validationData[$name]) ? $this->validationData[$name]: '{__PW_HERE__}' : ''),
				'class' => 'password'.($this->hasError($name)?' ui-state-error':''),
			));
		}

		public function hidden($name, array $attributes=NULL, $value=false)
		{
			if($value===false)
				$value = $this->get($name);

			return self::renderTag('input', $attributes, array(
				'id' => $this->_prefix.$name,
				'type' => 'hidden',
				'name' => $this->_prefix.$name,
				'value' => $value,
				'class' => 'hidden'.($this->hasError($name)?' ui-state-error':''),
			));
		}

		public function slider($name, array $attributes=NULL, $min=0, $max=1, $step=0.01, $value=false)
		{
			if($value===false) $value = $this->get($name);

			return self::renderTag('div', array(
				'class' => 'widget slider',
			), array(), self::renderTag('input', array(
				'id'  => $this->_prefix.$name,
				'type' => 'hidden',
				'name' => $this->_prefix.$name,
				'value' => $value,
				'alt' => '{"min":'.$min.',"max":'.$max.',"step":'.$step.'}',
				'class' => 'widget slider'.($this->hasError($name)?' ui-state-error':''),
			)));
		}

		public function htmlarea($name, array $attributes=NULL)
		{
			return self::renderTag('textarea', $attributes, array(
				'id' => $this->_prefix.$name,
				'name' => $this->_prefix.$name,
				'class' => 'htmlarea'.($this->hasError($name)?' ui-state-error':''),
                        ), htmlspecialchars($this->get($name)));
		}

		public function textarea($name, array $attributes=NULL)
		{
			return self::renderTag('textarea', $attributes, array(
				'id' => $this->_prefix.$name,
				'name' => $this->_prefix.$name,
				'class' => 'textarea'.($this->hasError($name)?' ui-state-error':''),
			), htmlspecialchars($this->get($name)));
		}

		public function errorTemplate($template, $noError='')
		{
			$this->_errorTemplate = $template;
			$this->_noError = $noError;
			return $this;
		}

		public function error($name)
		{
			if($this->errors !== false && isset($this->errors[$name]))
				return str_replace('%MESSAGE%', $this->errors[$name], $this->_errorTemplate);
			else
				return $this->_noError;
		}

		public /*package*/ static function renderTag($tagName, array $attributes=NULL, $defaults=array(), $innerHTML=false)
		{
			$res = '<'.$tagName.' '.self::renderAttributes($attributes, $defaults);
			if($innerHTML!==false)
				$res .= '>'.$innerHTML.'</'.$tagName.'>';
			else
				$res .= '>';
			return $res;
		}

		protected static function renderAttributes(array $attributes=NULL, $defaults=array())
		{
			if($attributes===NULL) $attributes = array();

			foreach($defaults as $key => $value)
				if(!isset($attributes[$key]) && !empty($value))
					$attributes[$key] = $value;
				else if($key=='class')
					$attributes[$key]= $defaults[$key].' '.$attributes[$key];

			$r = ' ';
			foreach($attributes as $name => $value)
				$r .= $name.'="'.htmlspecialchars($value).'" ';

			return $r;
		}

	}
