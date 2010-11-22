<?php

set_time_limit(0);

require_once(dirname(__FILE__).'/../../main.php');

SERIA_Base::pageRequires('login');
if (!SERIA_Base::isAdministrator())
	die();

if (isset($_GET['asynchronous']) && $_GET['asynchronous']) {
	SERIA_Template::override('text/plain', 'Hello world!');
	sleep(60);
}

/* Do something noticeable? */
if (isset($_GET['action'])) {
	switch ($_GET['action']) {
		case 'word':
			$np = new COM('Word.Application');
			$np->Visible = true;
			$np->Documents->Add();
			sleep(20);
			$np->Quit();
			$np = null;
			break;
		case 'loop':
			$i = 100000000;
			while ($i > 0)
				$i--;
			break;
	}
}

?><html>
	<head>
		<title>HTTP asynchronous test</title>
	</head>
	<body>
		<form method='get'>
			<div>
				<input id='iasync' name='asynchronous' value='1' type='checkbox' %XHTML_CLOSE_TAG%>
				<label for='iasync'>Asynchronous HTTP connection</label>
			</div>
			<div>
				<label for='iaction'>Action:</label>
				<select id='iaction' name='action'>
					<option value='loop'>While loop</option>
					<option value='word'>Open MSWord on server</option>
				</select>
			</div>
			<div>
				<button type='submit'>Submit</button>
			</div>
		</form>
	</body>
</html>