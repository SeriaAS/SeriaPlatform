<?php
	require_once(dirname(__FILE__)."/common.php");
	SERIA_Base::pageRequires("javascript");
	SERIA_Base::pageRequires("admin");
	SERIA_Base::viewMode("admin");

	SERIA_Base::addFramework('bml');

	$gui->exitButton(_t("Logout"), "location.href='../../';");
	$gui->activeMenuItem('controlpanel/other/translation');

	$contents = 
"<h1 class='legend'>"._t("Seria Platform Translation Tool")."</h1>
<p>"._t("Seria Platform Translation Tool allows you to translate _t-text for your site.")."</p>";

	if (isset($_GET['lang_root_local'])) {
		$lang_root = SERIA_LANG_PATH;
		$contents .= seria_bml('p')->addChild(
			seria_bml_ahref(SERIA_HTTP_ROOT.'/seria/apps/translation/')->setText(_t('Seria Platform translation'))
		)->output();
	} else {
		$lang_root = SERIA_ROOT.'/seria/lang';
		$contents .= seria_bml('p')->addChild(
			seria_bml_ahref(SERIA_HTTP_ROOT.'/seria/apps/translation/?lang_root_local=yes')->setText(_t('User code translation'))
		)->output();
	}

	$langs = readTranslationTrees($lang_root);

	$table = new SERIA_Table(array(
		'fields' => array(
			array(
				'title' => _t('Language')
			),
			array(
				'title' => _t('Files')
			),
			array(
				'title' => _t('Strings')
			)
		)
	));
	foreach ($langs as $lang => $files) {
		$fileCount = 0;
		$stringCount = 0;
		foreach ($files as $path => $strings) {
			foreach ($strings as $hash => $string)
				$stringCount++;
			$fileCount++;
		}
		$rawData = array($lang, $fileCount, $stringCount);
		$displayData = array(
			$rawData[0] != 'default' ? seria_bml_ahref(SERIA_HTTP_ROOT.'/seria/apps/translation/translate.php?translateTo='.urlencode($rawData[0]).(isset($_GET['lang_root_local']) ? '&lang_root_local=yes' : ''))->setText($rawData[0])->output() : $rawData[0],
			$rawData[1],
			$rawData[2],
		);
		$table->addRow($displayData, $rawData);
	}

	$tree = seria_bml()->addChildren(array(
		$table,
		seria_bml('form', array('method' => 'get', 'action' => SERIA_HTTP_ROOT.'/seria/apps/translation/translate.php'))->addChild(
			seria_bml('div')->addChildren(array(
				(isset($_GET['lang_root_local']) ? seria_bml('input', array('type' => 'hidden', 'name' => 'lang_root_local', 'value' => 'yes')) : false),
				seria_bml('input', array('name' => 'translateTo', 'type' => 'text')),
				seria_bml('button', array('type' => 'submit'))->setText(_t('Translate'))
			))
		)
	));

	$contents .= $tree->output();

	$gui->contents($contents);
	
	echo $gui->output();
	
	
