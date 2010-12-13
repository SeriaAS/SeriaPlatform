<?php

class SERIA_ScriptLoader_Solver
{
	private static $scripts;

	private static $providersDone = false;
	private static $loadList = null;

	private static $rollback = array();

	public static function setScripts($scripts)
	{
		self::$scripts = $scripts;
	}
	public static function getScripts()
	{
		return self::$scripts;
	}

	private static function beginTransaction()
	{
		array_push(self::$rollback, array(
			'scripts' => self::$scripts,
			'providersDone' => self::$providersDone,
			'loadList' => self::$loadList
		));
	}
	private static function rollback()
	{
		$rollback = array_pop(self::$rollback);
		self::$scripts = $rollback['scripts'];
		self::$providersDone = $rollback['providersDone'];
		self::$loadList = $rollback['loadList'];
	}
	private static function commit()
	{
		array_pop(self::$rollback);
	}

	private static function setupProviders()
	{
		if (self::$providersDone)
			return;
		self::$providersDone = true;
		foreach (self::$scripts as $name => $script) {
			foreach ($script['versions'] as $vername => $version) {
				if (isset($version['provides'])) {
					foreach ($version['provides'] as $provindex => $provides) {
						$provName = $provides['name'];
						if (isset($provides['version'])) 
							$provVersion = $provides['version'];
						else {
							$provVersion = $vername;
							$provides['version'] = $provVersion;
						}
						if ($provName == $name)
							throw new Exception('A script provides itself (loop,cyclic): '.$name);
						if (isset(self::$scripts[$provName]) &&
						    isset(self::$scripts[$provName]['versions'][$provVersion])) {
						    	/*
						    	 * Redirect direct loading of provided script.
						    	 */
							self::$scripts[$provName]['versions'][$provVersion]['alias'] = array(
								'name' => $name,
								'version' => $vername
							);
						} else
							throw new Exception('Provided script versions does not exist: '.$provName.'-'.$provVersion);
						$version['provides'][$provindex] = $provides;
					}
				}
				$script['versions'][$vername] = $version;
			}
			self::$scripts[$name] = $script;
		}
	}

	private static function initLoadingOrder()
	{
		if (self::$loadList !== null)
			return;

		self::$loadList = array();
		$safe = self::getSafeLoadOrder();
		foreach ($safe as $safeload)
			self::$loadList[$safeload] = array(
				'js' => false,
				'css' => false
			);
	}
	private static function setJSLoad($scriptName, $jsload)
	{
		self::initLoadingOrder();
		if (SERIA_DEBUG && !isset(self::$loadList[$scriptName]))
			throw new Exception('Unknown script: '.$scriptName);
		self::$loadList[$scriptName]['js'] = $jsload;
	}
	private static function setCSSLoad($scriptName, $cssload)
	{
		self::initLoadingOrder();
		if (SERIA_DEBUG && !isset(self::$loadList[$scriptName]))
			throw new Exception('Unknown script: '.$scriptName);
		self::$loadList[$scriptName]['css'] = $cssload;
	}
	public static function getJS()
	{
		self::initLoadingOrder();
		$js = '';
		foreach (self::$loadList as $loadjs) {
			if ($loadjs['js'])
				$js .= $loadjs['js']."\n";
		}
		return $js;
	}
	public static function getCSS()
	{
		self::initLoadingOrder();
		$css = '';
		foreach (self::$loadList as $loadcss) {
			if ($loadcss['css'])
				$css .= $loadcss['css']."\n";
		}
		return $css;
	}

