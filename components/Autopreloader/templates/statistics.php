<?php

/*
 * Sorting the easiest way thought of...
 */
$byPercentage = array();
for ($i = 0; $i <= 100; $i++) {
	$byPercentage[$i] = array();
}

foreach ($statistics as $name => $class) {
	$class['name'] = $name;
	$byPercentage[$class['percent']][] = $class;
}

$sortPercentage = array();
foreach ($byPercentage as $group) {
	foreach ($group as $class)
		$sortPercentage[] = $class;
}

$sortPercentage = array_reverse($sortPercentage);

?>
<table class='grid'>
	<thead>
		<tr>
			<th><?php echo htmlspecialchars(_t('Class name')); ?></th>
			<th><?php echo htmlspecialchars(_t('Load count')); ?></th>
			<th><?php echo htmlspecialchars(_t('Load percentage')); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
			foreach ($sortPercentage as $class) {
				?>
					<tr>
						<td><?php echo htmlspecialchars($class['name']); ?></td>
						<td><?php echo htmlspecialchars($class['count']); ?></td>
						<td><?php echo htmlspecialchars($class['percent']); ?></td>
					</tr>
				<?php
			}
		?>
	</tbody>
</table>