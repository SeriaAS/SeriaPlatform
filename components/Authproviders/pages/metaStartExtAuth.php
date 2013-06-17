<?php
	/*
	 * Get the compressed data:
	 */
	$compressed = '';
	$partNum = 0;
	while (isset($_GET['c'.$partNum])) {
		$part = $_GET['c'.$partNum];
		$compressed .= $part;
		$partNum++;
	}
	$params = json_decode(gzuncompress(base64_decode($compressed)), true);
	if (!isset($params['e']) || !isset($params['u'])) {
		/* OOps */
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT);
	}
	$url = $params['u'];
	if ($url[0] == '/')
		$url = SERIA_HTTP_ROOT.$url;
	$url = new SERIA_Url($url);
	if ($url->getParam('auth_abort'))
		$abort = $url->getParam('auth_abort');
	else
		$abort = SERIA_HTTP_ROOT;
	if (isset($params['i']))
		$interactive = $params['i'];
	else
		$interactive = true;
	if (isset($params['g']))
		$guest = $params['g'];
	else
		$guest = true;
	SERIA_Authproviders::loadProviders('SERIA_ExternalAuthprovider');
	$provider = SERIA_ExternalAuthprovider::getProviderByHostname($params['e']);
	$form = $provider->getExternalReq2RequestForm($interactive, $guest, $url->__toString(), $abort);
?><!DOCTYPE html>
<form method='post' action="<?php echo htmlspecialchars($form['url']); ?>">
	<?php
		foreach ($form['data'] as $name => $value) {
			?>
			<input type='hidden' name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>" />
			<?php
		}
	?>
	<div>
		<input id='submitb' type='submit' value='Continue' />
		<span id='msg'></span>
	</div>
</form>
<script>
	<!--
		document.getElementById('submitb').style.display = 'none';
		document.getElementById('msg').innerHTML = 'Please wait...';
		document.getElementById('submitb').form.submit();
	-->
</script>