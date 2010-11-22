<?php

	function seria_renderGenericForm($form, $formSpec, $subPart = false)
	{
		$r = '';
		foreach($formSpec as $fieldName => $spec)
		{
			if(!isset($spec['fieldtype']))
				throw new SERIA_Exception('The form does not specify which field type to use for the field "'.$fieldName.'".');

			$type = $spec['fieldtype'];

			$required = isset($spec['validator']) && $spec['validator']->hasRule(SERIA_Validator::REQUIRED);

			switch($type)
			{
				case 'captcha' : 
					$r .= '<div class="formfield'.($required?' required':'').'" style="padding-bottom: 2px; padding-top: 2px; overflow: hidden;"><div style="width: 150px; float:left">'.$form->label($fieldName, (isset($spec['caption']) ? $spec['caption'] : _t("Are you human?"))).'</div><div style=\'float: left;\'>';
					$captcha = $form->captcha($fieldName);
					if(!trim($form->get($fieldName)) || !$captcha->checkNumber($form->getValue($fieldName)))
					{
						$r .= '<div style=\'width: 298px; height: 30px; border: 1px solid black; margin: 1px; background-color: white; color: blue; text-align: center;\'><div style=\'font-size: 20px; line-height: 20px; margin-top: 5px; margin-bottom: 5px;\'>'.$captcha->getNumber().'</div></div>';
					}
					$r .= $form->text($fieldName, array('style' => 'width: 300px'));
					if($error = $form->error($fieldName))
						$r .= '<p class="error">'.$error.'</p>';
					$r .= '</div></div>';
					break;
				case 'group' :
					$r .= seria_renderGenericForm($form, $formSpec[$fieldName]['formSpec'], true);
					break;
				case 'fieldset' :
					$r .= '<fieldset id=".$fieldName." style="padding-bottom: 3px">'.seria_renderGenericForm($form, $formSpec[$fieldName]['formSpec'], true).'</fieldset>';
					break;
				case 'checkbox' :
					$r .= '<div class="formfield'.($required?' required':'').'" style="padding-bottom: 2px; padding-top: 2px; margin-left: 150px; overflow: hidden;">'.$form->checkbox($fieldName).' '.$form->label($fieldName);
					if($error = $form->error($fieldName))
						$r .= '<p class="error">'.$error.'</p>';
					$r .= '</div>';
					break;
				case 'textarea' :
					$r .= '<div class="formfield'.($required?' required':'').'" style="padding-bottom: 2px; padding-top: 2px; overflow: hidden;"><div style="width: 150px; float:left">'.$form->label($fieldName).'</div>';
					$r .= $form->$type($fieldName, array('style' => 'width: 300px; height: 150px;'));
					if($error = $form->error($fieldName))
						$r .= '<p class="error">'.$error.'</p>';
					$r .= '</div>';
					break;
				case 'hidden':
					$r .= $form->$type($fieldName, array());
					break;
				default :
					$r .= '<div class="formfield'.($required?' required':'').'" style="padding-bottom: 2px; padding-top: 2px; overflow: hidden;"><div style="width: 150px; float:left">'.$form->label($fieldName).'</div>';
					$classes = implode(' ', isset($spec['selectorClasses']) ? $spec['selectorClasses'] : array());
					$r .= $form->$type($fieldName, array('style' => 'width: 300px', 'class' => $classes));
					if($error = $form->error($fieldName))
						$r .= '<p class="error">'.$error.'</p>';
					$r .= '</div>';
					break;
			}
		}
		if(!$subPart && sizeof($form->getSubForms()))
		{
			$forms = "";
			$r .= '<div class="tabs" style="padding-top: 3px; min-height: 150px"><ul>';
			foreach($form->getSubForms() as $formName => $sub)
			{
				eval('$caption = '.get_class($sub['form']).'::caption();');
				$r .= "<li><a href=\"#$formName\"><span class='caption'>".$caption."</span></a></li>";
				$forms .= "<div id=\"$formName\">".seria_renderGenericForm($sub["form"], $sub["form"]->_getFormSpec())."</div>";
			}
			$r .= '</ul>'.$forms."</div>";
		}
		return $r;
	}

?>
