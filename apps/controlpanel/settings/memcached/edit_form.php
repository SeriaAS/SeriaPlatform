	<?php echo $form->start(); ?>
	
		<?php echo $form->errors(); ?>
		
		<table>
			<tr>
				<td>
					<?php echo $form->label('address', _t('Hostname or IP address (IP address preferred): ')); ?>
				</td>
				<td>
					<?php echo $form->text('address'); ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo $form->label('port', _t('Port: ')); ?>
				</td>
				<td>
					<?php echo $form->text('port'); ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo $form->label('enabled', _t('Enabled: ')); ?>
				</td>
				<td>
					<?php echo $form->checkbox('enabled'); ?>
				</td>
			</tr>
		</table>
		<?php echo $form->submit(_t('Save')); ?>
		
	<?php echo $form->end(); ?>
