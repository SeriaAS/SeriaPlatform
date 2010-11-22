<?php
	class SERIA_Installer {
		protected $updateDirectories;

		public function __construct() {
			$this->updateDirectories = array(
									// The paths is used as keys. Add, but DO NOT EDIT EXISTING!!
				SERIA_ROOT.'/seria/platform/install/',  // the backslash at the end must stay!
				SERIA_ROOT.'/install/',			// the backslash at the end must stay! If not, sites will be destroyed
			);
		}

		public function runUpdates() {
			foreach ($this->updateDirectories as $updateDirectory) {
				$basePath = $updateDirectory;
				if (file_exists($basePath) && is_dir($basePath)) {
					if ($dirhandler = opendir($basePath)) {
						while ($directory = readdir($dirhandler)) {
							if ($directory[0] != '.') {
								if (is_dir($basePath . '/' . $directory)) {
									$fullPath = $basePath . '/' . $directory;
									$directories[$directory] = $fullPath;
								}
							}
						}
						closedir($dirhandler);

						ksort($directories);
						foreach ($directories as $directory => $fullPath) {
							$this->runUpdatesFromDirectory($fullPath);
						}
					} else {
						SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::ERROR, _t('Directory exists, but unable to open install directory %PATH%', array('PATH' => $basePath)), 'system');
					}
				}
			}

			$this->runApplicationUpdates();
			$this->runComponentUpdates();
			// TODO: Move this out of the central maintain script. Widgets are part of Seria Publisher, not Seria Platform!
			if(SERIA_Applications::getApplication('seria_publisher'))
				$this->runWidgetUpdates();
		}

		public function runApplicationUpdates() {
			$apps = glob(SERIA_ROOT.'/seria/apps/'.'*', GLOB_ONLYDIR);
			//$apps = SERIA_Applications::getApplications();
			foreach($apps as $app)
			{ 
				$path = $app.'/install';
//				$path = $app->getInstallationPath().'/install';
				if(file_exists($path) && is_dir($path))
				{
					SERIA_Base::debug("<strong>Application updates for ".basename($path)."</strong>");
					$this->runUpdatesFromDirectory($path);
					SERIA_Base::debug("<strong>Finishing updates for ".basename($path)."</strong>");
				}
				else
				{
					SERIA_Base::debug("<strong>No application install folder for ".basename($path).". Expected at ".$path."</strong>");
				}
			}
		}
		public function runComponentUpdates() {
			$comps = SERIA_Components::getComponents();
			foreach($comps as $comp)
			{ 
				$path = $comp->getInstallationPath().'/install';
				if(file_exists($path) && is_dir($path))
				{
					SERIA_Base::debug("<strong>Component updates for ".$comp->getName()."</strong>");
					$this->runUpdatesFromDirectory($path);
					SERIA_Base::debug("<strong>Finishing updates for ".$comp->getName()."</strong>");
				}
				else
				{
					SERIA_Base::debug("<strong>No component install folder for ".$comp->getName().". Expected at ".$path."</strong>");
				}
			}
		}

		public function runWidgetUpdates() {
			
			// Find all widgets in use
			$widgetsInUse = array();
			$widgets = SERIA_WidgetRecords::find_all();
			foreach ($widgets as $widget) {
				if (!in_array($widget->type, $widgetsInUse)) {
					$widgetsInUse[] = $widget->type;
				}
			}
			
			$widgetSearchPaths = array('/widgets/', '/seria/platform/widgets/');
			
			foreach ($widgetsInUse as $widgetName) {
				if ($widgetName) {
					if (preg_match('/^[a-zA-Z0-9_-]+$/', $widgetName)) {
						
						$widgetPath = '';
						// Find install directory for this widget
						foreach ($widgetSearchPaths as $searchPath) {
							$path = $searchPath . $widgetName;
							if (is_dir(SERIA_ROOT . $path)) {
								$widgetPath = $path;
								break;
							}
						}
						
						if ($widgetPath) {
							$installPath = $widgetPath . '/install/';
							if (is_dir(SERIA_ROOT . $installPath)) {
								SERIA_Base::debug('Running install files from widget: ' . $widgetPath);
								$this->runUpdatesFromDirectory(SERIA_ROOT.'/'.$installPath);
							}
						}
					}
				}
			}
		}
		
		protected function runUpdatesFromDirectory($fullPath) {
			$path = str_replace(SERIA_ROOT,'',$fullPath);

			if (file_exists($fullPath) && is_dir($fullPath)) {
				$files = array();
				$dirhandler = opendir($fullPath);
				while ($file = readdir($dirhandler)) {
					if (is_file($fullPath . '/' . $file)) {
						list($id) = explode('_', $file, 2);
						$files[$id] = $file;
					}
				}
				
				ksort($files);

				/*
				 * To be able to dump a database and transfer from Windows to UNIX and vice versa
				 * backslashes should not be allowed in the install directory identifier.
				 */
				$compatPath = $path;
				$path = str_replace('\\', '/', $path);

				if (strlen($path) > 100) {
					$dbVersionKey = md5($path);
					if ($compatPath != $path)
						$compatMd5 = md5($compatPath);
					else
						$compatMd5 = false;
					$md5id = true;
				} else {
					$dbVersionKey = $path;
					$md5id = false;
					$compatMd5 = false;
				}
				
				$db = SERIA_Base::db();
				
				$count = 1;
				do {
					$query = null;
					try {
						if ($dbVersionKey[0] == '/') {
							/*
							 * Convert from old without leading slash.
							 */
							$vkey = substr($dbVersionKey, 1);
							$db->exec('UPDATE {dbversion} SET `key` = :new WHERE `key` = :old', array('old' => $vkey, 'new' => $dbVersionKey));
						}
						$query = $db->query('SELECT version FROM {dbversion} WHERE `key` = :key', array('key'=>$dbVersionKey))->fetchAll(PDO::FETCH_NUM);
					} catch (Exception $exception) {
						if ($count == 2) {
							throw $exception;
						}
						$db->exec('CREATE TABLE IF NOT EXISTS ' . SERIA_PREFIX . '_dbversion (
							id MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
							`key` VARCHAR(255) NOT NULL,
							path TEXT NOT NULL,
							version MEDIUMINT UNSIGNED NOT NULL
						)');
					}
				} while ($count++ < 2 && !$query);
				if (sizeof($query) == 0) {
					/*
					 * Backslash to slash convert is required if the key is md5.
					 */
					if ($compatMd5) {
						if ($db->exec('UPDATE {dbversion} SET `key` = :new WHERE `key` = :key', array('key'=>$compatMd5, 'new'=>$dbVersionKey))->fetchAll(PDO::FETCH_NUM)) {
							SERIA_Base::debug('Changing directory version entry identifier from '.$compatMd5.' to '.$dbVersionKey. '(compat)');
							return $this->runUpdatesFromDirectory($fullPath);
						}
					}
					/*
					 * First check if a match exists with backslashes
					 */
					$like = str_replace('%', '_', $dbVersionKey); /* Just in case */
					$like = str_replace('/', '_', $like);
					$query = $db->query('SELECT `key` FROM {dbversion} WHERE `key` LIKE :key', array('key'=>$like))->fetchAll(PDO::FETCH_NUM);
					foreach ($query as $q) {
						$key = $q[0];
						$key = str_replace('\\', '/', $key);
						if ($key == $dbVersionKey) {
							SERIA_Base::debug('Changing directory version entry identifier from '.$q[0].' to '.$key. '(compat)');
							$db->exec('UPDATE {dbversion} SET `key` = :new WHERE `key` = :old', array('old' => $q[0], 'new' => $key));
							return $this->runUpdatesFromDirectory($fullPath);
						}
					}
					$db->query('INSERT INTO {dbversion} (`key`, path, version) VALUES(:key, :path, :version)', array('key'=>$dbVersionKey,'path'=>$path, 'version'=>'0'));
					$dbVersion = 0;
				} else {
					$dbVersion = $query[0][0];
				}

				SERIA_Base::debug('Install: Database version for directory ' . $path . ' is ' . $dbVersion);
				foreach ($files as $id => $file) {
					if ($id > $dbVersion) {
						SERIA_Base::debug('Install: Running update file ' . $file . ' in directory ' . $path);
						try {
							$__setVersion = $this->runUpdatefile($fullPath . '/' . $file);
							$dbVersion = $id;
							if ($__setVersion !== false) {
								if (is_numeric($__setVersion)) {
									$dbVersion = $__setVersion;
								}
							}
							$db->query('UPDATE ' . SERIA_PREFIX . '_dbversion SET version=' . $dbVersion . ' WHERE `key`=' . $db->quote($dbVersionKey));
						} catch (Exception $exception) {
							try {
								$updateFailCache = new SERIA_Cache('updatefail' . md5($path));
								if (!$updateFailCache->get($id)) {
									SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::ERROR, 'Update file ' . $file . ' in ' . $path . ' is failing: ' . $exception->getMessage());
									$updateFailCache->set($id, true, 300);
								}
							} catch (Exception $null) {}
							SERIA_Base::debug('<strong>Install: Update file ' . $file . ' in directory ' . $path . ' failed: ' . $exception->getMessage()."</strong>");
							break;
 						}
					}
				}
			}
		}

		private function runUpdateFile($filename) {
			$__setVersion = false;
			require($filename);
			return $__setVersion;
		}
	}
