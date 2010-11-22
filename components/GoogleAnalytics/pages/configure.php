<?php
	SERIA_Base::pageRequires('admin');
	SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/components/GoogleAnalytics/pages/configure.css');
	$component = SERIA_Components::getComponent('GoogleAnalyticsComponent');
	if (!$component)
		throw new SERIA_Exception('Component (GoogleAnalyticsComponent) not found.');
?>
<s:gui title="{'Configure Google Analytics'|_t|htmlspecialchars}">
	<h1 class='legend'>{{'Configure Google Analytics'|_t}}</h1>
	<?php
		$action = $component->configureGoogleAnalyticsAction();
		echo $action->begin();
		?>
			<table class='google-analytics-settings'>
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
						<td colspans='2'><?php echo $action->submit(_t('Save settings')); ?></td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<th><?php echo $action->label('googleAnalyticsEnabled')?></th>
						<td class='padtop'>
							<?php
								echo $action->checkbox('googleAnalyticsEnabled');
							?> {{'Enabled'|_t}}
						</td>
					</tr>
					<tr>
						<th><?php echo $action->label('googleAnalyticsId'); ?></th>
						<td><?php echo $action->field('googleAnalyticsId'); ?></td>
					</tr>
				</tbody>
			</table>
		<?php
		echo $action->end();
	?>
</s:gui>