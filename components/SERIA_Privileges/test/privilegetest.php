<?php
	require("../../../main.php");
	class SERIA_User {
		public function get($v)
		{
			return 1;
		}

		public function getObjectId() { return array('SERIA_User','1'); }
	}

	$object = new SERIA_User();


	$privileges = new SERIA_Privileges($object, new SERIA_User());

	var_dump($privileges->hasPrivilege('test'));
	var_dump($privileges->grantPrivilege('test'));
	var_dump($privileges->hasPrivilege('test'));
//	var_dump($privileges->revokePrivilege('test'));
	var_dump($privileges->hasPrivilege('test'));
	

