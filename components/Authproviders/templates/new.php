<h1><?php echo htmlspecialchars(_t('Create a new authentication provider')); ?></h1>
<p><?php echo htmlspecialchars(_t('Please choose from the list below which kind of authentication provider you want to create.')); ?></p>
<ul>
	<?php
		foreach ($creators as &$creator) {
		?>
			<li>
				<a href="<?php echo htmlspecialchars(SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/new.php?id='.urlencode($creator['id'])); ?>"><?php echo htmlspecialchars($creator['caption']); ?></a>
			</li>
		<?php
		}
	?>
</ul>