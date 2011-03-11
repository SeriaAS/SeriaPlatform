<?php
	require("../../../main.php");

	file_put_contents('text-file.txt', 'some text');

	$blob = SERIA_Blob::createFromFile('text-file.txt');

	$blob->delete();

