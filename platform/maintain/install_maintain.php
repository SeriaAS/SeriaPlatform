<?php

	function install_maintain()
	{
		ob_start();

		$updatesRun = 0;
		$systemUpdatefiles = glob(SERIA_ROOT . "/seria/platform/install/update-*.php");
		
		//TODO: This should be a configuration variable
		$applicationUpdatefiles = glob(SERIA_ROOT . "/install/update-*.php");
		
		if (!is_array($systemUpdatefiles)) {
			$systemUpdatefiles = array();
		}
		if (!is_array($applicationUpdatefiles)) {
			$applicationUpdatefiles = array();
		}
		
		sort($systemUpdatefiles); // sorted by id
		sort($applicationUpdatefiles);
		
		$fileTable = array($systemUpdatefiles, $applicationUpdatefiles);
		
		$lastRunSysIndex = $keep = intval(SERIA_Base::getParam("install_maintain_index"));
		$lastRunAppIndex = $keep = intval(SERIA_Base::getParam("install_maintain_application_index"));
		foreach ($fileTable as $tableId => $table) {
			foreach($table as $file)
			{
				$basename = basename($file);
				$index = intval(substr($basename, 7));
				
				if ($tableId == 0) {
					$lastRunIndex = $lastRunSysIndex;
				} elseif ($tableId == 1) {
					$lastRunIndex = $lastRunAppIndex;
				}
				
				if($index>$lastRunIndex)
				{
					$updatesRun++;
					echo "Running $file: ";
					try
					{
						$result = include($file);
						
						if ($result !== false) {
							echo "OK<br>\n";
							$lastRunIndex = $index;
							
							// Set lastrunindex in database to prevent loss of index if future update fails
							if ($tableId == 0) {
								SERIA_Base::setParam("install_maintain_index", $lastRunIndex);
								$lastRunSysIndex = $lastRunIndex;
							} elseif ($tableId == 1) {
								SERIA_Base::setParam("install_maintain_application_index", $lastRunIndex);
								$lastRunAppIndex = $lastRunIndex;
							}
						} else {
							echo 'Error<br />' . "\n";
							break 2;
						}
					}
					catch (Exception $e)
					{
						echo "Error: ".$e->getMessage()."<br>\n";
						break 2;
					}
				}
			}
		}

		$start = ob_get_contents();
		ob_end_clean();

		if($updatesRun)
			return $start."<br>".$updatesRun." updates\n";
		return $start;
	}
