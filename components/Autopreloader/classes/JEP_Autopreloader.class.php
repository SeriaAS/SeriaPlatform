<?php

class JEP_Autopreloader
{
	protected static $loadList = array();
	protected static $loadOrder = array();
	protected static $queue;
	protected static $queueTask;
	protected static $threshold = 80;

	public static function autoloaded($class, $path)
	{
		self::$loadList[$class] = $path;
		self::$loadOrder[] = $class;
	}
	public static function addStatistics($loadList)
	{
		if ($loadList === false)
			return;
		$dataFile = SERIA_TMP_ROOT.'/jep_autopreloader_data.php';
		if (file_exists($dataFile))
			require($dataFile);
		if (count($autoloadStatistics) > 50)
			array_shift($autoloadStatistics);
		$autoloadStatistics[] = $loadList;

		/*
		 * Calculate load percentages..
		 */
		$totalRuns = count($autoloadStatistics);
		$classCounts = array();
		foreach ($autoloadStatistics as $run) {
			foreach ($run as $className => $filePath) {
				if (!isset($classCounts[$className]))
					$classCounts[$className] = array(
						'count' => 0
					);
				$classCounts[$className]['path'] = $filePath;
				$classCounts[$className]['count']++;
			}
		}
		foreach ($classCounts as &$counts) {
			$counts['percent'] = floor(100 * $counts['count'] / $totalRuns);
		}
		unset($counts);

		file_put_contents($dataFile, '<?php $autoloadStatistics = '.var_export($autoloadStatistics, true).'; $autoloadCounts = '.var_export($classCounts, true).';');
	}

	public static function getStatistics()
	{
		$dataFile = SERIA_TMP_ROOT.'/jep_autopreloader_data.php';
		if (file_exists($dataFile)) {
			include($dataFile);
			return $autoloadCounts;
		}
		return false;
	}

	public static function generatePreload()
	{
		$dataFile = SERIA_TMP_ROOT.'/jep_autopreloader_data.php';
		$preloadFile = SERIA_TMP_ROOT.'/jep_autopreloader_preload.php';
		if (file_exists($dataFile)) {
			include($dataFile);
			$preload = array();
			$preloadUnordered = array();
			foreach ($autoloadCounts as $className => $params) {
				/* We generate a load order by actually loading the classes */
				if ($params['percent'] > self::$threshold && class_exists($className))
					$preloadUnordered[] = $className;
			}
			if ($preloadUnordered) {
				$remaining = self::$loadOrder;
				while (($className = array_shift($remaining))) {
					if (in_array($className, $preloadUnordered)) {
						SERIA_Base::debug($className.' is loaded because of usage percentage ('.$autoloadCounts[$className]['percent'].'% usage).');
						$preload[] = var_export($autoloadCounts[$className]['path'], true);
					} else if (isset($autoloadCounts[$className])) {
						foreach ($preloadUnordered as $wClass) {
							if (is_subclass_of($wClass, $className) || in_array($className, class_implements($wClass))) {
								SERIA_Base::debug($className.' ('.$autoloadCounts[$className]['percent'].'% usage) is required by '.$wClass.' ('.$autoloadCounts[$wClass]['percent'].'% usage).');
								$preload[] = var_export($autoloadCounts[$className]['path'], true);
								break;
							}
						}
					}
				}
			}
			if ($preload) {
				$content = "<?php\ninclude_once(".array_shift($preload);
				if ($preload) {
					$content .= ");\ninclude_once(".implode(");\ninclude_once(", $preload);
				}
				$content .= ");\n";
				if (file_exists($preloadFile))
					unlink($preloadFile);
				file_put_contents($preloadFile, $content);
			}
		}
	}
	public static function completed()
	{
		SERIA_Async::call(array('JEP_Autopreloader', 'addStatistics'), self::$loadList);
	}
	public static function eavesdrop()
	{
		self::$threshold = JEP_AUTOPRELOADER_THRESHOLD;
		SERIA_Hooks::listen('seria_autoload', array('JEP_Autopreloader', 'autoloaded'));
		SERIA_Hooks::listen('seria_maintain_1_hour', array('JEP_Autopreloader', 'generatePreload'));
	}
	public static function collectStatistics()
	{
		self::$threshold = JEP_AUTOPRELOADER_THRESHOLD;
		SERIA_Hooks::listen('SERIA_Template::outputHandler', array('JEP_Autopreloader', 'completed'));
		/* Hack to load the neccesary classes and objects outside the output handler */
		SERIA_Async::call(array('JEP_Autopreloader', 'addStatistics'), false);
	}

	public function guiEmbed($gui)
	{
		$gui->addMenuItem('controlpanel/autopreloader', _t('Autopreloader'), _t('Autopreloader automatically collects statistics from the autoloader and preloads frequent classes.'), SERIA_HTTP_ROOT.'/seria/components/Autopreloader/pages/index.php', SERIA_HTTP_ROOT.'/seria/components/Autopreloader/icon.png', 100);
		$gui->addMenuItem('controlpanel/autopreloader/statistics', _t('Statistics'), _t('View autoload statistics.'), SERIA_HTTP_ROOT.'/seria/components/Autopreloader/pages/statistics.php', SERIA_HTTP_ROOT.'/seria/components/Autopreloader/icon.png', 100);
	}
}
