<?php
	$ftpServers = SERIA_Base::db()->query("SELECT * FROM ".SERIA_PREFIX."_ftp_servers")->fetchAll(PDO::FETCH_ASSOC);
	$ftp = array();
	foreach($ftpServers as $ftpServer)
	{
		$ftp[$ftpServer["id"]] = $ftpServer;
	}

        $files = SERIA_Base::db()->query("SELECT * FROM ".SERIA_PREFIX."_files")->fetchAll(PDO::FETCH_ASSOC);

	foreach($files as $file)
	{
		if($file["ftp_server_id"] && empty($file["url"]))
		{
			if(!isset($ftp[$file["ftp_server_id"]]))
				throw new SERIA_Exception("Unknown FTP-server ID");
			$sql = "UPDATE ".SERIA_PREFIX."_files SET url=".SERIA_Base::db()->quote($ftp[$file["ftp_server_id"]]["http_root_url"]."/".$file["filename"])." WHERE id=".$file["id"];
			SERIA_Base::db()->exec($sql);
		}
	}
