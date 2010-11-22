<?php
	class SERIA_Filesystem {
		public function copyDirectory($source, $destination, $ignoreSymlinks = true, $ignoreSvn = true) {
			if (is_dir($source)) {
				if ($dirhandler = opendir($source)) {
					if (!file_exists($destination)) {
						mkdir($destination, 0777, true);
					}
					while (($dircontent = readdir($dirhandler)) !== false) {
						if (($dircontent != '..') && ($dircontent != '.') && (!$ignoreSvn || ($dircontent != '.svn'))) {
							$sourcePath = $source . '/' . $dircontent;
							$destinationPath = $destination . '/' . $dircontent;
							
							self::copyDirectory($sourcePath, $destinationPath, $ignoreSymlinks);
						}
					}
					closedir($dirhandler);
					return true;
				}
			} elseif (is_link($source)) {
				if (!$ignoreSymLinks) {
					return copy($source, $destination);
				}
				return true;
			} elseif (is_file($source)) {
				return copy($source, $destination);
			}
			
			return false;
		}
		
		public static function deleteDirectory($path) {
			if (is_link($path)) {
				unlink($path);
			} elseif (is_file($path)) {
				unlink($path);
			} elseif (is_dir($path)) {
				$dirhandler = opendir($path);
				if ($dirhandler) {
					while (($node = readdir($dirhandler)) !== false) {
						if (($node != '.') && ($node != '..')) {
							self::deleteDirectory($path . '/' . $node);
						}
					}
					closedir($dirhandler);
				}
				
				rmdir($path);
			} else {
				unlink($path);
			}
		}
		
		public static function getDirectorySize($path, $divider = 1) {
			if (file_exists($path)) {
				$size = 0;
				
				if (is_dir($path)) {
					$dirhandler = opendir($path);
					if ($dirhandler) {
						while (($node = readdir($dirhandler)) !== false) {
							if (($node != '.') && ($node != '..')) {
								$newpath = $path . '/' . $node;
								$size += self::getDirectorySize($newpath);
							}
						}
						
						closedir($dirhandler);
					}
				} else {
					$size = filesize($path);
				}
				
				$size = $size / $divider;
				return $size;
			}
			
			return 0;
		}

		public static function breakUpPath($path, $realpath=false)
		{
			if ($realpath) {
				$rpath = realpath($path);
				if (!$rpath) {
					if (!file_exists($path))
						throw new SERIA_Exception('File does not exist: '.$path);
					throw new SERIA_Exception('Realpath for '.$path.' does not resolve.');
				}
				$path = $rpath;
			} else {
				if (DIRECTORY_SEPARATOR == '\\')
					$path = str_replace('/', DIRECTORY_SEPARATOR, $path);
			}
			$components = explode(DIRECTORY_SEPARATOR, $path);
			if ($path[0] == DIRECTORY_SEPARATOR && $components[0] == '')
				$components[0] = DIRECTORY_SEPARATOR;
			return $components;
		}
		public static function getUrlFromPath($path)
		{
			static $root_c = null;
			static $root_count = 0;
			static $httproot;

			if ($root_c === null) {
				$root_c = self::breakUpPath(SERIA_ROOT, true);
				$root_count = count($root_c);
				$httproot = SERIA_HTTP_ROOT;
				if (substr($httproot, -1) == '/')
					$httproot = substr($httproot, 0, -1);
			}
			$path_c = self::breakUpPath($path, true);
			$path_count = count($path_c);
			if ($path_count >= $root_count) {
				$i = 0;
				do {
					$rc = $root_c[$i];
					$pc = array_shift($path_c);
					if ($rc != $pc)
						throw new SERIA_Exception('Path is not a subpath of SERIA_ROOT (mismatch).');
				} while (++$i < $root_count);
				/*
				 * Remaining path components are the relative path of SERIA_ROOT, we can start creating the URL:
				 */
				$relpath = implode('/', $path_c);
				return $httproot.'/'.$relpath;
			} else
				throw new SERIA_Exception('Path is not a subpath of SERIA_ROOT.');
		}
		public static function getCachedUrlFromPath($path)
		{
			static $root_c = null;
			static $root_count = 0;
			static $httproot;

			if ($root_c === null) {
				$root_c = self::breakUpPath(SERIA_ROOT, true);
				$root_count = count($root_c);
				$httproot = SERIA_CACHED_HTTP_ROOT;
				if (substr($httproot, -1) == '/')
					$httproot = substr($httproot, 0, -1);
			}
			$path_c = self::breakUpPath($path, true);
			$path_count = count($path_c);
			if ($path_count >= $root_count) {
				$i = 0;
				do {
					$rc = $root_c[$i];
					$pc = array_shift($path_c);
					if ($rc != $pc)
						throw new SERIA_Exception('Path is not a subpath of SERIA_ROOT (mismatch).');
				} while (++$i < $root_count);
				/*
				 * Remaining path components are the relative path of SERIA_ROOT, we can start creating the URL:
				 */
				$relpath = implode('/', $path_c);
				return $httproot.'/'.$relpath;
			} else
				throw new SERIA_Exception('Path is not a subpath of SERIA_ROOT.');
		}
	}
?>