<?php
	require_once(dirname(__FILE__).'/../../../../main.php');

	set_time_limit(240);
?><html>
	<head>
	</head>
	<body>
<?php
	$starttime = microtime(true);
	if (sizeof($_POST)) {
		$all = new SERIA_WebBrowsers();
		$all->setTimeout(0);
		for ($i = 0; $i < 10; $i++) {
			if ($_POST['url'.$i]) {
				$url = $_POST['url'.$i];
				$webbrowser = new SERIA_WebBrowser();
				$webbrowser->navigateTo($url);
				$all->addWebBrowser($webbrowser);
				unset($webbrowser);
			}
		}
		$result = $all->fetchAll();
		?>
		<table>
			<tr>
				<th>URL</th>
				<th>Time</th>
				<th>Size</th>
			</tr>
		<?php
		$data = array();
		foreach ($result as $res) {
			$data[] = array(
				'url' => $res['webbrowser']->url,
				'headers' => $res['webbrowser']->responseHeaders,
				'data' => $res['data']
			);
			if ($res['data'] !== false) {
				$reltime = floor(($res['completedAt'] - $starttime) * 1000);
				?>
					<tr>
						<td><?php echo htmlspecialchars($res['webbrowser']->url); ?></td>
						<td><?php echo htmlspecialchars($reltime); ?></td>
						<td><?php echo htmlspecialchars(strlen($res['data'])); ?></td>
					</tr>
				<?php
			} else {
				?>
					<tr>
						<td><?php echo htmlspecialchars($res['webbrowser']->url); ?></td>
						<td>Timeout</td>
						<td><?php echo htmlspecialchars(strlen($res['partial'])); ?></td>
					</tr>
				<?php
			}
		}
		?>
		</table>
		<?php
		ob_start();
		print_r($data);
		$datastr = ob_get_clean();
		?>
		<div><?php echo htmlspecialchars($datastr); ?></div>
		<?php
	}
?>
		<form method='post'>
			<div>
				<h1>Requests</h1>
				<input type='text' name='url0' value='' %XHTML_CLOSE_TAG%>
				<input type='text' name='url1' value='' %XHTML_CLOSE_TAG%>
				<input type='text' name='url2' value='' %XHTML_CLOSE_TAG%>
				<input type='text' name='url3' value='' %XHTML_CLOSE_TAG%>
				<input type='text' name='url4' value='' %XHTML_CLOSE_TAG%>
				<input type='text' name='url5' value='' %XHTML_CLOSE_TAG%>
				<input type='text' name='url6' value='' %XHTML_CLOSE_TAG%>
				<input type='text' name='url7' value='' %XHTML_CLOSE_TAG%>
				<input type='text' name='url8' value='' %XHTML_CLOSE_TAG%>
				<input type='text' name='url9' value='' %XHTML_CLOSE_TAG%>
				<button type='submit'>Go ahead!</button>
			</div>
		</form>
	</body>
</html>