<?php

function createDirectoryRecursive(array $path, SERIA_FileDirectory $dir=null)
{
	if (!$path)
		return $dir;
	$dirname = array_shift($path);
	if ($dir === null)
		$dir = SERIA_FileDirectory::getRoot();
	$dirs = $dir->getChildren();
	foreach ($dirs as $pdir) {
		if ($pdir->get('name') == $dirname)
			return createDirectoryRecursive($path, $pdir);
	}
	$pdir = SERIA_FileDirectory::createDirectory($dirname, $dir);
	return createDirectoryRecursive($path, $pdir);
}

$q = new SERIA_FileQuery();

$fa = $q->getFileArticles();

if ($fa->count() > 100) {
	foreach ($fa as $fileArticle) {
		$file = $fileArticle->getFile();
		$path = date('Y/m/d', $file->get('createdAt'));
		$filename = $file->get('filename');
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$path .= '/'.$ext;
		$path = explode('/', $path);
		$dir = createDirectoryRecursive($path);
		$dir->moveFileArticleInto($fileArticle);
	}
}