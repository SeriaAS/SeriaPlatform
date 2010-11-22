<?php
	require_once('common.php');

	function contents($__param_file, $__param_vars = array()) {
		// Using SERIA_Template for actual parsing. The contents() global method is deprecated.
		// contents() is available for compatibility. Should not be used.
		return SERIA_Template::parseToString(dirname(__FILE__) . '/' . $__param_file, $__param_vars);
	}

	require_once(dirname(__FILE__)."/../main.php");
	
	$gui = new SERIA_Gui(_t('File archive'));
	
	SERIA_Base::pageRequires("login");
	SERIA_Base::pageRequires("javascript");
	SERIA_Base::viewMode("admin");
	$gui->exitButton(_t("Close window"), "window.close()");
	
	$gui->addTohead('filearchive_styles', '<link rel="stylesheet" type="text/css" href="' . SERIA_HTTP_ROOT . '/seria/filearchive/style.css">');
	
	$contents = "";
	
	$incomingDirectoryEnabled = false;
	if (file_exists(SERIA_FILE_INCOMING_ROOT)) {
		$incomingDirectoryEnabled = true;
	}
	
	$incomingServers = SERIA_IncomingFtpServers::find_all();
	
	$contents .= SERIA_Template::parseToString("top.php", array('incomingDirectoryEnabled' => $incomingDirectoryEnabled, 'incomingServers' => $incomingServers));
	
	$contents .= SERIA_Template::parseToString('handleFileUpload.php');
	
	$archiveArgs = array('type' => 'recent');
	if ($_GET['multiselect']) {
		$archiveArgs['multiselect'] = $multiselect = true;
	}
	
	$filenameList = '';
	
	$contents .= SERIA_Template::parseToString('javascriptDefs.js.php');
	
	$contents .= SERIA_Template::parseToString('archive_top.php', $archiveArgs);
	$contents .= SERIA_Template::parseToString('archive.php', $archiveArgs);
	$contents .= SERIA_Template::parseToString('archive_bottom.php', $archiveArgs);
	
	$contents .= SERIA_Template::parseToString('archive_top.php', array('type' => 'archive', 'multiselect' => $multiselect));
	$contents .= SERIA_Template::parseToString('archive.php', array('type' => 'archive', 'multiselect' => $multiselect));
	$contents .= SERIA_Template::parseToString('archive_bottom.php', array('type' => 'archive', 'multiselect' => $multiselect));
	
	
	if ($incomingDirectoryEnabled) {
		$contents .= SERIA_Template::parseToString('incoming_top.php', array('type' => 'incoming'));
		$contents .= SERIA_Template::parseToString('incoming_bottom.php', array('type' => 'incoming'));
	}
	
	foreach ($incomingServers as $incomingServer) {
		$contents .= SERIA_Template::parseToString('incoming_top.php', $params = array('incomingServer' => $incomingServer, 'type' => 'incoming_' . $icomingServer->id));
		$contents .= SERIA_Template::parseToString('incoming_bottom.php', $params);
	}
	
	if ($multiselect) {
		$contents .= SERIA_Template::parseToString('selectedFiles.php', array('multiselect' => (bool) $_GET['multiselect']));
	} else {
		$contents .= SERIA_Template::parseToString('selectedFile.php');
	}
	
	if ($multiselect) {
		$contents .= SERIA_Template::parseToString('multiselect.js.php');
	} else {
		$contents .= SERIA_Template::parseToString('singleselect.js.php');
	}
	
	$gui->contents($contents);
	echo $gui->output(true);
?>
