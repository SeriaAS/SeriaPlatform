<?php
	require_once(dirname(__FILE__)."/../../main.php");

	$gui = new SERIA_Gui(_t("Register"));

	$gui->exitButton("Back", "history.go(-1)");
        $errorMessage='';
        $errors=array();
        $errors["firstName"]="";
        $errors["lastName"]="";
        $errors["displayname"]="";
        $errors["email"]="";
        $errors["password"]="";
        $errors["username"]="";
  
	if(isset($_POST["username"]))
	{
            
            try
            {
               
		$result = SERIA_User::create( $_POST["firstname"],$_POST["lastname"],$_POST["dispalyname"],$_POST["username"],$_POST["password"],$_POST["email"],false);
		
		{
			if(isset($_GET["continue"]))
			{
				header("Location: "."login.php");
				die();
			}
			else
			{
				header("Location: "."register.php");
				die();
			}
		}
            }
            catch(SERIA_ValidationException $ex)
            {
		$errorMessage=$ex->getMessage();
               $errors=$ex->getValidationMessages();
		   
	
		$registerFailed=true;
            }
            catch(SERIA_Exception $ex)
            {
              $errorMessage=$ex->getMessage();  
               $registerFailed=true;
            }
            
	}

	$contents = "<h1>"._t("Please register")."</h1>";
        
	if($registerFailed)
		$contents .= "<p class='error'>"._t("".$errorMessage)."</p>";

	$contents .= "<form method='post' action=\"register.php?continue=".rawurlencode($_GET["continue"])."\">
<table  >
	<tbody>
		<tr>
			<th>"._t("First Name:")."</th>
			<td valign=top><input type='text' name='firstname' value=\"".htmlspecialchars($_POST["firstname"])."\">".($errors["firstName"])."</td>
		</tr>
                <tr>
			<th>"._t("Last Name:")."</th>
			<td><input type='text' name='lastname' value=\"".htmlspecialchars($_POST["lastname"])."\">".($errors["lastName"])."</td>
		</tr>
                <tr>
			<th>"._t("Email:")."</th>
			<td><input type='text' name='email' value=\"".htmlspecialchars($_POST["email"])."\">".($errors["email"])."</td>
		</tr>
                   <tr>
			<th>"._t("Display Name:")."</th>
			<td><input type='text' name='dispalyname' value=\"".htmlspecialchars($_POST["dispalyname"])."\">".($errors["displayName"])."</td>
		</tr>
                <tr>
			<th>"._t("Username:")."</th>
			<td><input type='text' name='username' value=\"".htmlspecialchars($_POST["username"])."\">".($errors["userName"])."</td>
		</tr>
		<tr>
			<th>"._t("Password:")."</th>
			<td><input type='password' name='password'>".($errors["password"])."</td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<td colspan='2'>
				<input type='submit' value=\""._t("Register")."\">
			</td>
		</tr>
	</tfoot>
</table>
</form>";

	

	$gui->contents($contents);

	echo $gui->output();
