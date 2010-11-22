<html>
	<head>
		<title><?php echo htmlspecialchars($title); ?></title>
	</head>
	<body>
		<h1><?php echo htmlspecialchars($title); ?></h1>
		<p><?php echo htmlspecialchars(_t('This is the list of participants for this event. The message was generated automatically by the system at %HOST%, please don\'t reply to this message.', array('HOST' => $host))); ?></p>
		<table>
			<thead>
				<tr>
					<th><?php echo htmlspecialchars(_t('Name')); ?></th>
					<th><?php echo htmlspecialchars(_t('Company')); ?></th>
				</tr>
			</thead>
			<tbody>
					<?php
						foreach ($participants as $values) {
							?>
								<tr>
									<td><?php echo htmlspecialchars($values['name']); ?></td>
									<td><?php echo htmlspecialchars($values['company']); ?></td>
								</tr>
							<?php
						}
					?>
			</tbody>
		</table>
	</body>
</html>
