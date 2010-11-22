<?php
	SERIA_Base::pageRequires('admin');
	$addlocale_form = AvailableUserLocale::getAddCustomLocaleAction();
	if ($addlocale_form->hasData() && $addlocale_form->errors === false) {
		/* done */
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'?route=userlanguages/configure');
		die();
	}
?>
<s:gui title="{'Add custom language'|_t|htmlspecialchars}">
	<div>
		<?php
			$this->gui->activeMenuItem('controlpanel/users/languages');
			echo $addlocale_form->begin();
		?>
		<table>
			<?php
			/* According to w3 spec:
			 * "TFOOT must appear before TBODY within a TABLE definition so that
			 * user agents can render the foot before receiving all of the
			 * (potentially numerous) rows of data."
			 */
			?>
			<tfoot>
				<tr>
					<?php
					/* This is a violation of the w3 recommendations about tfoot
					 * contents, and may look weird on printed or spoken media.
					 */
					?>
					<td colspans='2'><?php echo $addlocale_form->submit(_t('Add new language')); ?></td>
				</tr>
			</tfoot>
			<tbody>
				<tr>
					<th><?php echo $addlocale_form->label('language'); ?></th>
					<td><?php echo $addlocale_form->select('language'); ?></td>
				</tr>
				<tr>
					<th><?php echo $addlocale_form->label('country'); ?></th>
					<td><?php echo $addlocale_form->select('country'); ?></td>
				</tr>
				<tr>
					<th><?php echo $addlocale_form->label('displayName'); ?></th>
					<td><?php echo $addlocale_form->field('displayName'); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
			echo $addlocale_form->end(); 
		?>
	</div>
</s:gui>