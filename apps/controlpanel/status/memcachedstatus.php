<?php
	$pageCache = new SERIA_PageCache('memcached_status' . $server->id, 10);
	if ($pageCache->start()) {
		if ($server) {
			$socket = fsockopen($server->address, $server->port, $null1, $null2, 2);
			if ($socket) {
				fputs($socket, 'stats' . "\n");
				
				$stats = array();
				do {
					$data = fgets($socket);
					list($null, $command, $value) = explode(' ', trim(preg_replace("/\s+/", ' ', $data)));
					if ($command) {
						$stats[$command] = $value;
					}
				} while (trim($data) != 'END');
				
				fclose($socket);
			}
		}
		
		$cacheHitRate = '';
		if ($stats) {
			if (!$stats['get_hits']) {
				$cacheHitRate = 0;
			} else {
				$cacheHitRate = round($stats['get_hits'] * 100 / $stats['cmd_get']);
			}
			
			$cacheHitRate .= '%';
		}
?>

	<?php if ($stats) { ?>
		<table class="grid">
			<thead>
				<tr>
					<th class="tableMaxWidth">'._t('Description').'</th>
					<th>'._t('Status').'</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="nobr"><?php echo _t('Maximum memory usage: '); ?></td>
					<td class="statusRow"><?php echo SERIA_Format::filesize($stats['limit_maxbytes']); ?></td>
				</tr>
				<tr>
					<td class="nobr"><?php echo _t('Current memory usage: '); ?></td>
					<td class="statusRow"><?php echo SERIA_Format::filesize($stats['bytes']); ?></td>
				</tr>
				
				<tr>
					<td class="nobr"><?php echo _t('Current item count: '); ?></td>
					<td class="statusRow"><?php echo $stats['curr_items']; ?></td>
				</tr>
				
				<tr>
					<td class="nobr"><?php echo _t('Cache hit rate: '); ?></td>
					<td class="statusRow"><?php echo $cacheHitRate; ?></td>
				</tr>
			</tbody>
		</table>
	<?php } ?>
<?php
	}
	echo $pageCache->end();
?>
