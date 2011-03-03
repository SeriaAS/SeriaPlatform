<?php

	/**
	*	Seria Live for holding webcasts
	*	@author Joakim Eide
	*	@version 1.0
	*	@package SeriaLive
	*/
	class liveManifest {
		const SERIAL = 3;
		const NAME = 'serialive';

		public static $classPaths = array(
			'classes/*.class.php', /**/
		);

		public static $database = array(
			"creates" => array(
				"CREATE TABLE {blockusage} (id INT NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), presentationId INT,year INT, month INT, day INT, hour INT,viewers INT, customerId INT, totalBlocks INT) ENGINE=InnoDB DEFAULT CHARSET=utf8",
			),
		);
	}

	function liveInit()
	{
		SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, 'serialiveGui');
		SERIA_Hooks::listen(SERIA_PlatformHooks::MAINTAIN, 'serialiveMaintain');
		SERIA_Hooks::listen(SERIA_PlatformHooks::ROUTER_EMBED, 'serialiveRouterEmbed');
		SERIA_Hooks::listen(SERIA_MAINTAIN_1_HOUR_HOOK, 'serialiveAggregateBlockUsage');
	}

	function serialiveRouterEmbed($router)
	{
		$router->addRoute('serialive', 'front', array('SERIA_LivePages', 'front'), 'live');
	}

	function serialiveGui($gui)
	{
		//$gui->addMenuItem('serialive', _t("SeriaLIVE"), _t("Create a new live presentation"), SERIA_HTTP_ROOT.'/seria/apps/live/', SERIA_HTTP_ROOT.'/seria/apps/live/icon.png', 0);
		$gui->addMenuItem('serialive/presentations', _t("Presentations"), _t("Manage your presentations"), SERIA_HTTP_ROOT.'/seria/apps/live/presentations/');
		$gui->addMenuItem('serialive/presentationcompanies', _t("Companies"), _t("Edit companies"), SERIA_HTTP_ROOT.'/seria/apps/live/presentationcompanies/');
		$gui->addMenuItem('serialive/presentationcompanies/edit', _t("Add company"), _t("Create new company"), SERIA_HTTP_ROOT.'/seria/apps/live/presentationcompanies/edit.php');

		if(SERIA_Base::isAdministrator()) {
			$gui->addMenuItem('serialive/producers', _t("Customers"), _t("Edit customers"), SERIA_HTTP_ROOT.'/seria/apps/live/producers/');
			$gui->addMenuItem('serialive/producers/edit', _t("Add customer"), _t("Create new customer"), SERIA_HTTP_ROOT.'/seria/apps/live/producers/edit.php');
			$gui->addMenuItem('serialive/producersusers', _t("Live users"), _t("Edit live users"), SERIA_HTTP_ROOT.'/seria/apps/live/producersusers/');
			$gui->addMenuItem('serialive/producersusers/edit', _t("Add live user"), _t("Create new live user"), SERIA_HTTP_ROOT.'/seria/apps/live/producersusers/edit.php');
		}
	}

	function serialiveMaintain()
	{

	}

	function serialiveAggregateBlockUsage()
	{

		$aq = new SERIA_ArticleQuery('SERIA_Live');

		$aq->where('calculatedBlockHours=false');

		$articles = $aq->page(0,100000);

		$counter = new SERIA_Counter("SERIA_LiveArticleBlockHours");

		foreach($articles as $article) {
			if($article->isFinished()) {
				// Traverse all the hours the presentation has been accessed and blockhours have been counted (lets use a 24hr window)
				for($i=0;$i<24;$i++) {
					$blockCount = $counter->get(
						array("billable_webcastviewerhour_".$i."{".$article->get("id")."}")
					);
die(date('Y', $article->get("date")));
/*					$success = SERIA_Base::db()->exec('INSERT INTO {blockusage} VALUES(:presentationId, :customerId, :year, :month, :day, :hour)',array(
						'presentationId' => $article->get("id"),
						'customerId' => $article->get("customer_id"),
						'year' => date('Y', $article->get("date"),
						'month' => date('m', $article->get("date"),
						'day' => date('d', $article->get("date"),
						'hour' => $i+date('h', $article->get("date"), // Adding $i, ie: pres starts at 12:00, first pass h representents 0+12, seconds 1+12 (
						'viewers' => array_shift(array_values($blockCount)),
						'totalBlocks' => int(array_shift(array_values($blockCount))/500)+1
					));
*/

					if($success) {
						$article->writable();
						$article->set('calculatedBlockHours', 1);
						SERIA_Base::elevateUser(array($article, 'save'));
					}
				}
			}
			// Calculate block hours!
		}


/*
		for($ts = $lastTimeIRan; $ts += 3600)
		{
			list($y, $m, $d, $h) = explode("-", date("Y-m-d-h",$ts));

			// save "lastTimeIRan"
		}

		$counter = new SERIA_Counter('SeriaLiveBlocks');

		// hvor mange blokker ble totalt konsumert forrige time

		if(!$blocks = $counter->get('total_blockViews_2011-01_1764'))
			generateBlocks(1764, 2011, 01);

                $counter->add(array(
                                'total',
                                'total_Year_'.date("Y"),
                                'total_YearMonth_'.date("Y-m"),
                                'total_YearMonthDate_'.date("Y-m-d"),
                                'total_Year_Customer_'.date("Y").'_{'.$article->get("customer_id").'}',
                                'total_YearMonth_Customer_'.date("Y-m").'_{'.$article->get("customer_id").'}',
                                'total_YearMonthDate_Customer_'.date("Y-m-d").'_{'.$article->get("customer_id").'}',
                                'total_Hour_'.date("H"),
                                'billable_webcastviewerhour_'.$blockNumber."{".$article->get("id")."}",
                                'blockhours', 'blocknumber_'.$blockNumber
                        ), 1
                );
*/
	}
