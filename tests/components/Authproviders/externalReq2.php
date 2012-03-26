<?php
	require(dirname(__FILE__).'/../../../main.php');
?><!DOCTYPE html>
<?php
	if (isset($_POST['url'])) {
		$loginUrl = $_POST['url'].'seria/components/Authproviders/pages/externalReq2.php';
		$returnUrl = SERIA_HTTP_ROOT.'seria/tests/components/Authproviders/externalReq2/returned.php';
		$data = serialize(array(
			'authBaseUrl' => $_POST['url']
		));
		?>
			<title>Acknowledge authentication test</title>
			<h1>Acknowledge authentication test</h1>
			<p>Would you like to proceed requesting authentication from <?php echo htmlspecialchars($loginUrl); ?>?</p>
			<form method='post' action="<?php echo htmlspecialchars($loginUrl); ?>">
				<input type='hidden' name='returnData' value="<?php echo htmlspecialchars($data); ?>" />
				<input type='hidden' name='returnUrl' value="<?php echo htmlspecialchars($returnUrl); ?>" />
				<div>
					<input type='submit' value='Test authentication' />
				</div>
			</form>
		<?php
		return;
	}
?><title>Test externalReq2</title>
<form method='post'>
	<div>
		<label>Url to auth-server: <input type='text' name='url' value="<?php echo htmlspecialchars(SERIA_HTTP_ROOT); ?>" /></label>
	</div>
	<div>
		<input type='submit' value='Test' />
	</div>
</form>