<?php

if (defined('JEP_AUTOPRELOADER_THRESHOLD')) {
	require(dirname(__FILE__).'/classes/JEP_Autopreloader.class.php');
	$preloadFile = SERIA_TMP_ROOT.'/jep_autopreloader_preload.php';
	if (mt_rand(0,50)>48) {
		JEP_Autopreloader::eavesdrop();
		JEP_Autopreloader::collectStatistics();
	} else if (file_exists($preloadFile) && strpos($_SERVER['PHP_SELF'], 'maintain.php') === false) {
		SERIA_Base::debug('Preloading classes');
		include($preloadFile);
	} else {
		JEP_Autopreloader::eavesdrop();
		SERIA_Base::debug('Neither collecting statistics nor preloading classes.');
	}
}

function Autopreloader_init()
{
	if (defined('JEP_AUTOPRELOADER_THRESHOLD'))
		SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array('JEP_Autopreloader', 'guiEmbed'));
}
