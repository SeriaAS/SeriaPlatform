<?php
/**
 * Fix for seria_files regarding content type.
 */
	$db = SERIA_BASE::db();
	$rows = SERIA_BASE::db()->query("SELECT * FROM ".SERIA_PREFIX."_files")->fetchAll(PDO::FETCH_ASSOC);
	foreach($rows as $row)
	{
		$filename = $row["filename"];
                $ext = SERIA_Lib::getContentType($filename);
		$db->exec("UPDATE ".SERIA_PREFIX."_files SET content_type=".$db->quote($ext)." WHERE id=".$db->quote($row["id"])."");
	}
	
?>
