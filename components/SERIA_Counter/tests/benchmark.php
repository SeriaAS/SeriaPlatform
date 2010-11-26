<?php
	require("../../../main.php");

//	SERIA_Counter::commitMemory();
//	die();

	$t = microtime(true);
		$counter = new SERIA_Counter("cbenchmark");
	for($i = 0; $i < 1000; $i++)
	{
		$counter->add(array("testa1","testa2"), 1);
	}
		$counter = NULL;
	echo "INSERT TIME: ".(microtime(true)-$t)."\n";
	$counter = new SERIA_Counter("cbenchmark");
	var_dump($counter->get(array("testa1","testa2")));

