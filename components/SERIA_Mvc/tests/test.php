<?php
	require("../../../main.php");
	SERIA_Base::addClassPath(dirname(__FILE__)."/*.class.php");

	SERIA_Meta::help('CD');

	$cds = SERIA_Meta::all('CD')->where('title LIKE :title', array('title'=>'Frode'));

	foreach($cds as $o)
	{
		var_dump($o);
	}
