<?php
	require_once(dirname(__FILE__)."/../../../main.php");

	if(SERIA_Base::isLoggedIn()) {
		if($_REQUEST["id"] == 0) {
			// We're pinging from a new article - we dont need to lockdown the article with a given ID.
		} else if($_REQUEST["id"]>0) {
			// Lock the article.
			try {
				$article = SERIA_Article::createObjectFromId($_REQUEST["id"]);
			} catch(SERIA_Exception $e) {
				// No such article, no need to ping it!	
			}
			$article->writable();
			$article->set("lock_time", time());
			SERIA_Base::elevateUser(array($article, 'save'));
		} else {
			// No post data? that's odd!
		}
try {
		$foils_file = SERIA_File::createObject($article->get("foils_id"));

		$foils = LiveAPI::generateFoilsFromPPT($foils_file);

		//$foils = SERIA_File::createObject($article->get("foils_id"))->convertTo('ppt2png');
		//$foils = SERIA_File::createObject($article->get("foils_id"))->convertTo('Ziptoimages');
} catch(SERIA_Exception $e) {
	$transcode_status = 'no_foils';
}
		if(is_array($foils))
			$transcode_status = 'finished';
		else if($foils)
			$transcode_status = 'processing';
		else
			$transcode_status = 'error';
		
		$successXML = '<result>
					<status>success</status>
					<transcode_status>'.$transcode_status.'</transcode_status>
				</result>';
		SERIA_Template::override('text/xml', $successXML);
	} else {
		$failureXML = '<result><status>failed</status></result>';
		SERIA_Template::override('text/xml', $failureXML);
	}
