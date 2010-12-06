<?php
	abstract class SERIA_Form
	{
		public $object;
		protected $validationData = NULL;
		protected $subForms = array(); // an array of subForms that are attached to this form.
		protected $errors = array();
		protected $_fieldSpecCache = NULL;
		public $_buttons = array();

		/**
		*	Overridable. Never set $this->object if you override this function.
		*	If you override the constructor you have to 
		*/
		public function __construct(&$object=null)
		{
			if ($object !== null) {
				$interfaces = array_flip(class_implements($object));

				if(!isset($interfaces['SERIA_IFluentObject']) && !isset($interfaces['SERIA_IFluentAccess']))
					throw new SERIA_Exception('The class "'.get_class($object).'" must implement either SERIA_IFluentObject or SERIA_IFluentAccess, or you must override the getFormSpec() and the get()-methods of "'.get_class($this).'".');

				$this->object = $object;

				// Allow developers to override and modify any form by hooking into for example 'SERIA_UserEditForm'
				SERIA_Hooks::dispatch(get_class($this), $this, $object);
			}
		}

                function addButton($caption, $onclick, $weight=0)
                {
                        $this->_buttons[] = array('caption' => $caption, 'onclick' => $onclick, 'weight' => $weight);
                        return $this;
                }

/**
*	START OF OVERRIDABLE METHODS FOR CREATING FORMS
*/
		/**
		*	ALWAYS OVERRIDE THIS
		*	This method should return a structured array defining the form.
		*
		*	array(
		*		'field_name' => array(						// if the field name matches the FluentObjects field names, spec is fetched from the SERIA_Fluent::getFieldSpec($fluentObjectClassName)
		*			'fieldtype => ['text','textarea','date',...],
		*			'caption' => _t('Some translated caption'),
		*			'weight' => 0,						// fields are sorted from low to high according to weight
		*		),
		*	),
		*/
		abstract public static function getFormSpec();

		/**
		*	ALWAYS OVERRIDE THIS
		*	Should return a suitable, translated caption for the form. Mainly used in subforms.
		*/
		abstract public static function caption();

		/**
		*	ALWAYS OVERRIDE THIS
		*	This method should assume everything have been validated and try to store it.
		*	If an error happens it should throw an Exception - since database must be rolled back.
		*
		*	WARNING! Never call this function yourself. This function is called by executing ->receive().
		*	Calling it directly will result in attached forms not handling their data.
		*/
		abstract public function _handle($data);

		/**
		*	OVERRIDE THIS IF YOU WANT TO SUPPORT DELETION
		*	Returns true if the currently logged in user can delete the object, else returns false
		*/
		public function isDeletable()
		{
			return false;
		}

		/**
		*	OVERRIDE THIS TO SUPPORT DELETION
		*	Performs the delete operation on the current object.
		*/
		public function delete()
		{
			throw new SERIA_Exception('Override the '.get_class($this).'::_delete() method to support deletion.');
		}
/**
*	END OF OVERRIDABLE METHODS
*/

		/**
		*	Check SERIA_FluentObject::getFieldSpec for syntax for field spec
		*/
		public function getFieldSpec()
		{
			if($this->object === NULL)
				return $this->_getFinalFieldSpec();

			return SERIA_Fluent::getFieldSpec(get_class($this->object));
// 			return eval('return '.get_class($this->object).'::getFie__REMOVED__ldSpec();');
		}

		/**
		*	Special function used by templates to receive form spec for templates
		*/
		public function _getFormSpec()
		{
			$formSpec = $this->_getInternalFormSpec();
			foreach($formSpec as $key => $spec)
				if(isset($spec['special']))
					unset($formSpec[$key]);
			return $formSpec;
		}

		public function _getFormButtons()
		{
			usort($this->_buttons, create_function('$a,$b', 'if($a["weight"]==$b["weight"]) return 0; return($a["weight"]<$b["weight"])?-1:1;'));
			return $this->_buttons;
		}

		protected function _getInternalFormSpec()
		{
			$fieldSpec = $this->getFieldSpec();
			$formSpec = call_user_func(array(get_class($this), 'getFormSpec'), get_class($this));
			SERIA_Form::_recursiveExtend($formSpec, $fieldSpec);
			return $formSpec;
		}

		protected function _getFinalFieldSpec($root = false)
		{
			if($this->_fieldSpecCache === NULL)
			{
				$this->_fieldSpecCache = array();
			}
			else if($root === false)
			{ // this is the second call to this function
				return $this->_fieldSpecCache;
			}

			if($root === false)
			{
				$root = $this->_getInternalFormSpec();
			}

			foreach($root as $fieldName => $spec)
			{
				if(isset($spec['formSpec']))
				{ // subform
					$this->_getFinalFieldSpec($spec['formSpec']);
				}
				else
				{
					$this->_fieldSpecCache[$fieldName] = $spec;
				}

			}

			return $this->_fieldSpecCache;
		}

		protected static function _recursiveExtend(&$formSpec, &$fieldSpec)
		{
			foreach($formSpec as $fieldName => $spec)
			{
				if(!is_array($spec))
				{ // ignore fields that are special
					$formSpec[$fieldName] = $fieldSpec[$fieldName];
					$formSpec[$fieldName]['special'] = $spec;
				}
				else if(isset($fieldSpec[$fieldName]))
				{
					foreach($fieldSpec[$fieldName] as $key => $val)
						if(!isset($formSpec[$fieldName][$key]))
							$formSpec[$fieldName][$key] = $val;
				}
				else if($spec['fieldtype'] === 'fieldset' || $spec['fieldtype'] === 'group')
					SERIA_Form::_recursiveExtend($formSpec[$fieldName]['formSpec'], $fieldSpec);
			}
		}

		/**
		*	Validates all fields, adds error messages to $this->errors and returns false if no errors were found.
		*	NOTE! Errors in subforms are stored inside the $errors property of the subform.
		*/
		public function validate($data, $fixedData=array())
		{
			foreach($fixedData as $key=>$val)
				$data[$key] = $val;

			// Special trick to hide passwords from HTML source code :-)
			foreach($data as $key => $val)
				if($val == '{__PW_HERE__}')
					$data[$key] = $this->get($key);

			$this->injectSpecials($data);

			$this->validationData = $data;

			$hasErrors = false;

			foreach($this->subForms as $subForm)
			{
				if(!$subForm['form']->validate($data))
					$hasErrors = true;
			}

			$spec = $this->_getFinalFieldSpec();
			foreach($spec as $fieldName => $info)
			{
				if($info['fieldtype'] === 'captcha')
				{
					$captcha = $this->captcha($fieldName);
					if(!$captcha->checkNumber($data[$fieldName]))
					{
						$this->errors[$fieldName] = _t('Please try again');
						$hasErrors = true;
					}
				}
				else
				{
					$error = isset($info['validator']) ? $info['validator']->isInvalid($data[$fieldName], array('object' => $this->object, 'field' => $fieldName)) : false;
					if($error)
					{
						$this->errors[$fieldName] = $error;
						$hasErrors = true;
					}
				}
			}

			return !$hasErrors;
		}

		/**
		*	Inverted alias of ->validate, since some people may like this syntax better:-)
		*/
		public function isInvalid($data)
		{
			return !$this->validate($data);
		}

		public function injectSpecials(&$data)
		{
			foreach($this->_getFinalFieldSpec() as $key => $val)
			{
				if(isset($val['special']))
				{
					switch($val['special'])
					{
						case 'createdBy' :
							if(!$this->object->getKey())
								$data[$key] = SERIA_Base::userId();
							else
								$data[$key] = $this->object->get($key);
							break;
						case 'modifiedBy' :
							$data[$key] = SERIA_Base::userId();
							break;
						case 'createdDate' :
							if(!$this->object->getKey())
								$data[$key] = time();
							else
								$data[$key] = $this->object->get($key);
							break;
						case 'modifiedDate' :
							$data[$key] = time();
							break;
					}
				}
			}
		}

		/**
		*	Receives data and performs whatever the form decides. Returns true if the
		*	data was received successfully, or false if validation or saving failed.
		*/
		public function receive($data, $fixedData=array())
		{
			if(sizeof($data) === 0)
				return false;

			foreach($fixedData as $key => $value)
				$data[$key] = $value;

			// Special trick to hide passwords from HTML source code :-)
			$toNotBeSaved = array();
			foreach($data as $key => $val)
				if($val == '{__PW_HERE__}')
				{
					$toNotBeSaved[] = $key;
					$data[$key] = $this->get($key);
				}

			// Special trick for checkboxes
			$fsTmp = $this->_getFinalFieldSpec();
			foreach($data as $key => $val)
			{
				if(!isset($fsTmp[$key]))
				{ // most likely $key is validFieldNameCheckbox, and we need to strip Checkbox away from the end and check again.
					$fTmp = substr($key,0,-8);
					if(!isset($data[$fTmp])) 
						$data[$fTmp] = null;
				}
			}

			if($this->object)
				$this->injectSpecials($data);

			// validate self
			$errorless = false;

			if(sizeof($data))
			{
				$errorless = call_user_func_array(array($this,'validate'), array($data));
			}

			if($errorless)
			{
				foreach($toNotBeSaved as $felt)
					unset($data[$felt]);

				call_user_func_array(array($this, '_handle'), array($data));

				foreach($this->getSubForms() as $name => $subFormSpec)
				{
					call_user_func_array(array($subFormSpec['form'],'_handle'), array($data));
				}


				return true;
			}
			else
			{
				return false;
			}
		}

		public function extend($form)
		{
throw new Exception('Not implemented yet. Use subForm instead.');
			// merge $form->fields into this form's fields
			$this->extendedBy[] = $form;
		}

		/**
		*	Should output the form, including <form></form>-tags.
		*/
		function output($template=false,$params=array())
		{
			if ($template === false)
				$template = SERIA_ROOT.'/seria/platform/templates/seria/special/genericForm.php';
			$params['form'] = $this;
			return SERIA_Template::parseToString($template, $params);
		}
		/**
		*	Begins the output of a form, returns html code <form action=$action method=$method name=$name>
		*/
		public function begin($name='', $method='post', $action='') {
			return '<form class="SERIA_Form" method="'.$method.'" '.($name ? 'name="'.$name.'"' : '').' '.($action ? 'action="'.$action.'"' : '').'>';
		}

		public function end() {
			return '</form>';
		}
		
		public function submit($name='submit', $caption=false) {
			if($caption === false) $caption = _t('Submit');
			return '<button type="submit" name="'.$name.'">'.$caption.'</button>';
		}

		public function image($name='submit', $src, $caption=false, $attributes = array())
		{
			if($caption===false)
			{
				$spec = $this->_getFinalFieldSpec();
				if(!isset($spec[$name]) || !isset($spec[$name]['caption']))
					$caption = _t('Submit');
				else
					$caption = $spec[$name]['caption'];
			}
			return '<input '.SERIA_Form::renderAttributes($attributes, array(
				'name' => $name,
				'type' => 'image',
				'src' => $src,
				'alt' => $caption,
			)).'>';
		}

		public function subForm($name, $form, $weight=0)
		{
			$this->subForms[$name] = array('form' => $form, 'weight' => $weight);
		}

		public function getSubForms()
		{
			usort($this->subForms, create_function('$a,$b', 'if($a["weight"]==$b["weight"])return 0;return($a["weight"]<$b["weight"])?-1:1;'));
			return $this->subForms;
		}
		
		public function get($name)
		{
			$spec = $this->_getFinalFieldSpec();

			if($this->validationData !== NULL)
			{
				return (isset($this->validationData[$name]) ? $this->validationData[$name] : '');
			}
			else if($this->object)
			{
				$fieldSpec = $this->getFieldSpec();
				if(isset($fieldSpec[$name]))
					return $this->object->get($name);
			}

			if(isset($spec[$name]) && isset($spec[$name]['default']))
				return $spec[$name]['default'];

			return '';
		}

		/**
		 *	Source of dropdown list is $spec['options'] which may be an array or an iterator object.
		 */
		public function select($name, $attributes = array())
		{
			$current = $this->get($name);

			$spec = $this->_getFinalFieldSpec();
			$contents = '<select name="'.$name.'" id="'.$name.'">';

			if(!isset($spec[$name]))
				throw new SERIA_Exception('No such field "'.$name.'".');

			foreach($spec[$name]['options'] as $value => $caption)
				$contents.= '<option value="'.$value.'"'.($value==$current?' selected="selected"':'').'>'.htmlspecialchars($caption).'</option>';

			$contents.='</select>';

			return $contents;
			
		}
		
		public function label($name, $caption=false, $attributes = array())
		{
			if($caption===false)
			{
				$spec = $this->_getFinalFieldSpec();
				if(!isset($spec[$name]))
					throw new SERIA_Exception('No such field "'.$name.'".');
				if(!isset($spec[$name]['caption']))
					throw new SERIA_Exception('No caption defined for field "'.$name.'".');
				$caption = $spec[$name]['caption'];
			}

			return '<label for=\''.$name.'\'>'.$caption.'</label>';
		}

		public function checkbox($name, $attributes = array())
		{
			return '<input'.SERIA_Form::renderAttributes(array('name'=>$name.'Checkbox', 'type'=>'hidden','value'=>'1')).'><input'.SERIA_Form::renderAttributes($attributes, array(
				'type' => 'checkbox',
				'id' => $name,
				'name' => $name,
				'value' => '1',
			)).' '.($this->get($name)?'checked="checked"':'').'>';
		}

		public function __call($method, $arguments)
		{
			return $this->text($arguments[0]);
		}

		public function text($name, $attributes = array())
		{
			return '<input'.SERIA_Form::renderAttributes($attributes, array(
				'type' => 'text',
				'id' => $name,
				'name' => $name,
				'value' => $this->get($name),
			)).'>';

		}
		public function password($name, $attributes = array())
		{
			return '<input'.SERIA_Form::renderAttributes($attributes, array(
				'type' => 'password',
				'id' => $name,
				'name' => $name,
				'value' => ($this->get($name) ? $this->validationData !== NULL && isset($this->validationData[$name]) ? $this->validationData[$name]: '{__PW_HERE__}' : ''),
			)).'>';
		}

		public function hidden($name, $attributes = array())
		{
			$value = $this->get($name);
			return ($value !== null ? '<input type=\'hidden\' name=\''.htmlspecialchars($name).'\' value=\''.htmlspecialchars($value).'\'>' : '');
		}

		public function textarea($name, $attributes = array())
		{
			return '<textarea name="'.htmlspecialchars($name).'">'.htmlspecialchars($this->get($name)).'</textarea>';
		}

		public function error($name)
		{
			if(isset($this->errors[$name]))
				return $this->errors[$name];
			else
				return false;
		}

		public function captcha($name)
		{
			$spec = $this->_getFinalFieldSpec();

			if(!isset($spec[$name]))
				throw new SERIA_Exception('No such field "'.$name.'".');

			$spec = $spec[$name];

			if(isset($spec['key']))
				$key = $spec['key'];
			else
				$key = NULL;

			if(isset($spec['max']))
				$max = intval($spec['max']);
			else
				$max = NULL;

			if(isset($spec['min']))
				$min = intval($spec['min']);
			else
				$min = NULL;

			return new SERIA_Captcha($key, $min, $max);
		}

		protected static function renderAttributes($attributes, $defaults=array())
		{
			foreach($defaults as $key => $value)
				if(!isset($attributes[$key]))
					$attributes[$key] = $value;

			$r = ' ';
			foreach($attributes as $name => $value)
				$r .= $name.'="'.htmlspecialchars($value).'" ';

			return $r;
		}
	}
