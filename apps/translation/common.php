<?php
	require_once(dirname(__FILE__)."/../../../seria/main.php");
	SERIA_Base::pageRequires("admin");
	$gui = new SERIA_Gui(_t("Seria Platform Translation Tool"));

	$gui->topMenu(_t("Translation tool"), "location.href=\"" . SERIA_HTTP_ROOT . "/seria/apps/translation/\";", "seria");

	SERIA_Applications::getApplication('seria_controlpanel')->setActive(true);

	function transSubdirsOfDirectory($dir)
	{
		$dh = opendir($dir);
		if ($dh === false)
			return false;
		$subdirs = array();
		while (($filename = readdir($dh)) !== false) {
			if ($filename[0] != '.' && is_dir($dir.'/'.$filename))
				$subdirs[] = $filename;
		}
		closedir($dh);
		return $subdirs;
	}
	function transSubtreeOfDirectory($dir)
	{
		$append = array();
		$subdirs = transSubdirsOfDirectory($dir);
		foreach ($subdirs as $subdir) {
			$th_subdirs = transSubtreeOfDirectory($dir.'/'.$subdir);
			foreach ($th_subdirs as $th_dir)
				$append[] = $subdir.'/'.$th_dir;
		}
		return array_merge($subdirs, $append);
	}
	function readTranslationTree($lang_root, $lang)
	{
		/* read directory structure */
		$dirs = transSubtreeOfDirectory($lang_root.'/'.$lang);
		$files = array();
		foreach ($dirs as $reldir) {
			$dir = $lang_root.'/'.$lang.'/'.$reldir;
			$dh = opendir($dir);
			if ($dh === false)
				continue;
			while (($filename = readdir($dh)) !== false) {
				$path = $dir.'/'.$filename;
				$relpath = $reldir.'/'.$filename;
				if (!is_dir($path))
					$files[$relpath] = array();
			}
			closedir($dh);
		}
		/* Read trans-lists */
		foreach ($files as $relpath => &$Giant_list_lang) {
			require($lang_root.'/'.$lang.'/'.$relpath);
		}
		return $files;
	}
	function readTranslationTrees($lang_root)
	{
		$langs = array_flip(transSubdirsOfDirectory($lang_root));
		foreach ($langs as $lang => &$subfiles) {
			/* Grok the whole subtree of this */
			$subfiles = readTranslationTree($lang_root, $lang);
		}
		return $langs;
	}