	private static function doLoadScript($scriptName, &$script, &$version, $provided=false)
	{
		/*
		 * Handle redirects..
		 */
		if (!$provided && isset($version['alias'])) {
			/*
			 * Clear any loads by any other version of this script
			 */
			self::setJSLoad($scriptName, null);
			self::setCSSLoad($scriptName, null);
			self::loadScript($version['alias']['name'], $version['alias']['version'], $version['alias']['version'], $version['alias']['version']);
			return;
		}
		/*
		 * Handle dependencies..
		 */
		if (isset($version['depends'])) {
		   	foreach ($version['depends'] as $depend) {
		   		if (!isset(self::$scripts[$depend['name']]['constraints']))
		   			self::$scripts[$depend['name']]['constraints'] = array();
		   		$vc = array(
		   			'preferred' => isset($depend['preferred']) ? $depend['preferred'] : false,
		   			'minimum' => isset($depend['minimum']) ? $depend['minimum'] : false,
		   			'maximum' => isset($depend['maximum']) ? $depend['maximum'] : false
		   		);
		   		self::$scripts[$depend['name']]['constraints'][$scriptName] = $vc;
				self::loadScript(
					$depend['name'],
					$vc['preferred'],
					$vc['minimum'],
					$vc['maximum']
				);
			}
		}
		if (isset($version['provides'])) {
			foreach ($version['provides'] as $provided_v)
				self::loadScript($provided_v['name'], $provided_v['version'], $provided_v['version'], $provided_v['version'], true);
		}
		/*
		 * Do the (re)load..
		 */
		$script['loaded']['index'] = $version['index'];
//		echo 'Loading '.$scriptName.': '.SERIA_CACHED_HTTP_ROOT.$version['filename'].($provided?' (provided)':'').'<br>'."\n";
		if (!$provided && $version['filename'] !== false) {
			if (is_string($version['filename']))
				$filenames = array($version['filename']);
			else
				$filenames = $version['filename'];
			$jsload = '';
			foreach ($filenames as $fname) {
				/*
				 * Also support loading of external javascripts.
				 */
				if (strpos($fname, 'http://') !== 0 && strpos($fname, 'https://') !== 0)
					$filename = SERIA_CACHED_HTTP_ROOT.$fname;
				else
					$filename = $fname;

				$jsload .= "<script type='text/javascript' src=\"".htmlspecialchars($filename)."\"></script>";
			}
			self::setJSLoad($scriptName, $jsload);
		} else
			self::setJSLoad($scriptName, null);
		$cssload = '';
		if (!$provided && isset($version['css'])) {
			foreach ($version['css'] as $cssfile)
				$cssload .= "<link rel='stylesheet' type='text/css' href=\"".htmlspecialchars(SERIA_CACHED_HTTP_ROOT.$cssfile)."\" title=\"\" %XHTML_CLOSE_TAG%>";
		}
		self::setCSSLoad($scriptName, $cssload);
	}
	private static function loadScriptAsReq($scriptName, $prefIndex, $minIndex, $maxIndex, $provided=false, $dependencyRetry=false)
	{
		$script =& self::$scripts[$scriptName];
		SERIA_Template::debugMessage('SERIA_ScriptLoader_Solver request: '.$scriptName.' '.$minIndex.'<='.$prefIndex.'<='.$maxIndex);
		if (isset($script['loaded'])) {
		   	$loadedInfo =& $script['loaded'];
			$combMinIndex = max($loadedInfo['minimum'], $minIndex);
			$combMaxIndex = min($loadedInfo['maximum'], $maxIndex);
			if ($combMinIndex > $combMaxIndex) {
				SERIA_Template::debugMessage('SERIA_ScriptLoader_Solver: An impossible situation was requested (impossible to satisfy min<=max): '.$scriptName.' (min='.$minIndex.',max='.$maxIndex.')');
				$keys = array_keys($script['versions']);
				if (!$dependencyRetry && $keys) {
					SERIA_Template::debugMessage('SERIA_ScriptLoader_Solver: Trying to recalculate dependency constraints..');
					$first_key = $keys[0];
					$last_key = array_pop($keys);
					if (!isset($script['versions'][$first_key]['index']))
						throw new Exception('No index for first set.');
					if (!isset($script['versions'][$last_key]['index']))
						throw new Exception('No index for last set.');
					$rcMin = $script['versions'][$first_key]['index'];
					$rcMax = $script['versions'][$last_key]['index'];
					if (isset($script['constraints'])) {
						foreach ($script['constraints'] as $name => $constraint) {
							SERIA_Template::debugMessage($name.' asked for '.($constraint['preferred'] !== false ? $constraint['preferred'] : 'any version').', but between '.($constraint['minimum'] !== false ? $constraint['minimum'] : 'lowest').' and '.($constraint['maximum'] !== false ? $constraint['maximum'] : 'highest'));
							if ($constraint['minimum'] !== false) {
								if (!isset($script['versions'][$constraint['minimum']]))
									throw new Exception('Unknown minimum version '.$constraint['minimum']);
								if (!isset($script['versions'][$constraint['minimum']]['index']))
									throw new Exception('No index for version set: '.$constraint['minimum']);
								$rcMin = max($script['versions'][$constraint['minimum']]['index'], $rcMin);
							}
							if ($constraint['maximum'] !== false) {
								if (!isset($script['versions'][$constraint['maximum']]))
									throw new Exception('Unknown maximum version '.$constraint['maximum']);
								if (!isset($script['versions'][$constraint['maximum']]['index']))
									throw new Exception('No index for version set: '.$constraint['maximum']);
								$rcMax = min($script['versions'][$constraint['maximum']]['index'], $rcMax);
							}
						}
					}
					SERIA_Template::debugMessage('Dependency and request version constraints recalculated: '.$rcMin.'<=ver<='.$rcMax);
					$loadedInfo['minimum'] = $rcMin;
					$loadedInfo['maximum'] = $rcMax;
					return self::loadScriptAsReq($scriptName, $prefIndex, $minIndex, $maxIndex, $provided, true);
				}
				throw new Exception('An impossible situation was requested (impossible to satisfy min<=max): '.$scriptName.' (min='.$minIndex.',max='.$maxIndex.')');
			}
			$minIndex = $combMinIndex;
			$maxIndex = $combMaxIndex;
			$loadedInfo['minimum'] = $minIndex;
			$loadedInfo['maximum'] = $maxIndex;
			/*echo $scriptName.' '.$minIndex.'<='.$loadedInfo['index'].'<='.$maxIndex.'<br>';*/
			if ($loadedInfo['index'] >= $minIndex &&
			    $loadedInfo['index'] <= $maxIndex)
			    return;
		} else {
			$loadedInfo = array();
			$loadedInfo['minimum'] = $minIndex;
			$loadedInfo['maximum'] = $maxIndex;
			$script['loaded'] =& $loadedInfo;
		}

		if ($prefIndex < $minIndex || $prefIndex > $maxIndex)
		   	$prefIndex = $maxIndex;
		/*
		 * Try loading the preferred version
		 */
		self::beginTransaction();
		try {
			foreach ($script['versions'] as $version) {
				if (!isset($version['index']))
					throw new Exception('Index is not set');
				if ($version['index'] == $prefIndex) {
					self::doLoadScript($scriptName, $script, $version, $provided);
					self::commit();
					return;
				}
			}
			throw new Exception('Preferred version does not exist.');
		} catch (Exception $preferred_exception) {
			self::rollback();
			$script =& self::$scripts[$scriptName];
			
			$versions = array();
			foreach ($script['versions'] as $versionName => &$version) {
				if ($minIndex <= $version['index'] && $version['index'] <= $maxIndex)
					$versions[] = $versionName;
			}
			unset($version);
			$versions = array_reverse($versions);
			foreach ($versions as $try_ver) {
				$version = $script['versions'][$try_ver];
				self::beginTransaction();
				try {
					self::doLoadScript($scriptName, $script, $version, $provided);
					self::commit();
					return;
				} catch (Exception $e) {
					/* ignore */
					self::rollback();
					$script =& self::$scripts[$scriptName];
					throw $e;
				}
			}
			throw $preferred_exception;
		}
	}
	public static function loadScript($name, $preferred=false, $minimum=false, $maximum=false, $provided=false)
	{
		self::setupProviders();
		if (isset(self::$scripts[$name])) {
			$scriptName =  $name;
		   	$script =& self::$scripts[$name];
			$index = 0;
			foreach ($script['versions'] as $name => &$version) {
				$version['index'] = $index;
				$index++;
			}
			unset($version);
			if ($index == 0)
			   	throw new Exception('No versions available: '.$name);
			if ($minimum !== false) {
			   	if (!isset($script['versions'][$minimum]))
					throw new Exception('Minimum version '.$minimum.' of '.$name.' is not known');
				$minIndex = $script['versions'][$minimum]['index'];
			} else if (isset($script['default']) &&
			           isset($script['default']['minimum'])) {
			        if (!isset($script['versions'][$script['default']['minimum']]))
			        	throw new Exception('Minimum version '.$script['default']['minimum'].' of '.$name.' is not known');
				$minIndex = $script['versions'][$script['default']['minimum']]['index'];
			} else
				$minIndex = 0;
			if ($maximum !== false) {
			   	if (!isset($script['versions'][$maximum]))
					throw new Exception('Maximum version '.$maximum.' of '.$name.' is not known');
				$maxIndex = $script['versions'][$maximum]['index'];
			} else if (isset($script['default']) &&
			           isset($script['default']['maximum'])) {
				if (!isset($script['versions'][$script['default']['maximum']]))
					throw new Exception('Maximum version '.$script['default']['maximum'].' of '.$name.' is not known');
				$maxIndex = $script['versions'][$script['default']['maximum']]['index'];
			} else
				$maxIndex = $index - 1;
			if ($preferred !== false && isset($script['versions'][$preferred]))
				$prefIndex = $script['versions'][$preferred]['index'];
			else if (isset($script['default']) &&
			           isset($script['default']['preferred']) &&
			           isset($script['versions'][$script['default']['preferred']]))
				$prefIndex = $script['versions'][$script['default']['preferred']]['index'];
			else
				$prefIndex = $maxIndex;
			if ($minIndex > $maxIndex)
				throw new Exception('An impossible situation was requested (dependency conflict)');
			if ($prefIndex < $minIndex || $prefIndex > $maxIndex)
			   	$prefIndex = $maxIndex;

			self::loadScriptAsReq($scriptName, $prefIndex, $minIndex, $maxIndex, $provided);
		} else
			throw new Exception('There is no known script with name '.$name);
	}
	public static function userLoadScript($name, $preferred=false, $minimum=false, $maximum=false)
	{
		self::$scripts[$name]['constraints'][] = array(
			'preferred' => $preferred,
			'minimum' => $minimum,
			'maximum' => $maximum
		);
		self::loadScript($name, $preferred, $minimum, $maximum);
	}

