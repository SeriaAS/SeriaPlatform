<?php

require_once(dirname(__FILE__).'/../../../../main.php');

SERIA_Base::pageRequires('admin');

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>File meta test</title>
	</head>
	<body>
		<?php
			if (isset($_GET['id'])) {
				$file = SERIA_File::createObject($_GET['id']);
				if (sizeof($_POST)) {
					if (isset($_POST['name']) && $_POST['name'] && isset($_POST['value'])) {
						$file->setMeta($_POST['name'], $_POST['value']);
						SERIA_Base::redirectTo(SERIA_Url::current());
						die();
					}
				}
				$q = SERIA_Base::db()->query('SELECT * FROM {file_meta} WHERE file_id = :file_id', array('file_id' => $_GET['id']))->fetchAll(PDO::FETCH_ASSOC);
				if (isset($q[0])) {
					$f = $q[0];
					$cols = array_keys($f);
					?>
						<table class='grid'>
							<thead>
								<tr>
									<?php
										foreach ($cols as $name) {
											?>
												<th><?php echo $name; ?></th>
											<?php
										}
									?>
									<th>Read-test</th>
								</tr>
							</thead>
							<tbody>
					<?php
					foreach ($q as $r) {
						?>
							<tr>
								<?php
									foreach ($cols as $name) {
										?>
											<td><?php echo $r[$name]; ?></td>
										<?php
									}
								?>
								<td><?php echo $file->getMeta($r['key']); ?></td>
							</tr>
						<?php
					}
					?>
							</tbody>
						</table>
						<form method='post'>
							<table>
								<tfoot>
									<tr>
										<td colspan='2'><input type='submit' value='Set'></td>
									</tr>
								</tfoot>
								<tbody>
									<tr>
										<th><label for='name'>Name: </label></th>
										<td><input type='text' id='name' name='name' value=''></td>
									</tr>
									<tr>
										<th><label for='value'>Value: </label></th>
										<td><input type='text' id='value' name='value' value=''></td>
									</tr>
								</tbody>
							</table>
						</form>
					<?php
				}
			} else {
				$q = SERIA_Base::db()->query('SELECT id,filename FROM {files} WHERE parent_file = 0')->fetchAll(PDO::FETCH_ASSOC);
				?>
					<ul>
				<?php
				foreach ($q as $f) {
					?>
						<li>
							<a href="<?php echo htmlspecialchars(SERIA_Url::current()->setParam('id', $f['id'])->__toString()); ?>"><?php echo $f['filename']; ?></a>
						</li>
					<?php
				}
				?>
					</ul>
				<?php
			}
		?>
	</body>
</html>