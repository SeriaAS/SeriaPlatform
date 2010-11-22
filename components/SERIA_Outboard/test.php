<?php
	require("../../main.php");


	$comments = SERIA_Comment::getComments($user);

	var_dump($comments);
