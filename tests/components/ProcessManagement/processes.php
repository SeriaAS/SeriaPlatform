<?php

require_once(dirname(__FILE__).'/../../../main.php');

SERIA_Base::pageRequires('admin');

if (!defined('SERIA_DEBUG') || !SERIA_DEBUG)
	throw new SERIA_Exception('Enable SERIA_DEBUG to test process list/kill. (Blocked)');

$component = SERIA_Components::getComponent('process_management_component');

$processes = $component->getWin32ProcessList();

if (isset($_GET['kill'])) {
	$process = $processes->getProcess($_GET['kill']);
	$process->kill(isset($_GET['force']) && $_GET['force']);
	SERIA_Base::redirectTo(SERIA_Url::current()->unsetParam('force')->unsetParam('kill')->unsetParam('timestamp')->__toString());
	die();
}

if (isset($_GET['name']) && $_GET['name'])
	$processes = $processes->getProcessesByName($_GET['name']);
else
	$processes = $processes->toArray();

?><!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
	<head>
		<title>Test proccess management</title>
	</head>
	<body>
		<form method='get'>
			<table border='1'>
				<thead>
					<tr>
						<th>Process name</th>
						<th>PID</th>
						<th>Kill</th>
					</tr>
				</thead>
				<tbody>
					<?php
						foreach ($processes as $process) {
							?>
								<tr>
									<td><?php echo $process->get('processName'); ?></td>
									<td><?php echo $process->get('pid'); ?></td>
									<td>
										<input type='button' onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON(SERIA_Url::current()->setParam('timestamp', time())->setParam('kill', $process->get('pid'))->__toString())); ?>;" value='Kill'>
										<input type='button' onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON(SERIA_Url::current()->setParam('timestamp', time())->setParam('kill', $process->get('pid'))->setParam('force', '1')->__toString())); ?>;" value='Forced kill'>
									</td>
								</tr>
							<?php
						}
					?>
				</tbody>
			</table>
			<table>
				<tfoot>
					<tr>
						<td colspan='2'>
							<input type='submit' value='Query'>
						</td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<th><label for='name'>Process name: </label></th>
						<td><input id='name' type='text' name='name' value=''></td>
					</tr>
				</tbody>
			</table>
		</form>
	</body>
</html>