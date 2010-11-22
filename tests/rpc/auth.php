<?php require_once(dirname(__FILE__).'/../../main.php'); ?><html>
	<head>
		<title>RPC Auth Test</title>
	</head>
	<body>
		<h1>RPC Auth Test</h1>
<?php
	require_once(dirname(__FILE__).'/../../main.php');

	class SERIA_WebBrowserDebugger extends SERIA_WebBrowser {
		protected $debugBin = '';

		public function getState()
		{
			ob_start();
			print_r($this);
			return ob_get_clean();
		}
		public function navigateTo($url, $post=false, $ip=false)
		{
			$retv = parent::navigateTo($url, $post, $ip);
			?>
			<h2>HTTP request</h2>
			<table>
				<tr>
					<th>URL:</th>
					<td><?php echo htmlspecialchars($url); ?></td>
				</tr>
			</table>
			<?php
			$this->debugBin = '';
			return $retv;
		}
		protected function send()
		{
			$retv = parent::send();
			?>
			<h3>Cookies</h3>
			<pre><?php
			ob_start();
			print_r($this->cookies);
			echo htmlspecialchars(ob_get_clean());
			?></pre>
			<h3>Request headers:</h3>
			<pre><?php
			ob_start();
			print_r($this->requestHeaders);
			echo htmlspecialchars(ob_get_clean());
			?></pre>
			<?php
			return $retv;
		}
		public function fetch($bytes=4096,$dontWaitForMore=false)
		{
			$buf = parent::fetch($bytes, $dontWaitForMore);
			if ($buf !== false) {
				if (!$this->debugBin) {
					?>
					<h3>Response headers</h3>
					<pre><?php
					ob_start();
					print_r($this->responseHeaders);
					echo htmlspecialchars(ob_get_clean());
					?></pre>
					<?php
				}
				$this->debugBin .= $buf;
			} else if ($this->debugBin) {
				?>
				<h3>Response data</h3>
				<pre><?php echo htmlspecialchars($this->debugBin); ?></pre>
				<?php
				$this->debugBin = '';
			}
			return $buf;
		}
	}
	class SERIA_RPCClientDebugger extends SERIA_RPCClient {
		protected function __construct()
		{
			parent::__construct();
			$this->browser = new SERIA_WebBrowserDebugger();
		}
		protected function do__call($function, $args, $auth_attempted=false)
		{
			?>
				<h2>Call browser pre-state</h2>
				<pre><?php echo htmlspecialchars($this->browser->getState()); ?></pre>
			<?php
			$retv = parent::do__call($function, $args, $auth_attempted);
			?>
				<h2>Call browser post-state</h2>
				<pre><?php echo htmlspecialchars($this->browser->getState()); ?></pre>
			<?php
			return $retv;
		}
		public static function connect($serviceName, $className)
		{
			$obj = new self();
			$obj->connectToService($serviceName, $className);
			return $obj;
		}
	}
	if (isset($_POST['Class'])) {
		try {
			$rpc = SERIA_RPCClientDebugger::connect($_POST['Class'], 'SERIA_RPCHost');
			if ($rpc !== null) {
				if (!$rpc->forceAuthentication())
					die('Did not auth');
				echo $rpc->hello(SERIA_HTTP_ROOT)."<br/>\n";
				echo $rpc->hello(SERIA_HTTP_ROOT)."<br/>\n";
				echo $rpc->hello(SERIA_HTTP_ROOT)."<br/>\n";
			}
		} catch (Exception $e) {
			?>
			<h2>RPC system failure</h2>
			<p>Breaking the algorithm at this point.</p>
			<p><?php echo htmlspecialchars($e->getMessage().' ('.$e->getFile().':'.$e->getLine().')'); ?></p>
			<pre><?php echo htmlspecialchars($e->getTraceAsString()); ?></pre>
			<?php
		}
	}
?>
		<form method='POST'>
			<input type='text' name='Class' %XHTML_CLOSE_TAG%>
			<button type='submit'>Test</button>
		</form>
	</body>
</html>