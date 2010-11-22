<?php
	require("../main.php");
	SERIA_Base::pageRequires("admin");
	$gui = new SERIA_Gui("Database admin");
	$db = SERIA_Base::db();

	$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN,0);
	$leftColumn = "<h1>"._t("Tables in database")."</h1><ul>";
	foreach($tables as $table)
		$leftColumn .= "<li><a href='database.php?table=".rawurlencode($table)."'>".$table."</a></li>";
	$leftColumn .= "</ul>";

	if(!empty($_GET["table"]))
	{
		$table = safe_mysql_tablename($_GET["table"]);
		$contents = "<h1>".htmlspecialchars($table)."</h1>";

		$columnRS = $db->query("DESC ".$table)->fetchAll(PDO::FETCH_ASSOC);
		$columns = "<div style='width: 200px; height: 400px; overflow:auto'>".dbresult_to_string($columnRS)."</div>";

		$leftColumn .= "<h2>"._t("Stats")."</h2>".$columns;
	}
	else
		$contents = "<h1>"._t("Database manager")."</h1>";

	$contents .= "<h2>"._t("Execute SQL")."</h2>".
		"<form method='post' action='database.php".(!empty($table)?"?table=".rawurlencode($table):"")."'>";

	$contents .= "<p><textarea name='sql' style='width: 100%; height: 60px;'>".(!empty($_POST["sql"])?htmlspecialchars($_POST["sql"]):"")."</textarea></p>";
	$contents .= "<p><input type='submit' value=\""._t("Execute SQL")."\"></p>";

	$contents .= "</form>";

	if(!empty($_POST["sql"]))
	{
		$contents .= "<h2>"._t("Result")."</h2>";
		$contents .= dbresult_to_string($db->query($_POST["sql"])->fetchAll(PDO::FETCH_ASSOC));
	}

	$gui->sectionMenu($leftColumn);
	$gui->contents($contents);

	echo $gui->output();

	function safe_mysql_tablename($table)
	{
		return str_replace(array("\n"," ","\"","'",":","\0"),array("","","","","",""),$table);
	}

	function dbresult_to_string($result)
	{
		$res = "<table class='grid'><thead><tr>";
		foreach($result[0] as $header => $v)
			$res .= "<th>".htmlspecialchars($header)."</th>";
		$res .= "</tr></thead><tbody>";

		foreach($result as $row)
		{
			$res .= "<tr>";
			foreach($row as $val)
				$res .= "<td>".htmlspecialchars($val)."</td>";
			$res .= "</tr>";
		}

		$res .= "</tbody></table>";
		return $res;
	}
