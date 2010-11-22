<?php
	require('../../main.php');
	require('S.class.php');
	require('SDB.class.php');
	require('SData.class.php');
	require('SDBData.class.php');
	require('SFluent.class.php');
	require('SFluentObject.class.php');
	require('SFluentQuery.class.php');
	require("SFluentGrid.class.php");
	require("SException.class.php");
	require('CD.class.php');
//	require('Customer.class.php');
//	require('Logg.class.php');

	$a = SFluent::all('CD');

//	$a = new SDBData('seria_customers');
//	$a->where('name NOT LIKE "w%"');
//	var_dump($a);
	foreach($a as $k => $v)
	{
		echo "$k = ";
		var_dump($v);
		echo "\n";
	}
die("2");

	foreach(SFluent::all('CD')->order('title desc')->where('title LIKE ?', array('B%')) as $cd)
		echo $cd->get("title")."\n";

/*

	$cd = new CD;
	$cd->set('title', 'Best of Frode 2');
	$cd->set('gender', 'm');
	SFluent::save($cd);
*/
