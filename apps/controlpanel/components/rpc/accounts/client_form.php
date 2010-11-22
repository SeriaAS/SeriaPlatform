<?php
	$baseparams = $_GET;
	if (isset($baseparams['id']))
		unset($baseparams['id']);
	if (!$baseparams)
		$baseparams['rpcsys'] = 'Whiskey'; /* Dummy field */
	$basepath = explode('?', $_SERVER['REQUEST_URI']);
	$basepath = $basepath[0];
	$baseurl = SERIA_HTTP_ROOT.$basepath.'?'.http_build_query($baseparams);
?>
	<form method='post'>
		<input type='hidden' id='deleteMark' name='delete' value='0' %XHTML_CLOSE_TAG%>
		<div>
			<h1 class='legend'><?php
				if ($_GET['id'] != 'create')
					echo htmlspecialchars($form->caption());
				else
					echo htmlspecialchars(_t('New RPC Client key'));
			?></h1>
			<?php
				echo $form->output(SERIA_ROOT.'/seria/platform/templates/seria/special/genericInlineForm.php');
			?>
		</div>
		<div>
			<button type='submit'><?php echo htmlspecialchars(_t('Save')); ?></button>
			<?php
				if (SERIA_Base::isAdministrator() && $_GET['id'] != 'create') {
					?>
						<button type='submit' onclick="<?php echo htmlspecialchars('deleteThis();'); ?>"><?php echo htmlspecialchars(_t('Delete')); ?></button>
					<?php
				}
			?>
			<button type='button' onclick="<?php echo htmlspecialchars('location.href = \''.$baseurl.'\';'); ?>"><?php echo htmlspecialchars(_t('Cancel')); ?></button>
		</div>
		<script type='text/javascript'>
			<!--
				function deleteThis()
				{
					document.getElementById('deleteMark').value = 1;
					return true;
				}
			-->
		</script>
	</form>
