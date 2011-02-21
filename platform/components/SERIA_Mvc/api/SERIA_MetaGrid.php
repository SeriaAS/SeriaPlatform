<?php

require_once(dirname(__FILE__).'/../../../../main.php');

if (isset($_GET['metaGridKey']) && isset($_GET['op']) &&
    ($info = SERIA_MetaGrid::restoreFromCache($_GET['metaGridKey']))) {

	/*
	 * Restore the original REQUEST_URI in order to allow usage of
	 * SERIA_Url::current() and similar in this AJAX call.
	 */
	if (isset($info['REQUEST_URI']) && $info['REQUEST_URI'])
		$_SERVER['REQUEST_URI'] = $info['REQUEST_URI'];

    if ($_GET['op'] == 'tbody-content') {
		if (isset($_GET['sortBy']) && $_GET['sortBy'])
			$info['object']->userRequestedOrdering($_GET['sortBy']);
		if ($info['callableTemplate'] && !is_callable($info['args']['templateOrCallback'])) {
			SERIA_Lib::publishJSON(array(
				'errorMsg' => ((!defined('SERIA_DEBUG') || !SERIA_DEBUG) ? _t('Can\'t sort this table.') : _t('Can\'t sort this table because the template is a function that is not available from global namespace (from AJAX API)'))
			));
		}
		$serialize_key = $info['object']->serializeOutputCall($info['args']['columnSpec'], $info['args']['templateOrCallback'], $info['args']['pageSize']);
		SERIA_Lib::publishJSON(array(
			'data' => $info['object']->outputTBodyContent($info['args']['columnSpec'], $info['args']['templateOrCallback'], $info['args']['pageSize']),
			'errorMsg' => '',
			'serializeKey' => $serialize_key
		));
	}
} else
	SERIA_Lib::publishJSON(array(
		'errorMsg' => _t('There is no record of this table or operation, so I can\'t do anything about it!')
	));