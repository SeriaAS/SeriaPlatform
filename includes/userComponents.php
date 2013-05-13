<?php
	/**
	*	Include file for supporting Seria Platform components
	*/
	$GLOBALS['seria']['components'] = array();

	if(defined('SERIA_USER_COMPONENTS')) {
		$components = explode(",", trim(SERIA_USER_COMPONENTS));
		foreach($components as $i => $component) {
			if($component) {
				$components[$i] = SERIA_ROOT.'/seria/components/'.$component;
			} else unset($components[$i]);
		}
	} else {
	        if(!($components = SERIA_Base::coreCache('user-components'))) {
			$components = glob(SERIA_ROOT."/seria/components/*", GLOB_ONLYDIR);
	                SERIA_Base::coreCache('user-components', $components, 5);
	        }
	}
	SERIA_Manifests::loadComponents('userComponents', $components);
