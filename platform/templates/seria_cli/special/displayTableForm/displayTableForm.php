<?php

	function seria_special_templates_renderDisplayTableForm($form, $formSpec, $subPart = false)
	{
		?>
		<div class='formDisplayTable'>
		<!--[if lt IE 8]><table cellspacing='0'><![endif]-->
		<?php
		foreach($formSpec as $fieldName => $spec)
		{
			if(!isset($spec['fieldtype']))
				throw new SERIA_Exception('The form does not specify which field type to use for the field "'.$fieldName.'".');

			$type = $spec['fieldtype'];

			$required = isset($spec['validator']) && $spec['validator']->hasRule(SERIA_Validator::REQUIRED);

			switch($type)
			{
				case 'captcha' : 
					?>
					<div class="formfield"><!--[if lt IE 8]><tr><![endif]-->
						<div class='label'>
							<!--[if lt IE 8]><td class='labelDisplayTableForm'><![endif]--><?php echo $form->label($fieldName, (isset($spec['caption']) ? $spec['caption'] : _t("Are you human?"))); ?><!--[if lt IE 8]></td><![endif]-->
						</div>
						<div class='field'>
							<!--[if lt IE 8]><td><![endif]-->
								<?php
								$captcha = $form->captcha($fieldName);
								if(!trim($form->get($fieldName)) || !$captcha->checkNumber($form->getValue($fieldName)))
								{
									?>
									<div class='containerDisplayTableForm' style='overflow: hidden; margin: 1px; border: 1px solid black; height: 30px;'>
										<div style='font-size: 20px; line-height: 20px; margin-top: 5px; margin-bottom: 5px; color: blue; text-align: center;'><?php echo $captcha->getNumber(); ?></div>
									</div>
									<?php
								}
								?>
							<!--[if lt IE 8]></td><![endif]-->
						</div>
					<!--[if lt IE 8]></tr><![endif]--></div>
					<div class="formfield<?php echo ($required ? ' required': ''); ?>"><!--[if lt IE 8]><tr><![endif]-->
						<div class='label'>
							<!--[if lt IE 8]><td class='labelDisplayTableForm'></td><![endif]-->
						</div>
						<div class='field'>
							<!--[if lt IE 8]><td><![endif]-->
								<?php
								echo $form->text($fieldName, array('class' => 'normalDisplayTableForm', 'title' => _t('Check to distinguish between humans and machines. Please type the numbers from above.')));
								if($error = $form->error($fieldName))
									echo '<p class="error">'.$error.'</p>';
								?>
							<!--[if lt IE 8]></td><![endif]-->
						</div>
					<!--[if lt IE 8]></tr><![endif]--></div>
					<?php
					break;
				case 'group' :
					seria_renderGenericForm($form, $formSpec[$fieldName]['formSpec'], true);
					break;
				case 'fieldset' :
					throw new Exception('This template can not support fieldset');
				case 'checkbox' :
					?>
					<div class="formfield<?php echo ($required ? ' required': ''); ?>"><!--[if lt IE 8]><tr><![endif]-->
						<div class='label'><!--[if lt IE 8]><td class='labelDisplayTableForm'><![endif]--><!--[if lt IE 8]></td><![endif]--></div>
						<div class='field'><!--[if lt IE 8]><td><![endif]-->
							<?php
								echo $form->checkbox($fieldName, array('class' => 'checkbox')).' '.$form->label($fieldName);
								if($error = $form->error($fieldName))
									echo '<p class="error">'.$error.'</p>';
							?>
						<!--[if lt IE 8]></td><![endif]--></div>
					<!--[if lt IE 8]></tr><![endif]--></div>
					<?php
					break;
				case 'textarea' :
					?>
					<div class="formfield<?php echo ($required ? ' required': ''); ?>"><!--[if lt IE 8]><tr><![endif]-->
						<div class='label'><!--[if lt IE 8]><td class='labelDisplayTableForm'><![endif]--><?php echo $form->label($fieldName);?><!--[if lt IE 8]></td><![endif]--></div>
						<div class='field'><!--[if lt IE 8]><td><![endif]-->
							<?php
								echo $form->$type($fieldName);
								if($error = $form->error($fieldName))
									echo '<p class="error">'.$error.'</p>';
							?>
						<!--[if lt IE 8]></td><![endif]--></div>
					<!--[if lt IE 8]></tr><![endif]--></div>
					<?php
					break;
				case 'hidden':
					echo $form->$type($fieldName, array());
					break;
				default :
					?>
					<div class="formfield<?php echo ($required ? ' required': ''); ?>"><!--[if lt IE 8]><tr><![endif]-->
						<div class='label'><!--[if lt IE 8]><td class='labelDisplayTableForm'><![endif]--><?php echo $form->label($fieldName); ?><!--[if lt IE 8]></td><![endif]--></div>
						<div class='field'><!--[if lt IE 8]><td><![endif]-->
							<?php
								$classes = implode(' ', array_merge(array('normalDisplayTableForm'), isset($spec['selectorClasses']) ? $spec['selectorClasses'] : array()));
								echo $form->$type($fieldName, array('class' => $classes));
								if($error = $form->error($fieldName))
									echo '<p class="error">'.$error.'</p>';
							?>
						<!--[if lt IE 8]></td><![endif]--></div>
					<!--[if lt IE 8]></tr><![endif]--></div>
					<?php
					break;
			}
		}
		?>
		<!--[if lt IE 8]></table><![endif]-->
		</div>
		<?php
		if(!$subPart && sizeof($form->getSubForms()))
		{
			$forms = "";
			echo '<div class="tabs" style="padding-top: 20px"><ul>';
			foreach($form->getSubForms() as $formName => $sub)
			{
				eval('$caption = '.get_class($sub['form']).'::caption();');
				echo "<li><a href=\"#$formName\"><span class='caption'>".$caption."</span></a></li>";
				ob_start();
				seria_special_templates_renderDisplayTableForm($sub["form"], $sub["form"]->_getFormSpec());
				$form_output = ob_get_clean();
				$forms .= "<div id=\"$formName\">".$form_output."</div>";
			}
			echo '</ul>'.$forms."</div>";
		}
	}

?>