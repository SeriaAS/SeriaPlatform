<?php
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets(SERIA_ShareWithFriends/js/common.js');
	$article = $this->getNamedObject();
	$http_root = SERIA_HTTP_ROOT;
	$len = strlen($http_root);
	if ($len > 0 && $http_root[$len-1] == '/')
		$http_root = substr($http_root, 0, --$len);
	$twitterurl = $http_root.'/seria/platform/widgets/SERIA_ShareWithFriends/pages/doShare.php?type=twitter&widget_id='.$this->getId();
	$article_url = $http_root.'/?article_id='.$article->get('id');

	$templateName = 'index.php';
	$rel_filename = 'widgets/'.$this->getWidgetDirname().'/templates/'.$templateName;
	$filename = SERIA_ROOT.'/'.$rel_filename;
	if (!file_exists($filename))
		$filename = SERIA_ROOT.'/seria/platform/'.$rel_filename;
	SERIA_Template::parse($filename, array(
		'twitter_share_url' => $twitterurl,
		'article_url' => $article_url,
		'share' => array(
			'twitter' => $this->get('share_twitter'),
			'facebook' => $this->get('share_facebook')
		)
	));

?>