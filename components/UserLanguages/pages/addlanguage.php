<?php
	SERIA_Base::pageRequires('admin');
	$form = AvailableUserLocale::getAddLocaleAction();
	if ($form->hasData() && $form->errors === false) {
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'?route=userlanguages/configure');
		die();
	}
?>
<s:gui title="{'Add language'|_t|htmlspecialchars}">
	<div>
		<?php
			$this->gui->activeMenuItem('controlpanel/users/languages');
			echo $form->begin();
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
					<td colspans='2'>
						<?php echo $form->submit(_t('Add language locale')); ?>
						<input class='prelocale' type='button' onclick="<?php echo htmlspecialchars('location.href = '.SERIA_Lib::toJSON(AvailableUserLocale::getAddCustomLocaleActionUrl()->__toString())); ?>" value="{{"Add custom language locale"|_t|htmlspecialchars}}" />
					</td>
				</tr>
			</tfoot>
			<tbody>
				<tr>
					<th><?php echo $form->label('strid'); ?></th>
					<td><?php echo $form->select('strid'); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
			echo $form->end();
		?>
	</div>
</s:gui>