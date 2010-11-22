<?php

if (!class_exists('SERIA_ArticleQuery'))
	return; /* Seria Publisher is not enabled */

/*
 * This is a heavy job of checking every single file-article against it's file-object.
 */

$step = 0;
$pagerStep = 1000;
$fq = new SERIA_ArticleQuery('SERIA_File');

do {
	$count = 0;
	$chunk = $fq->page($step, $pagerStep);
	foreach ($chunk as $article) {
		$encoded = $article->get('title');
		$raw = SERIA_Sanitize::reverseFilename($encoded);
		if ($encoded != $raw) {
			SERIA_Base::debug('Reversing filename encoding of file "'.$encoded.'" to "'.$raw.'"');
			$article->writable(true);
			$article->set('title', $raw);
			$article->save();
		}
		$count++;
	}
	$step += $count;
} while ($count == $pagerStep);
