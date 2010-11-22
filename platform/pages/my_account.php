<?php
	require_once(dirname(__FILE__)."/../../main.php");
	$gui = new SERIA_Gui(_t("User Details"));

        SERIA_Base::pageRequires("login");
	SERIA_Base::pageRequires("mainmenu");

	$gui->exitButton("Back", "history.go(-1)");
        $message='';

	// user that should be edited is stored in $user object.

        $user=SERIA_Base::user();

	$contents = "<h1>"._t("My account")."</h1>";

	if(isset($_POST["firstName"]))
	{
		try
		{
			$user->set("firstName", $_POST["firstName"]);
			$user->set("lastName", $_POST["lastName"]);
			$user->set("email", $_POST["email"]);
			$user->set("displayName", $_POST["displayName"]);
			$user->set("username", $_POST["username"]);
			$user->set("password", $_POST["password"]);
			$user->validate();
			$user->save();
			header("Location: ".SERIA_HTTP_ROOT."/seria/platform/pages/my_account.php?message=saved");
			die();
		}
		catch (SERIA_ValidationException $e)
		{
			$errors = $e->getValidationMessages();
			$contents .= "<p class='error'>"._t("Validation errors.")."</p>";
		}
	}
	else if($_GET["message"]=="saved")
	{
		$contents .= "<p class='ok'>"._t("Saved ok.")."</p>";
	}

	$contents .= "<form method='post' action=\"".SERIA_HTTP_ROOT."/seria/platform/pages/my_account.php\">
<table>
	<tbody>
		<tr>
			<th>"._t("First Name:")."</th>
			<td valign=top><input type='text' name='firstName' value=\"".htmlspecialchars($user->get("firstName"))."\">".($errors["firstName"])."</td>
		</tr>
                <tr>
			<th>"._t("Last Name:")."</th>
			<td><input type='text' name='lastName' value=\"".htmlspecialchars($user->get("lastName"))."\">".($errors["lastName"])."</td>
		</tr>
                <tr>
			<th >"._t("Email:")."</th>
			<td><input type='text' name='email' value=\"".htmlspecialchars($user->get("email"))."\">".($errors["email"])."</td>
		</tr>
                   <tr>
			<th>"._t("Display Name:")."</th>
			<td><input type='text' name='displayName' value=\"".htmlspecialchars($user->get("displayName"))."\">".($errors["displayName"])."</td>
		</tr>
                <tr>
			<th>"._t("Username:")."</th>
			<td><input type='text' name='username' value=\"".htmlspecialchars($user->get("username"))."\">".($errors["username"])."</td>
		</tr>
		<tr>
			<th>"._t("Password:")."</th>
			<td><input type='password' name='password' value=\"".htmlspecialchars($user->get("password"))."\">".($errors["password"])."</td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<td align='center' colspan='2'>
				<input type='submit' value=\""._t("Update")."\">
			</td>
                       
		</tr>
	</tfoot>
</table>
</form>";

	

	$gui->contents($contents);

	echo $gui->output();
