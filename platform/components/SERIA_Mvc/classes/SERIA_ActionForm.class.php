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
    protected $_errorTemplate = '%MESSAGE%';	// String returned when calling $form->error('fieldname') and there is an error
    protected $_noError = '';			// String returned when calling $form->error('fieldname') and there is no error
    protected $_fileFields = array();

    /**
     *	Construct a form object for allowing users to perform actions.
     *
     *	Usage 1:
     *	__construct($id, $metaObject, $fieldNames)
     *	@param $id		An ID to use when rendering the form
     *	@param $metaObject	An instance of a MetaObject to be edited
     *	@param $fields		An array of field names to allow editing on
     *
     *	Usage 2:
     *	__construct($id, $fieldSpec)
     *	@param $id		An ID to use when rendering the form
     *	@param $fieldSpec	A field specification as the fields returned by MetaObject::Spec()
     */
    function __construct($id, $p1=NULL, array $p2=NULL)
    {
        if(!is_string($id))
            throw new SERIA_Exception('Expecting a form ID as first parameter.');
        $this->id = $id;
        if($p1!==NULL && $p2!==NULL)
            $this->importFields($p1, $p2);
        else if($p1) {
            $this->_spec = $p1;
            foreach($this->_spec as $key => $info)
            {
                if(!is_array($info))
                { // $spec['fields']['myField'] = 'createdBy';
                    throw new SERIA_Exception("SERIA_ActionForm can't accept the type '".$info."'");
                }
                $this->_spec[$key] = SERIA_Meta::parseFieldSpec($info);
            }

            if($this->hasData()) {
                $errors = array();
                foreach($this->_spec as $key => $info) {
                    if(isset($info['validator'])) {
                        $error = $info['validator']->isInvalid($this->get($key));
                        if($error) $errors[$key] = $error;
                    }
                }
                $this->errors = $errors;
            }
        }

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

    public function __get($name) {
        return $this->get($name);
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

	if(isset($src['SAFI']) && $src['SAFI']!=$this->id) {
		// The post data is not intended for this form
		if(isset($this->_data[$name]))
			return $this->_data[$name];
		return NULL;
	}

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
    function setDefault($name, $value)
    {
        $this->_data[$name] = $value;
    }

    /**
     *	Return the specification for the form
     */
    public function getSpec() {
        return $this->_spec;
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
        return $this->id === $id; // '' == NULL is true, so must use strict check.
    }

    /**
     *	Checks if the specified field has an error and returns true/false
     *	@return boolean
     */
    public function hasError($name)
    {
        return ($this->errors!==false && isset($this->errors[$name]));
    }

    public function addError($name, $error) {
	if($this->errors===false) $this->errors = array();
	$this->errors[$name] = $error;
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
     *  Add a file field to the form. This field must be handled separately via your application logic, by accessing the $_FILES array.
     *
     *  function myFileAction() {
     *      $form->addFileField('myFile');
     *      if($form->hasData()) {
     *        // Validate that the $_FILES['myFile'] is whatever you expect
     *        // If it isn't then $form->addError('myFile', 'errormessage');
     *        // Else do your file handling and set $form->success = TRUE;
     *      }
     *      return $form;
     *  }
     *
     *  @param $name
     */
    public function addFileField($name) {
	$this->fileFields[$name] = array();
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
        } else $spec = SERIA_Meta::parseFieldSpec($spec);

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
        /*
                    $legal = array('style','class','title','id','autocomplete','placeholder','onfocus','onblur', );
                    foreach($attributes as $key => $val)
                    {
                        if(!in_array($key, $legal))
                            unset($attributes[$key]);
                    }
        */
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
        if($this->hasError($name) && !isset($attributes['data-validationerror']))
            $attributes['data-validationerror'] = $this->errors[$name];

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

    /**
     *	@param $name		Fieldname
     *	@parem $attributes	Extra attributes to place on the <select> element
     *	@param $values		Key=>Value map of available choices. If NULL, will use values from spec if they exists.
     *	@param $blankChoice	Show a blank choice (should be FALSE if an initial value is provided on a required field)
     */
    public function select($name, array $attributes=NULL, $values=NULL, $blankChoice=TRUE)
    {
        if($this->hasError($name) && !isset($attributes['data-validationerror']))
            $attributes['data-validationerror'] = $this->errors[$name];
        $currentValue = ($this->get($name) ? $this->get($name) : NULL);
        if($blankChoice)
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
        $outerAttributes = array('type'=>'hidden','name'=>$this->_prefix.'bool['.$name.']','value'=>'1');
        if($this->hasError($name) && !isset($attributes['data-validationerror'])) {
            $attributes['data-validationerror'] = $this->errors[$name];
            $outerAttributes['data-validationerror'] = $this->errors[$name];
        }
        return self::renderTag('input', $outerAttributes).self::renderTag('input', $attributes, array(
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
        if($this->hasError($name) && !isset($attributes['data-validationerror']))
            $attributes['data-validationerror'] = $this->errors[$name];
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
        if($this->hasError($name) && !isset($attributes['data-validationerror']))
            $attributes['data-validationerror'] = $this->errors[$name];
        return self::renderTag('input', $attributes, array(
            'type' => 'text',
            'id' => $this->_prefix.$name,
            'name' => $this->_prefix.$name,
            'title' => _t("Format is YYYY-MM-DD HH:MM"),
            'value' => ($this->get($name) ? $this->get($name) : ''),
            'class' => 'datetime'.($this->hasError($name)?' ui-state-error':''),
        ));
    }

    public function date($name, array $attributes=NULL)
    {
        if($this->hasError($name) && !isset($attributes['data-validationerror']))
            $attributes['data-validationerror'] = $this->errors[$name];
        return self::renderTag('input', $attributes, array(
            'type' => 'text',
            'id' => $this->_prefix.$name,
            'name' => $this->_prefix.$name,
            'title' => _t("Format is YYYY-MM-DD"),
            'value' => ($this->get($name) ? $this->get($name) : ''),
            'class' => 'date'.($this->hasError($name)?' ui-state-error':''),
        ));
    }

    public function email($name, array $attributes=NULL)
    {
        if($this->hasError($name) && !isset($attributes['data-validationerror']))
            $attributes['data-validationerror'] = $this->errors[$name];
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
        if($this->hasError($name) && !isset($attributes['data-validationerror']))
            $attributes['data-validationerror'] = $this->errors[$name];
        return self::renderTag('input', $attributes, array(
            'type' => 'password',
            'id' => $this->_prefix.$name,
            'name' => $this->_prefix.$name,
            'value' => ($this->get($name) ? $this->validationData !== NULL && isset($this->validationData[$name]) ? $this->validationData[$name]: '{__PW_HERE__}' : ''),
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

    public function file($name, array $attributes=NULL)
    {
        return self::renderTag('input', $attributes, array(
	    'type' => 'file',
            'id' => $this->_prefix.$name,
            'name' => $this->_prefix.$name,
            'class' => 'file'.($this->hasError($name)?' ui-state-error':''),
        ), htmlspecialchars($this->get($name)));
    }

    public function errorTemplate($template, $noError='')
    {
        $this->_errorTemplate = $template;
        $this->_noError = $noError;
        return $this;
    }

    /**
     *	Render an error message using $this->_errorTemplate
     */
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
