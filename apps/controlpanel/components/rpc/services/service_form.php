<?php
	$baseparams = $_GET;
	unset($baseparams['id']);
	if (!$baseparams)
		$baseparams['rpcsys'] = 'Whiskey'; /* Dummy field */
	$basepath = explode('?', $_SERVER['REQUEST_URI']);
	$basepath = $basepath[0];
	$baseurl = SERIA_HTTP_ROOT.$basepath.'?'.http_build_query($baseparams);
?>
		<form method='post'>
			<div>
				<h1><?php echo htmlspecialchars($form->caption()); ?></h1>
				<?php
					echo $form->output(SERIA_ROOT.'/seria/platform/templates/seria/special/genericInlineForm.php');
				?>
			</div>
			<div>
				<button type='submit'><?php echo htmlspecialchars(_t('Save')); ?></button>
				<button type='button' onclick="<?php echo htmlspecialchars('location.href = \''.$baseurl.'\';'); ?>"><?php echo htmlspecialchars(_t('Cancel')); ?></button>
			</div>
		</form>
