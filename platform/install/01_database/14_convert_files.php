<?php
/**
 * Fix seria_files table to fit new database model
 */
	$db = SERIA_BASE::db();
	$rows = SERIA_BASE::db()->query("SELECT * from " . SERIA_PREFIX . "_files")->fetchAll(PDO::FETCH_ASSOC);
	foreach($rows as $row)
	{
		if($row["url"])
		{
			if(strpos($row["url"], "://")!==false)
			{ // this is an url
				$p = parse_url($row["url"]);
				$filename = basename($p["path"]);
				$db->exec("UPDATE ".SERIA_PREFIX."_files SET filename=".$db->quote($filename)." WHERE id=".$row["id"]);
			}
			else if($row["url"])
			{
				$filename = basename($row["url"]);			
				$db->exec("UPDATE ".SERIA_PREFIX."_files SET url=NULL, filename=".$db->quote($filename)." WHERE id=".$row["id"]);
			}
		}
	}
	
?>
