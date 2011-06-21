<!DOCTYPE HTML>
<title>Passthrough action</title>
<?php
	if (!isset($_GET['url']) || !isset($_GET['name']) || !isset($_GET['data']))
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT);
	$url = $_GET['url'];
	$name = $_GET['name'];
	$data = $_GET['data'];
	if (isset($_GET['state']))
		$state = $_GET['state'];
	else
		$state = null;
?>
<form method="post" action="{{$url|htmlspecialchars}}">
	<input type='hidden' name="{{$name|htmlspecialchars}}" value="{{$data|htmlspecialchars}}" />
	<?php
		if ($state !== null) {
			?>
				<input type='hidden' name="<?php echo htmlspecialchars($name.'-s'); ?>" value="{{$state|htmlspecialchars}}" />
			<?php
		}
	?>
	<div>
		<input id='submitb' type='submit' value='Continue' />
		<span id='msg'></span>
	</div>
</form>
<script type='text/javascript'>
	<!--
		document.getElementById('submitb').style.display = 'none';
		document.getElementById('msg').innerHTML = <?php echo SERIA_Lib::toJSON(_t('Please wait...')); ?>;
		document.getElementById('submitb').form.submit();
	-->
</script>
