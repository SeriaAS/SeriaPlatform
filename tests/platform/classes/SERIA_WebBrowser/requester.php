<?php
	require_once(dirname(__FILE__).'/../../../../main.php');

	class SERIA_WebBrowserDebugger extends SERIA_WebBrowser {
		public function getState()
		{
			ob_start();
			print_r($this);
			return ob_get_clean();
		}
		public function getNextRequest()
		{
			return $this->nextRequest;
		}
	}

	$errors = '';
	if (isset($_POST['browser']) && $_POST['browser']) {
		$browser = unserialize($_POST['browser']);
		if (!$browser)
			echo "ERROR: Unserialize failed<br/>\n";
	} else
		$browser = false;
	if (isset($_POST['url'])) {
		$t_url = $_POST['url'];
		if (!$browser)
			$browser = new SERIA_WebBrowserDebugger();
		$preState = $browser->getState();
		$browser->followRedirect = isset($_POST['followRedirect']) && $_POST['followRedirect'];
		$browser->navigateTo($_POST['url']);
		$request = $browser->getNextRequest();
		try {
			$headers = $browser->fetchHeaders();
			$data = $browser->fetchAll();
		} catch (Exception $e) {
			ob_start();
			?>
			<p>NOTICE: Exception: <?php echo htmlspecialchars($e->getMessage()); ?></p>
			<p>DEBUG MODE:</p>
			<?php
			$errors .= ob_get_clean();
			$data = $browser->_fetchAll();
		}
		$postState = $browser->getState();
	} else {
		$browser = false;
		$t_url = '';
	}
?>
<html>
	<head>
		<title>Request tester for SERIA_WebBrowser</title>
	</head>
	<body>
		<h1>Request tester for SERIA_WebBrowser</h1>
		<form method='post'>
			<div>
				<div><label for='browser_blk'>SERIA_WebBrowser: </label><textarea cols="80" rows="25" id='browser_blk' name='browser'><?php echo htmlspecialchars(serialize($browser)); ?></textarea></div>
				<div><label for='url_i'>Url: </label><input id='url_i' type='text' name='url' value="<?php echo htmlspecialchars($t_url); ?>" %XHTML_CLOSE_TAG%><br %XHTML_CLOSE_TAG%></div>
				<div><label><input type='checkbox' name='followRedirect' value='1' <?php if ($browser === false || $browser->followRedirect) echo 'checked=\'checked\' '; ?>/> Follow redirects.</label></div>
				<button type='submit'>Load</button>
			</div>
		</form>
		<?php echo $errors;?>
<?php
	if ($browser) {
		/* Display browser results */
		?>
		<h2>Request</h2>
		<pre><?php print_r($request); ?></pre>
		<h2>Response</h2>
		<p>This is the request response:</p>
		<h3>Headers</h3>
		<pre><?php print_r($headers); ?></pre>
		<h3>Cookies</h3>
		<pre><?php print_r($browser->cookies); ?></pre>
		<h3>Data</h3>
		<pre><?php echo htmlspecialchars($data); ?></pre>
		<h2>Special</h2>
		<p>Special debugging:</p>
		<?php
			if (isset($_POST['browser'])) {
				?>
					<h3>Intake serialized</h3>
					<pre><?php echo htmlspecialchars($_POST['browser']); ?></pre>
				<?php
			}
		?>
		<h3>Pre-state</h3>
		<pre><?php echo htmlspecialchars($preState); ?></pre>
		<h3>Post-state</h3>
		<pre><?php echo htmlspecialchars($postState); ?></pre>
		<?php
	}
?>
	</body>
</html>