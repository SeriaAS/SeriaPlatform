<?php
	SERIA_Base::pageRequires("admin");
	$gui->topMenu(_t("Add server"), "location.href='".SERIA_Base::url(array("grid-id"=>""))."'");
	$grid = new SERIA_Grid(array(
		"gridIntroduction" => "<h1>".
			_t("FTP-servers for static content")
		."</h1><p class='info'>".
			_t("FTP-servers for static content is used to offload the main server when serving static files such as images and other files.")
		."</p>",
		"formIntroduction" => "<h1>".
			_t("Add/edit FTP-server")
		."</h1>",
		"allowAdd" => true,
		"allowDelete" => true,
		"caption" => _t("FTP-servers"),
		"description" => _t("Edit FTP-servers that are used for hosting static files."),
		"table" => SERIA_PREFIX."_ftp_servers",
		"sort" => "host",
		"primaryKey" => "id",
		"fields" => array(
			"host" => array(
				"caption" => _t("Host"),
				"validation" => 'return SERIA_IsInvalid::name($value, true);',
				"textAlign" => 'left',
				"gridWidth" => 200,
				"outputAsString" => 'return htmlspecialchars($value);',
				"outputAsFormField" => 'return "<input type=\"text\" name=\"".$fieldName."\" value=\"".htmlspecialchars($value)."\" style=\"width: 300px;\">";',
				"sortable" => true,
			),
			"port" => array(
				"caption" => _t("FTP&nbsp;port"),
				"validation" => 'return SERIA_IsInvalid::integer($value, true, 1, 65535);',
				"textAlign" => 'left',
				"gridWidth" => 50,
				"outputAsString" => 'return htmlspecialchars($value);',
				"outputAsFormField" => 'return "<input type=\"text\" name=\"".$fieldName."\" value=\"".($value?htmlspecialchars($value):21)."\" style=\"width: 30px;\">";',
			),
			"username" => array(
				"caption" => _t("Username"),
				"validation" => 'return false;',
				"textAlign" => 'left',
				"gridWidth" => 200,
				"outputAsString" => 'return htmlspecialchars($value);',
				"outputAsFormField" => 'return "<input type=\"text\" name=\"".$fieldName."\" value=\"".htmlspecialchars($value)."\" style=\"width: 300px;\">";',
				"sortable" => true,
			),
			"password" => array(
				"caption" => _t("Password"),
				"validation" => 'return false;',
				"textAlign" => 'left',
				"gridWidth" => false,
				"outputAsString" => 'return "********";',
				"outputAsFormField" => 'return "<input type=\"password\" name=\"".$fieldName."\" value=\"".htmlspecialchars($value)."\" style=\"width: 300px;\">";',
			),
			"type" => array(
				"caption" => _t("Type"),
				"validation" => 'return SERIA_IsInvalid::oneOf($value, array("normal","ssl"));',
				"textAlign" => 'left',
				"gridWidth" => 70,
				"outputAsString" => 'switch($value) {
					case "normal" : return _t("Normal");
					case "ssl" : return _t("SSL-FTP");
				}',
				"outputAsFormField" => 'return "
					<input ".($value!="ssl"?"checked=\"1\" ":"")."type=\"radio\" name=\"".$fieldName."\" value=\"normal\" id=\"".$fieldName."_normal\"> <label for=\"".$fieldName."_normal\">"._t("Normal FTP")."</label><br>
					<input ".($value=="ssl"?"checked=\"1\" ":"")."type=\"radio\" name=\"".$fieldName."\" value=\"ssl\" id=\"".$fieldName."_ssl\"> <label for=\"".$fieldName."_ssl\">"._t("SSL-FTP")."</label>";
				',
			),
			"pasv" => array(
				"caption" => _t("Transfer&nbsp;mode"),
				"validation" => 'return false;',
				"textAlign" => 'left',
				"gridWidth" => 70,
				"outputAsString" => 'switch($value) {
					case "1" : return _t("Passive FTP");
					default : return _t("Normal FTP");
				}',
				"outputAsFormField" => 'return "
					<input ".($value!="1"?"checked=\"1\" ":"")."type=\"radio\" name=\"".$fieldName."\" value=\"0\" id=\"".$fieldName."_0\"> <label for=\"".$fieldName."_0\">"._t("Normal FTP")."</label><br>
					<input ".($value=="1"?"checked=\"1\" ":"")."type=\"radio\" name=\"".$fieldName."\" value=\"1\" id=\"".$fieldName."_1\"> <label for=\"".$fieldName."_1\">"._t("Passive mode transfer (PASV)")."</label>";
				',
			),
			"active" => array(
				"postHandler" => 'return $value ? 1 : 0;',
				"caption" => _t("Status"),
				"validation" => 'return false;',
				"gridWidth" => 70,
				"outputAsString" => 'if($value) return _t("Active"); return _t("DISABLED");',
				"outputAsFormField" => 'return "<input ".($value?"checked=\"1\" ":"")."type=\"checkbox\" name=\"".$fieldName."\" id=\"".$fieldName."\"> <label for=\"".$fieldName."\">"._("Check this if in use.")."</label>";',
			),
		),
	));

	$gui->contents($grid->output());
