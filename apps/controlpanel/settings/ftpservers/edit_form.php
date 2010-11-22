	<?php echo $form->start(); ?>
	
		<?php echo $form->errors(); ?>
		
		<table>
			<tr>
				<td>
					<?php echo $form->label('host', _t('Hostname: ')); ?>
				</td>
				<td>
					<?php echo $form->text('host'); ?>
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
					<?php echo $form->label('pasv', _t('Mode: ')); ?>
				</td>
				<td>
					<?php echo $form->select('pasv', array(0 => _t('Active'), 1 => _t('Passive'))); ?>
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
					<?php echo $form->label('file_root', _t('File root: ')); ?>
				</td>
				<td>
					<?php echo $form->text('file_root'); ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo $form->label('request_host', _t('Request hostname: ')); ?>
				</td>
				<td>
					<?php echo $form->text('request_host'); ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo $form->label('request_path', _t('Request path: ')); ?>
				</td>
				<td>
					<?php echo $form->text('request_path'); ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo $form->label('delay', _t('Delay in seconds before usage after upload: ')); ?>
				</td>
				<td>
					<?php echo $form->text('delay'); ?>
				</td>
			</tr>
		</table>
		<?php echo $form->submit(_t('Save')); ?>
		
	<?php echo $form->end(); ?>
