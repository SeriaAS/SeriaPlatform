<?php
	require_once(dirname(__FILE__)."/common.php");
	SERIA_Base::pageRequires("javascript");
	SERIA_Base::pageRequires("login");
	SERIA_Base::viewMode("admin");

	SERIA_Base::addFramework('bml');

	$gui->exitButton(_t("Logout"), "location.href='../../';");
	$gui->activeMenuItem('controlpanel/other/translation');

	if (!SERIA_Base::isAdministrator())
		die('Requires administrator privileges');

	if (!isset($_GET['translateTo']) || !$_GET['translateTo']) {
		header('Location: '.SERIA_HTTP_ROOT.'/seria/apps/translation/'.(isset($_GET['lang_root_local']) ? '&lang_root_local=yes' : ''));
		die();
	}

	$contents = 
"<h1 class='legend'>"._t("Seria Platform Translation Tool")."</h1>
<p>"._t("Seria Platform Translation Tool allows you to translate _t-text for your site.")."</p>";

	if (isset($_GET['lang_root_local']))
		$lang_root = SERIA_LANG_PATH;
	else
		$lang_root = SERIA_ROOT.'/seria/lang';

	$langs = readTranslationTrees($lang_root);

	if (isset($langs['default']))
		$defaultTree =& $langs['default'];
	else
		$defaultTree = array();
	if (isset($langs[$_GET['translateTo']]))
		$langTree =& $langs[$_GET['translateTo']];
	else
		$langTree = array();

	/**
	 * Generate a set of translation items..
	 *
	 * @param $path
	 * @param $defaultPhr
	 * @param $langPhr
	 * @return SERIA_BMLElement
	 */
	function getTranslateFormItems(&$path, &$lang, &$defaultPhr, &$langPhr)
	{
		$candPhr = array_keys($defaultPhr);
		$transPhr = array();
		foreach ($candPhr as $cand) {
			if (!isset($langPhr[$cand]) || (isset($_GET['edit']) && $_GET['edit']))
				$transPhr[$cand] = array(
					'default' => $defaultPhr[$cand],
					$lang => isset($langPhr[$cand]) ? $langPhr[$cand] : ''
				);
		}
		$items = array();
		foreach ($transPhr as $hash => &$values) {
			ob_start();
			?>
				<div>
					<h3><?php echo htmlspecialchars($values['default']); ?></h3>
					<label for="<?php echo htmlspecialchars('id_'.$hash); ?>"><?php echo htmlspecialchars(_t('Translate to %LANG%: ', array('LANG' => $lang))); ?></label>
					<input type='text' name="<?php echo htmlspecialchars('transPhr['.$hash.']'); ?>" id="<?php echo htmlspecialchars('id_'.$hash); ?>" value="<?php echo htmlspecialchars($values[$lang]); ?>" />
					<button type='button' onclick="document.getElementById(<?php echo htmlspecialchars(SERIA_Lib::toJSON('id_'.$hash)); ?>).value = <?php echo htmlspecialchars(SERIA_Lib::toJSON($values['default'])); ?>"><?php echo _t('Same'); ?></button>
				</div>
			<?php
			$items[] = ob_get_clean();
		}
		return seria_bml('div')->addChildren(array(
			seria_bml('h2')->setText(_t('Translate file %FILE%', array('FILE' => $path))),
			seria_bml()->addChildren($items)
		));
	}
	function storeLangFile($filename, $langPhr)
	{
		/* Create the directory if it does not exist */
		$directory = dirname($filename);
		if (!file_exists($directory))
			mkdir($directory, 0775, true);

		/* Write content */
		$Giant_list_lang = $langPhr;
		$fh = fopen($filename.'.tmp', 'w');
		if ($fh) {
			flock($fh, LOCK_EX);
			$newCode = '<?php $Giant_list_lang = array_merge($Giant_list_lang, '.var_export($Giant_list_lang, true).'); ?>';
			/*
			 * Please don't fail after this..
			 */
			while ($newCode) {
				$written = fwrite($fh, $newCode);
				if ($written === false) {
					fclose($fh);
					unlink($filename.'.tmp'); /* This file is truncated, and some or all page views will fail if we don't delete it! */
					throw new Exception('Failed to write a language file, and we have now lost the content! ('.$filename.')');
				}
				$newCode = substr($newCode, $written);
			}
			flock($fh, LOCK_UN);
			fclose($fh);
			if (file_exists($filename))
				unlink($filename);
			rename($filename.'.tmp', $filename);
		} else
			throw new Exception('Failed to open language file: '.$filename);
	}
	
	if (isset($_GET['filename'])) {
		/*
		 * Translate form mode..
		 */

		if (!isset($defaultTree[$_GET['filename']]) && !isset($langTree[$_GET['filename']])) {
			$failingScript = new SERIA_BMLScript(
'
	$(document).ready(function () {
		alert("File not found: '.htmlspecialchars($_GET['filename']).'");
		location.href = \''.SERIA_HTTP_ROOT.'/seria/apps/translation/translate.php?translateTo='.htmlspecialchars(urlencode($_GET['translateTo'])).'\';
	});
', 'text/javascript'
			);
			$contents .= $failingScript->output();
		} else {
			if (isset($defaultTree[$_GET['filename']]))
				$defaultPhr =& $defaultTree[$_GET['filename']];
			else
				$defaultPhr = array();
			if (isset($langTree[$_GET['filename']]))
				$langPhr =& $langTree[$_GET['filename']];
			else
				$langPhr = array();

			if (sizeof($_POST)) {
				/* Add phrases to translation */
				foreach ($_POST['transPhr'] as $hash => $trans) {
					if (isset($defaultPhr) && $trans)
						$langPhr[$hash] = $trans;
				}
				/* Store to file */
				storeLangFile($lang_root.'/'.$_GET['translateTo'].'/'.$_GET['filename'], $langPhr);
				/* Redirect */
				header('Location: '.SERIA_HTTP_ROOT.'/seria/apps/translation/translate.php?translateTo='.urlencode($_GET['translateTo']).(isset($_GET['lang_root_local']) ? '&lang_root_local=yes' : ''));
				die();
			}
			/*
			 * Generate form...
			 */
			$tree = seria_bml('form', array('method' => 'post'))->addChildren(array(
				getTranslateFormItems($_GET['filename'], $_GET['translateTo'], $defaultPhr, $langPhr),
				seria_bml('div')->addChild(
					seria_bml('button', array('type' => 'submit'))->setText(_t('Submit'))
				)
			));
			$contents .= $tree->output();
		}
	} else {
		/*
		 * Summary mode..
		 */

		$table = new SERIA_Table(array(
			'fields' => array(
				array(
					'title' => _t('Filename')
				),
				array(
					'title' => _t('Translated phrases')
				),
				array(
					'title' => _t('Default phrases')
				),
				array(
					'title' => _t('Phrases that need translation')
				)
			)
		));

		$files = array_keys(array_merge($defaultTree, $langTree));
		foreach ($files as $path) {
			if (isset($defaultTree[$path]))
				$defaultPhr =& $defaultTree[$path];
			else
				$defaultPhr = array();
			if (isset($langTree[$path]))
				$langPhr =& $langTree[$path];
			else
				$langPhr = array();
			$allPhr = array_keys(array_merge($defaultPhr, $langPhr));
			$needTrans = 0;
			foreach ($allPhr as $hash) {
				if (!isset($langPhr[$hash]))
					$needTrans++;
			}
			if ((!isset($_GET['showAll']) || !$_GET['showAll']) && $needTrans == 0)
				continue;
			$rawData = array($path, count($langPhr), count($defaultPhr), $needTrans);
			$displayData = array(
				seria_bml_ahref(SERIA_HTTP_ROOT.'/seria/apps/translation/translate.php?translateTo='.urlencode($_GET['translateTo']).'&filename='.urlencode($path).(isset($_GET['lang_root_local']) ? '&lang_root_local=yes' : ''))->setText($rawData[0])->output(),
				seria_bml_ahref(SERIA_HTTP_ROOT.'/seria/apps/translation/translate.php?translateTo='.urlencode($_GET['translateTo']).'&filename='.urlencode($path).'&edit=yes'.(isset($_GET['lang_root_local']) ? '&lang_root_local=yes' : ''))->setText($rawData[1])->output(),
				$rawData[2],
				seria_bml_ahref(SERIA_HTTP_ROOT.'/seria/apps/translation/translate.php?translateTo='.urlencode($_GET['translateTo']).'&filename='.urlencode($path).(isset($_GET['lang_root_local']) ? '&lang_root_local=yes' : ''))->setText($rawData[3])->output()
			);
			$table->addRow($displayData, $rawData);
		}
		$showAllBox = seria_bml('input', array('type' => 'checkbox', 'id' => 'showAllBox', 'name' => 'showAll', 'value' => 'yes'));
		if (isset($_GET['showAll']) && $_GET['showAll'])
			$showAllBox->setAttr('checked', 'checked');
		$getparam = $_GET;
		if (isset($getparam['showAll']))
			unset($getparam['showAll']);
		foreach ($getparam as $nam => &$val)
			$val = seria_bml('input', array('type' => 'hidden', 'name' => $nam, 'value' => $val));
		$tree = seria_bml()->addChildren(array(
			seria_bml('form', array('method' => 'get'))->addChild(
				seria_bml('div')->addChildren(array(
					seria_bml()->addChildren($getparam),
					seria_bml('div')->addChildren(array(
						$showAllBox,
						seria_bml('label', array('for' => 'showAllBox'))->setText(_t('Show all files. (Also those that does not need translation.)'))
					)),
					seria_bml('button', array('type' => 'submit'))->setText(_t('Update'))
				))
			),
			$table
		));
		$contents .= $tree->output();
	}

	$gui->contents($contents);

	echo $gui->output();
