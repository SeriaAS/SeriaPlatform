	<?php echo $form->start(); ?>
	
		<?php echo $form->errors(); ?>
		
		<table>
			<tr>
				<td>
					<?php echo $form->label('title', _t('Title: ')); ?>
				</td>
				<td>
					<?php echo $form->text('title'); ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo $form->label('hostname', _t('Hostname: ')); ?>
				</td>
				<td>
					<?php echo $form->text('hostname'); ?>
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
					<?php echo $form->label('username', _t('Username: ')); ?>
				</td>
				<td>
					<?php echo $form->text('username'); ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo $form->label('password', _t('Password: ')); ?>
				</td>
				<td>
					<?php echo $form->password('password'); ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo $form->label('root', _t('Root directory: ')); ?>
				</td>
				<td>
					<?php echo $form->text('root'); ?>
				</td>
			</tr>
		</table>
		<?php echo $form->submit(_t('Save')); ?>
		
	<?php echo $form->end(); ?>
