<?php
	require("../../main.php");

	SERIA_Template::disable();

	$db = SERIA_Base::db();

	try {
		$db->exec("DROP TABLE frode_tester");
		$db->exec("CREATE TABLE frode_tester (id integer primary key, qn varchar(100), ts datetime, message blob, index(qn, ts))");
	} catch (PDOException $e) {
	}
	$desc = $db->query("DESC frode_tester")->fetchAll(PDO::FETCH_ASSOC);
	var_dump($desc);
	var_dump($db->exec("DELETE FROM frode_tester"));

	$blobdata = '
qeroqjweproiqwj erqwehri qwuegyf iqwuefgy qiwufgy qwieufyg qwieufgyq wie
qeroqjweproiqwj erqwehri qwuegyf iqwuefgy qiwufgy qwieufyg qwieufgyq wie
qeroqjweproiqwj erqwehri qwuegyf iqwuefgy qiwufgy qwieufyg qwieufgyq wie
qeroqjweproiqwj erqwehri qwuegyf iqwuefgy qiwufgy qwieufyg qwieufgyq wie
qeroqjweproiqwj erqwehri qwuegyf iqwuefgy qiwufgy qwieufyg qwieufgyq wie
qeroqjweproiqwj erqwehri qwuegyf iqwuefgy qiwufgy qwieufyg qwieufgyq wie
qeroqjweproiqwj erqwehri qwuegyf iqwuefgy qiwufgy qwieufyg qwieufgyq wie
';
	$t = microtime(true);
	for($i = 0; $i < 1000000; $i++)
	{
		$db->exec("INSERT INTO frode_tester VALUES ($i, 'queue".($i%10)."', now(), '$blobdata')", null, true);
		if($i % 10000 == 0)
		{
			echo "10000 rows inserted in ".(microtime(true)-$t)." seconds\n";
			$t = microtime(true);
			ob_end_flush();
		}
	}