	public static function getAllDependencies($name)
	{
		$deps = array();
		$script = self::$scripts[$name];
		foreach ($script['versions'] as $version) {
			if (!isset($version['depends']))
				continue;
			foreach ($version['depends'] as $dep)
				$deps[$dep['name']] = true;
		}
		return array_keys($deps);
	}
	public static function getAllReverseDependencies($name)
	{
		$revDeps = array();
		foreach (self::$scripts as $revName => $script) {
			foreach ($script['versions'] as $revVersion) {
				if (!isset($revVersion['depends']))
					continue;
				foreach ($revVersion['depends'] as $dep) {
					if ($dep['name'] == $name)
						$revDeps[$revName] = true;
				}
			}
		}
		foreach ($revDeps as $revname => &$dep)
			$dep = self::getAllReverseDependencies($revname);
		unset($dep);
		return $revDeps;
	}
	private static function getFullReverseDependencyTree_testReach(&$reach, $tree)
	{
		foreach ($tree as $name => $sub) {
			if (!isset($reach[$name]))
				throw new Exception('Reaching not existing script ('.$name.').');
			if ($sub)
				self::getFullReverseDependencyTree_testReach($reach, $sub);
			$reach[$name] = true;
		}
	}
	public static function getFullReverseDependencyTree()
	{
		$roots = array();
		$testReach = array();
		/* Start with all scripts that have no dependencies: They are the roots */
		foreach (self::$scripts as $name => $script) {
			$testReach[$name] = false;
			if (count(self::getAllDependencies($name)) == 0)
				$roots[$name] = self::getAllReverseDependencies($name);
		}
		self::getFullReverseDependencyTree_testReach($testReach, $roots);
		if (in_array(false, $testReach)) {
			foreach ($testReach as $name => $val) {
				if (!$val)
					SERIA_Template::debugMessage('ERROR: Script dependency loop: '.$name);
			}
			throw new Exception('Can\'t reach all scripts. Script dependency loop detected.');
		}
		return $roots;
	}
	public static function getSafeLoadOrder()
	{
		static $ordering = null;

		if ($ordering !== null)
			return $ordering;

		$revdep = self::getFullReverseDependencyTree();
		/*
		 * Roots first, and collapse trees as we go..
		 */
		$ordering = array();
		while ($revdep) {
			$subroots = array();
			foreach ($revdep as $rootName => &$sub) {
				$subroots = array_merge($subroots, $sub);
				$key = array_search($rootName, $ordering);
				if ($key !== false)
					unset($ordering[$key]);
				$ordering[] = $rootName;
			}
			unset($sub); /* important! */
			$revdep = $subroots;
		}
		return $ordering;
	}
}
