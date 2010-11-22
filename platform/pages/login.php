<?php
	require_once(dirname(__FILE__)."/../../main.php");

	/*
	 * Throw a hook that a login application can catch
	 */
	if (SERIA_Hooks::dispatchToFirst('login'))
		return; /* Login was handled (return false to use local login) */

	if(defined('SERIA_LOGIN_URL'))
	{
		$url = new SERIA_Url(SERIA_LOGIN_URL);
		$url->setParam('continue', $_GET['continue']);
		header('Location: '.$url);
		die();
	}


	SERIA_Base::pageRequires("javascript");
	$gui = new SERIA_Gui(_t("Login"));
	$gui->activeMenuItem('controlpanel');
	
	$gui->exitButton("Back", "history.go(-1)");

	$loginFailed = false;

	if(isset($_POST["username"]))
	{
		$result = SERIA_User::login($_POST["username"], $_POST["password"]);
		$user = SERIA_Base::user();
		if(!$result)
		{
			$loginFailed = true;
		}
		else
		{
			if (!$user->get('password_change_required')) {
				if(!empty($_GET['continue'])) {
					$continue = $_GET['continue'];

					// Prevent escaping Host: header by URL
					$continue = str_replace(array("\r", "\n", "\0"), array('', '', ''), $continue);
					$url = $continue;

				} else {
					$url = SERIA_HTTP_ROOT;
				}
				SERIA_Base::redirectTo($url);
				die();
			} else {
				SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/platform/pages/changepassword.php?required=yes&continue='.(isset($_GET["continue"]) ? $_GET["continue"] : SERIA_HTTP_ROOT));
				die();
			}
		}
	}

	$contents = "<h1>"._t("Please login")."</h1>";

	$focusTo = "username";
	if($_POST)
		$username = $_POST["username"];
	else
		$username = "";

	if (isset($_GET["key"])) {
		$contents .= "<p>" . _t("Password has been sent to ") . htmlspecialchars($_GET["key"]) . ". ";
		$contents .= _t("If you don't receive an email, please check the spelling of your email address or username.");
		$contents .= "</p>\n";
		$username = $_GET["key"];
		$focusTo = "password";
	}

	if($loginFailed)
		$contents .= "<p class='error'>"._t("Incorrect username or password.")."</p>";

	SERIA_Template::focusTo($focusTo);

	$contents .= "<form method='post' action=\"".SERIA_HTTP_ROOT."/seria/platform/pages/login.php?continue=".rawurlencode($_GET["continue"])."\">
<table>
	<tbody>
		<tr>
			<th>"._t("Username:")."</th>
			<td><input type='text' id='username' name='username' value=\"".htmlspecialchars($username)."\"></td>
		</tr>
		<tr>
			<th>"._t("Password:")."</th>
			<td><input type='password' id='password' name='password'></td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
";
		if (defined("SERIA_FORGOT_PASSWORD_ENABLED") && SERIA_FORGOT_PASSWORD_ENABLED)
			$contents .= "
			<td>
				<input type='submit' value=\""._t("Login")."\">
			</td>
			<td>
				<a href='forgotten.php'>"._t("Forgot password")."</a>
			</td>
";
		else
			$contents .= "
			<td colspan='2'>
				<input type='submit' value=\""._t("Login")."\">
			</td>
";
		$contents .= "		
		</tr>
	</tfoot>
</table>
</form>";

	$maxUploadSize = round(SERIA_Lib::getMaxUploadSize() / (1024*1024), 1);
	if(SERIA_INSTALL || SERIA_DEBUG)
	{
		
		$contents .= "<div style='border: 2px solid red; padding: 20px;'>";
		$contents .= "<h1>"._t("Installation mode")."</h1>";
		$contents .= "<p>"._t("A user with username 'admin' and password 'admin123' was created by default. You should change this user password as soon as possible. To remove this message please disable SERIA_INSTALL or SERIA_DEBUG in '_config.php'!")."</p>";
		$contents .= "<h2>"._t("System information")."</h2>";
		$contents .= "<ul>
				<li>"._t("Maximum upload size is approximately <strong>%MAX%</strong> MB", array("MAX" => $maxUploadSize))."</li>
				<li>"._t("PHP version is <strong>%PHPVERSION%</strong>", array("PHPVERSION" => phpversion()))."</li>
			</ul>";
		$contents .= "</div>";
	}
	else
	{
		$contents .= "<div style='border: 2px solid #ddd; padding: 20px;'>";
		$contents .= "<h1>"._t("System information")."</h1>";
		$contents .= "<ul>
				<li>"._t("Maximum upload size is approximately <strong>%MAX%</strong> MB", array("MAX" => $maxUploadSize))."</li>
			</ul>";
		$contents .= "</div>";
	}

	$gui->contents($contents);

        $gui->topMenu(_t("Site"), "location.href=\"" . SERIA_HTTP_ROOT . "\";");
        $gui->topMenu(_t("Exit"), "location.href=\"" . SERIA_HTTP_ROOT . "\";");

	echo $gui->output();
