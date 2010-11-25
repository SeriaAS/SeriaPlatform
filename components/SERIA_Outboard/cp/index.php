<?php
	require('common.php');

	$gui->activeMenuItem('controlpanel/outboard');


	$contents = '<h1 class="legend">'._t("Outboard").'</h1>
<p>'._t("Manage user generated content and other interactive features for your users.").'</p>';

	ob_start();
?>
	<?php
		$topCategories = $gui->getMenuItemsLevel(2);
	?>
	<h2><?php echo _t('What do you want to do?'); ?></h2>
	<?php
		$items = array();
		foreach ($topCategories as $id => $info) {
			ob_start();
			?>
				<div style='overflow: hidden; float: left;'>
					<div style='float: left;'>
						<img alt='[Icon]' src="<?php echo htmlspecialchars($info['icon']); ?>" />
					</div>
					<div style='float: left;'>
						<h2><a href="<?php echo htmlspecialchars($info['url']); ?>"><?php echo $info['title'] ?></a></h2>
						<?php
							$subitems = $gui->getMenuItems($id);
							if ($subitems) {
								?>
									<ul>
										<?php
											foreach ($subitems as $subitem) {
												?>
													<li><a href="<?php echo htmlspecialchars($subitem['url']); ?>"><?php echo $subitem['title']; ?></a></li>
												<?php
											}
										?>
									</ul>
								<?php
							} else {
								?>
									<ul><li><em><?php echo _t("No alternatives here..."); ?></em></li></ul>
								<?php
							}
						?>
					</div>
				</div>
			<?php
			$items[] = ob_get_clean();
		}
		/*
		 * Flat list of items: Will be shown in a two-column structure:
		 */
		$row = array();
		$rows = array();
		foreach ($items as $count => $item) {
			if (count($row)) {
				$row[] = $item;
				$rows[] = $row;
				$row = array();
			} else {
				$row[] = $item;
			}
		}
		if ($row)
			$rows[] = $row;
		$col_styles = array(
			'width: 300px;',
			''
		);
		foreach ($rows as $row) {
			?>
				<div style='overflow: hidden;'>
					<?php
						foreach ($row as $col => $item) {
							?>
								<div style='float: left; overflow: hidden; <?php echo $col_styles[$col]; ?>'>
									<?php echo $item; ?>
								</div>
							<?php
						}
					?>
				</div>
			<?php
		}
	?>
<?php
	$contents .= ob_get_clean();
	$gui->contents($contents);

	echo $gui->output();
