<?php
	require_once(dirname(__FILE__)."/../../main.php");

	if (!defined("SERIA_FORGOT_PASSWORD_ENABLED") || !SERIA_FORGOT_PASSWORD_ENABLED)
		die();

	SERIA_Base::pageRequires("javascript");

	$gui = new SERIA_Gui(_t("Forgot password"));

	$gui->exitButton("Back", "history.go(-1)");

	if (isset($_POST["key"])) {
		$url = "login.php?key=" . $_POST["key"];
		if (isset($_GET["continue"]))
			$url .= "&continue=" . rawurlencode($_GET["continue"]);
		SERIA_Base::redirectTo($url);
		die();
	}

	$contents = "<h1>" . _t("Forgot my password") . "</h1>\n";
	$contents .= "<form method='post' action=\"forgotten.php?r=0";
	if (isset($_GET["continue"]))
		$contents .= "&continue=" . rawurlencode($_GET["continue"]);
	$contents .= "\">";

	SERIA_Template::focusTo("key");

	$contents .= "<div>\n";
	$contents .= "\t<div style='clear: right;'>\n";
	$contents .= "\t\t" . _t("Please enter your email address or username") . "\n";
	$contents .= "\t</div>\n";
	$contents .= "\t<div style='clear: right;'>\n";
	$contents .= "\t\t<input type='text' name='key' value='' style='width: 250px;'>\n";
	$contents .= "\t</div>\n";
	$contents .= "\t<button type='submit'>" . _t("Send password") . "</button>\n";
	$contents .= "</div>\n";

	$contents .= "WARNING: SKELETON\n";
	
	$gui->contents($contents);

        $gui->topMenu(_t("Site"), "location.href=\"" . SERIA_HTTP_ROOT . "\";");
        $gui->topMenu(_t("Exit"), "location.href=\"" . SERIA_HTTP_ROOT . "\";");

	if(isset($_GET["continue"]))
	        $gui->topMenu(_t("Back"), "location.href=\"" . $_GET["continue"] . "\";");

	echo $gui->output();
?>
