<?php
	require_once(dirname(__FILE__)."/../common.php");
	require('common.php');
	$gui->setActiveTopMenu("status");
	$gui->exitButton(_t("< Main Admin"), "location.href='./../'");
	SERIA_Base::pageRequires("admin");
	
	$pageCache = new SERIA_PageCache('systemstatus', 30);
	if ($pageCache->start()) {
		$maintainLastRun = SERIA_Base::getParam('maintain_last_run');
		if ($maintainLastRun <= (time() - (10 * 60))) {
			$maintainLastRunClass = 'statusError';
		} else {
			$maintainLastRunClass = 'statusOk';
		}
		
		$cache = new SERIA_Cache('status');
		$cache->get('testing');
		if ($cache->highPerformance) {
			$cacheStatus = 'Using memory/high performance cache';
			$cacheStatusClass = 'statusOk';
		} else {
			$cacheStatus = 'Using filesystem fallback cache';
			$cacheStatusClass = 'statusError';
		}
		if (defined('SERIA_NOCACHE') && SERIA_NOCACHE) {
			$cacheStatus = 'Cache disabled';
			$cacheStatusClass = 'statusError';
		}
		
		if (is_writeable(SERIA_TMP_ROOT)) {
			$tmpDirStatus = 'Writeable (' . htmlspecialchars(realpath(SERIA_TMP_ROOT)) . ')';
			$tmpDirStatusClass = 'statusOk';
		} else {
			$tmpDirStatus = 'Unwriteable (' . htmlspecialchars(realpath(SERIA_TMP_ROOT)) . ')';
			$tmpDirStatusClass = 'statusError';
		}
		
		$query = 'SELECT (SUM(filesize) / 1024 / 1024), COUNT(*) FROM ' . SERIA_PREFIX . '_files';
		list($usage) = SERIA_Base::db()->query($query)->fetchAll(PDO::FETCH_NUM);
		list($usage, $filecount) = $usage;

		$fileDirectoryUsage = _t('%USAGE% MB in %FILES% files', array('USAGE' => round($usage, 2), 'FILES' => $filecount));
?>

	<h1 class="legend"><?php echo _t('Status'); ?></h1>
	
	<table class="grid">
		<thead>
			<tr>
				<th class="tableMaxWidth"><?php echo _('Description'); ?></th>
				<th><?php echo _t('Status'); ?></th>
			</tr> 
		</thead>
		<tbody>
			<tr>
				<td><?php echo _t('Maintain last run: '); ?></td>
				<td class="<?php echo $maintainLastRunClass; ?> nobr"><?php echo _datetime($maintainLastRun); ?></td>
			</tr>
			<tr>
				<td><?php echo _t('Cache status: '); ?></td>
				<td class="<?php echo $cacheStatusClass; ?> nobr"><?php echo $cacheStatus; ?></td>
			</tr>
			<tr>
				<td><?php echo _t('File directory usage: '); ?></td>
				<td class="nobr"><?php echo $fileDirectoryUsage; ?></td>
			</tr>
			<tr>
				<td><?php echo _t('Temp directory status: '); ?></td>
				<td class="<?php echo $tmpDirStatusClass; ?> nobr"><?php echo $tmpDirStatus; ?></td>
			</tr>
		</tbody>
	</table>
	
	<p>
		<?php echo _t('Last update %DATETIME%', array('DATETIME' => _datetime(time()))); ?>
	</p>
<?php
	}
	$gui->contents($pageCache->end());
	$gui->output();
?>