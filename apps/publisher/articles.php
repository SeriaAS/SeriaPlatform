<?php
	/**
	*	Seria TV Welcome page
	*/
	require_once(dirname(__FILE__)."/common.php");
	if(isset($_GET['id']))
		$gui->activeMenuItem('publisher/articles/edit');
	else
		$gui->activeMenuItem("publisher/articles");
	SERIA_Base::pageRequires("login");
	SERIA_Base::viewMode("admin");
	$gui->exitButton(_t("Logout"), "location.href='./logout.php';");

	// Denne skal lastes på hele platformen
	SERIA_ScriptLoader::loadScript("jQuery-flashembed");


	$optionsMenu = '';
	if(SERIA_Base::hasRight("create_article")) {
		$urlParts = array();
		foreach($_GET as $key => $value)
			if($key[0]=="q")
				$urlParts[$key] = $value;
		$urlParts["id"] = "";
		
		$optionsMenu .= '<div><a href="'.htmlspecialchars(SERIA_Base::url($urlParts)).'">'._t("Create article").'</a></div>';
	}
	if ($optionsMenu)
		$gui->addBlock(_t('Options'), $optionsMenu);
	
	if(!isset($_GET["qt"])) $_GET["qt"] = "";
	if(!isset($_GET["qtitle"])) $_GET["qtitle"] = "";
	if(!isset($_GET["qauthor"])) $_GET["qauthor"] = "";
	if(!isset($_GET["qcat"])) $_GET["qcat"] = "";
	if(!isset($_GET["qtype"])) $_GET["qtype"] = "";
	if(!isset($_GET["qstatus"])) $_GET["qstatus"] = "";

	$sectionMenu = "
		<form method='get'>
			<div style='margin-bottom: 10px'>
				"._t("Search:")."<br>
				<input type='text' name='qt' style='width:100%' value=\"".htmlspecialchars($_GET["qt"])."\">
			</div>
			<div style='margin-bottom: 10px'>
				"._t("Title:")."<br>
				<input type='text' name='qtitle' style='width:100%' value=\"".htmlspecialchars($_GET["qtitle"])."\">
			</div>
			<div style='margin-bottom: 10px'>
				"._t("Author:")."<br>
				<select name='qauthor' style='width:100%'>
					<option value=''></option>";

	$users = SERIA_User::getUsers();
	foreach($users as $u)
		$sectionMenu .= "
					<option ".($_GET["qauthor"]==$u->get("id")?"selected='1'":"")." value=\"".$u->get("id")."\">".htmlspecialchars($u->get("displayName"))."</option>";

	$sectionMenu .= "
				</select>
			</div>
			<div style='margin-bottom: 10px'>
				"._t("Only in category:")."<br>
				<select name='qcat' style='width:100%'>".buildCategorySelect(SERIA_ArticleCategory::getRoot(), 0, intval($_GET["qcat"]))."</select>
			</div>
			<div style='margin-bottom: 10px'>
				"._t("Search by type:")."<br>
				<select name='qtype' style='width:100%'><option value=''></option>";
			foreach(SERIA_Article::getAvailableArticleTypes() as $articleType) {
				$sectionMenu .= "<option value='".$articleType."' ".($_GET["qtype"]==$articleType ? " selected " : "").">".$articleType."</option>";
			}
	$sectionMenu.="</select>
			</div>
			<div style='margin-bottom: 10px'>
				"._t("Published status:")."<br>
				<input type='radio' name='qstatus' value='' ".(!isset($_GET["qstatus"])||$_GET["qstatus"]==""?"checked='1'":"")." id='qPublishedNoFilter'><label for='qPublishedNoFilter'>"._t("Ignore")."</label><br>
				<input type='radio' name='qstatus' value='1' ".($_GET["qstatus"]=="1"?"checked='1'":"")." id='qPublishedYes'><label for='qPublishedYes'>"._t("Only published")."</label><br>
				<input type='radio' name='qstatus' value='0' ".($_GET["qstatus"]=="0"?"checked='1'":"")." id='qPublishedNo'><label for='qPublishedNo'>"._t("Only not published")."</label><br>
			</div>
			<div>
				<input type='submit' value=\""._t("Search")."\">
			</div>
		</form>";


	$gui->addBlock(_t('Search'), $sectionMenu);

	if(!isset($_GET["id"]))
	{
		$articles = new SERIA_ArticleQuery();

		

		if(!empty($_GET["qt"]))
			$articles->where($_GET["qt"]);

		if(!empty($_GET["qtitle"]))
			$articles->where($_GET["qtitle"], "@title:");

		if(!empty($_GET["qtype"]))
		{
			$articles->where($_GET["qtype"], "@type:");
		}
		else 
		{
			$articles->excludeType('SERIA_File');
			$articles->excludeType('SERIA_Image');
		}

		if(!empty($_GET["qauthor"]))
			$articles->where($_GET["qauthor"], "@author_id:");

		if(!empty($_GET["qcat"]))
			$articles->where($_GET["qcat"], "@category_id:");

		if(!empty($_GET["qstatus"]))
		{
			if($_GET["qstatus"]==="")
				;
			else if($_GET["qstatus"])
				$articles->isPublished();
			else
				$articles->isNotPublished();
		}

		$articles->order("altered_date DESC");
		$pageLength = 50;
		$page = isset($_GET['qpage']) ? intval($_GET['qpage']) : 0;
		$res = $articles->page($page * $pageLength,$pageLength = 50);
		$count = ceil($articles->count() / $pageLength);

		$pages = array();
		for($i = 0; $i < $count; $i++)
		{
			$pages[] = '<a '.($page==$i?'style="color: red" ':'').'href="'.SERIA_Base::url(array("qt" => $_GET["qt"],"qtype" => $_GET["qtype"], "qtitle" => $_GET["qtitle"], "qauthor" => $_GET["qauthor"], "qcat" => $_GET["qcat"], "qstatus" => $_GET["qstatus"], 'qpage' => $i)).'">'.($i+1).'</a>';
		}

		$contents = "
		<h1 class='legend'>"._t("Articles")."</h1>";

		$contents .= "<div>"._t('Page %page%: %pages%', array('page' => $page+1, 'pages' => implode(" ", $pages)))."</div>";

		$contents .= "<table class='grid' style='width: 100%'>
		<thead>
			<tr>
				<th style='width: 16px'>&nbsp;</th>
				<th style='width: 50px;text-align: right;padding-right:10px;'>"._t("#")."</th>
				<th>"._t("Title")."</th>
				<th style='width: 150px;'>"._t("Author")."</th>
				<th style='width: 50px;'>"._t("Type")."</th>
				<th style='width: 130px;'>"._t("Created")."</th>
				<th style='width: 130px;'>"._t("Altered")."</th>
				<th style='width: 130px;'>" . _t('Status') . "</th>
			</tr>
		</thead><tbody>";

		foreach($res as $article)
		{
			$abstract = $article->getAbstract();

			$status = _t('Not published');
			if ($article->get('is_published')) {
				$status = _t('Published');
			} elseif ($article->get('pending_publish')) {
				$status = _t('Publishing when ready');
			}

			$author = $article->getAuthor();
			$contents .= "
			<tr ".$article->getContextMenu()." class='highlight' onclick=\"location.href='".SERIA_Base::url(array("qt" => $_GET["qt"],"qtype" => $_GET["qtype"], "qtitle" => $_GET["qtitle"], "qauthor" => $_GET["qauthor"], "qcat" => $_GET["qcat"], "qstatus" => $_GET["qstatus"], "id" => $article->get("id")))."'\">
				<td style='text-align:center;vertical-align:middle'>x</td>
				<td style='text-align:right;padding-right:10px;'>".$article->get("id")."</td>
				<td>".htmlspecialchars($abstract["title"])."</td>
				<td ".($author !== false ? $author->getContextMenu() : '').">".$article->get("author_name")."</td>
				<td>".$article->get("type")."</td>
				<td>"._datetime($article->get("created_date"))."</td>
				<td>".($article->get("created_date")!=$article->get("altered_date")?_datetime($article->get("altered_date")):"")."</td>
				<td>" . $status . "</td>
			</tr>";
		}

		$contents .= "
		</tbody></table>";
		$gui->contents($contents);
	}
	else if(!$_GET["id"] && !$_GET["articleType"]) 
	{ // User choose article type to create
		$types = SERIA_Article::getAvailableArticleTypes();
		// Forward automaticly if there are only one article type
		if(sizeof($types) == 1) {
			header("Location: ".SERIA_Base::url(array("id"=>"","articleType"=>$types[0])));
			die();
		}
		$contents = "<h1 class='legend'>"._t("Choose article type")."</h1>";
		foreach($types as $type) 
		{
			$className = $type."Article";
			eval('
				$name = '.$className.'::getTypeName();
				$description = '.$className.'::getTypeDescription();
			');

			$contents .= 
				"<h2><a href='".SERIA_Base::url(array("id"=>"","articleType"=>$type))."'>".$name."</a></h2>".
				"<p>$description</p>";
		}
		$gui->contents($contents);
	}
	else
	{ // view/edit article

		$gui->exitButton(_t("< Article list"), "location.href='".SERIA_Base::url(array("qt" => $_GET["qt"],"qtype" => $_GET["qtype"], "qtitle" => $_GET["qtitle"], "qauthor" => $_GET["qauthor"], "qcat" => $_GET["qcat"], "qstatus" => $_GET["qstatus"]))."'");

		if($_GET["id"])
		{
			$article = SERIA_Article::createObjectFromId($_GET["id"]);
		}
		else if(isset($_GET["id"]) && isset($_GET["articleType"])) 
		{
			$article = SERIA_Article::createObject($_GET["articleType"]);
		}
		else 
		{
			die("Select article type");
		}

		$currentArticleHash = $article->contentHash();

		try
		{
			$article->writable();
			$notEditable = false;
		}
		catch(SERIA_Exception $e)
		{
			switch($e->getCode())
			{
				case 1 : $notEditable = _t("This article is published. You do not have privileges to edit published articles."); break;
				case 2 : $notEditable = _t("This article is owned by %OWNER%. You do not have privileges to edit other users articles.", array("OWNER" => $article->getAuthor()->get("display_name"))); break;
				default : $notEditable = _t("You can't edit this article at the moment. I was unable to make it writable.");
			}
		}

		$errors = array();

		if(sizeof($_POST))
		{ // receive data and set all values that are common for the forms, and then call receiveData and validate
			// common fields for all article types:
			if($_POST["delete"]) {
				SERIA_Template::disable();
				$article->delete();
				SERIA_Base::redirectTo("./articles.php");
			}
			
			$article->set("title", $_POST["title"]);
			
			if ($_POST['pending_publish']) {
				$article->publish();
			} else {
				$article->unpublish();
			}
			
			$article->set("author_name", $_POST["author_name"]);
			$article->set("author_email", $_POST["author_email"]);
			$article->set("notes", $_POST["notes"]);
			
			if(isset($_POST["categories"]) && is_array($_POST["categories"]))
			{
				$categoryIds = $_POST["categories"];
			} else {
				$categoryIds = array();
			}
				
			// Get list of categories the article is in
			$inCategories = $article->getCategories();
			
			// Add to categories selected
			foreach($categoryIds as $category_id) {
				$alreadyInCategory = false;
			       foreach ($inCategories as $category) {
			       	if ($category->get('id') == $category_id) {
			       		$alreadyInCategory = true;
			       	}
				}
				
				if (!$alreadyInCategory) {
					$categoryToAdd = SERIA_ArticleCategory::createObject($category_id);
					$article->addToCategory($categoryToAdd);
				}
			}
			
			// Remove from categories not selected
			foreach ($inCategories as $inCategory) {
				$inCategory_id = $inCategory->get('id');
				if (!in_array($inCategory_id, $categoryIds)) {
					$article->removeFromCategory($inCategory);
				}
			}
			
			$article->receiveData($_POST, "articleEditor");
			
			try
			{
				if(isset($_GET["id"]) && $_GET["id"] && $_POST["article_hash"] != $currentArticleHash)
					throw new SERIA_ValidationException(_t("Article was changed from somewhere else, and can't be updated. Click <a href='%URL%'>here</a> to reload the article.", array("URL" => $_SERVER["REQUEST_URI"])), array());

				$article->validateData();
				$article->updatePublishStatus();
				$article->save();
				SERIA_HtmlFlash::notice(_t("The article was successfully saved"));
				if($_GET["continue"])
				{
					die("<html><head></head><body><script type='text/javascript'>top.location.href=\"".htmlspecialchars($_GET["continue"])."\";</script></body></html>");
				}
				else
				{
					header("Location: ".SERIA_Base::url(array("qt" => $_GET["qt"],"qtype" => $_GET["qtype"], "qtitle" => $_GET["qtitle"], "qauthor" => $_GET["qauthor"], "qcat" => $_GET["qcat"], "qstatus" => $_GET["qstatus"], "id" => $article->get("id"))));
					die();
				}

			}
			catch (SERIA_ValidationException $e)
			{
				$errors = $e->getValidationMessages();
				SERIA_HtmlFlash::error($e->getMessage());
			}
		}


		$contents = "<div class='articleEditor'>";

		if($article)
		{
			$contents .= "<h1 class='legend'>".htmlspecialchars($article->get("title"))."</h1>";

			if($notEditable)
				$contents .= "<div class='important'>".htmlspecialchars($notEditable)."</div>";
		}
		else
		{
			$contents .= "<h1 class='legend'>"._t("New article")."</h1>";
		}
		
		$htmlFlashMessagesHtml = SERIA_HtmlFlash::getHtml();
		
		SERIA_Template::head('jQuery-tabs-activate', '<script type="text/javascript" language="javascript">
			$(function(){
				$("#tabs").tabs();
			});
		</script>');

		$contents .= "
			<div id='tabs'>
				<ul>
					<li><a href='#article_overview'><span>"._t("Article")."</span></a></li>
					<li><a href='#article_notes'><span>"._t("Notes")."</span></a></li>";
		
		if($article->get("id")) {
			$contents .= "		
					<li><a href='#article_statistics'><span>"._t("Statistics")."</span></a></li>
					<li><a href='#article_delete'><span>"._t("Delete")."</span></a></li>
";
		}
	
		$contents .= "
				</ul>
				<div id='article_overview'>
					" . $htmlFlashMessagesHtml . "
					<table class='form'>
					<tr><td style='width: 500px'>
						<h2>"._t("Title")."</h2>
						<p><input type='text' style='width: 480px' name='title' value='".htmlspecialchars($article->get("title"))."'>".$errors["title"]."</p>

						<div id='article_type_editor'>
							".$article->getForm("articleEditor", $errors)."
						</div>

					<td style='background-color: #eee;'></td>
					</td><td class='help'>
						<h2>"._t("Categories")."</h2>";

		$contents .= buildCategorySelectTree($article->getCategories());
		$author = $article->getAuthor();
		$contents .= "

						<h2>"._t("Publishing")."</h2>
						".(!SERIA_Base::hasRight("publish_article")?"<div class='important'>"._t("You do not have access to publish articles")."</div>":"<p><input type='checkbox' ".(!SERIA_Base::hasRight("publish_article")?"disabled='1'":"")." name='pending_publish' ".(($article->getPublishStatus()||(!$article->get("id") && SERIA_Base::hasRight("publish_article")))?"checked='1'":"")." id='pending_publish'><label for='pending_publish'>Check this box to make this article publicly available.</label></p>")."
						<h2>"._t("Author")."</h2>
 <table class='form'>
	<tbody>
	<tr>
		<th style='width: 80px'>"._t("Name:")."</th>
		<td><input type='text' name='author_name' value=\"".htmlspecialchars($article->get("author_name"))."\">".$errors["author_name"]."</td>
	</tr>
	<tr>
		<th style='width: 80px'>"._t("E-mail:")."</th>
		<td><input type='text' name='author_email' value=\"".htmlspecialchars($article->get("author_email"))."\">".$errors["author_email"]."</td>
	</tr>
	<tr>
		<th style='width: 80px'>"._t("Owner:")."</th>
		<td>".htmlspecialchars($author !== false ? $author->get("display_name") : 'Unknown')."</td>
	</tr>
	</tbody>
</table>";
		
		$adminLinks = $article->getAdminLinks();
		if(sizeof($adminLinks) > 0) {
			$contents .= "<h2>"._t("Links")."</h2>";
			foreach($adminLinks as $link) {
				$contents .= "<div style='cursor: pointer;' onclick='".$link["onclick"]."'>".$link["caption"]."</div>";				
			}
		}
		
		$contents .= "
						

					</td></tr>
					</table>
				</div>
				<div id='article_notes'>
				" . $htmlFlashMessagesHtml . "
<h2>Internal notes about this article</h2>
<p><textarea name='notes' style='width: 100%; height: 250px;'>".htmlspecialchars($article->get("notes"))."</textarea></p>
				</div>
				<div id='article_statistics'>
				" . $htmlFlashMessagesHtml . "";

//die("K");

	if($_GET["id"])
	{
		if($article->get("rating") && $article->get("rating_counter")) {
			$contents .= "<h2>"._t("Article rating")."</h2>";
			$contents .= "<p>"._t("Total votes").": ".$article->get("rating_counter")."<br>"._t("Rating").": ".$article->get("rating")."<p>";
		}
		
		$contents .= "<h2>"._t("Article statistics")."</h2>";
		$contents .= "<p>"._t("Updated statistics for this article.")."</p>";
		$contents .= _t("Period").": <select name='start_date' onchange='document.location.href=\"./articles.php?id=".$_GET["id"]."&period=\" + this.value + \"#article_statistics\";'><option value='30'>"._t("Last 30 days")."</option><option value='90'".($_GET["period"] == "90" ? " selected" : "").">"._t("Last 90 days")."</option><option value='183'".($_GET["period"] == "183" ? " selected" : "").">"._t("Last half year")."</option><option value='365'".($_GET["period"] == "365" ? " selected" : "").">"._t("Last year")."</option><option value='unlimited'".($_GET["period"] == "unlimited" ? " selected" : "").">"._t("All days")."</option></select>";
		$contents .= "<fieldset><legend>"._t("Summary")."</legend>";
		$contents .= "<ul>";
		$contents .= "	<li>"._t("This article has been viewed a total of %COUNT% times.", array("COUNT" => $article->getViews()))."</li>";
		$contents .= "</ul>";
		$contents .= "</fieldset>";

		if($_GET["period"] != "unlimited") {
			$startDate = date("Y-m-d \0\0\:\0\0", strtotime("-".$_GET["period"]." days"));
			$endDate = date("Y-m-d \0\0\:\0\0", strtotime("+1 day"));
			$contents .= statisticsHours($article->getStatisticsHour($startDate, $endDate));
			$contents .= statisticsWeekdays($article->getStatisticsWeekday($startDate, $endDate));
			$contents .= statisticsDays($article->getViews($startDate, $endDate, true));
			$contents .= statisticsTimingStops($article->getTimingStatistics("stop", $startDate, $endDate));
		} else {
			$contents .= statisticsHours($article->getStatisticsHour());
			$contents .= statisticsWeekdays($article->getStatisticsWeekday());
			$contents .= statisticsDays($article->getViews(false,false,true));
			$contents .= statisticsTimingStops($article->getTimingStatistics("stop"));
		} 

	}
	else
	{
//		$contents .= "<h2>"._t("Statistics unavailable")."</h2>";
//		$contents .= "<p>"._t("You must save the article before you can view article statistics.")."</p>";
	}


		$contents .= "</div>";
	
		if($_GET["id"]) {
			$contents .= "
			<div id='article_delete'>
			" . $htmlFlashMessagesHtml . "
			<h2>Delete article</h2>
			<p class='description warning'>If you delete this article, you will not be able to recover it.</p>
			<input type='checkbox' name='delete' id='deleteArticle'><label for='deleteArticle'>Check this box and click save to delete this article.</label>
					</div>
				</div>
	";
	
			$contents .= "
			</div>";
		}

	
	
		$contents = "<form enctype='multipart/form-data' method='post'".($notEditable?" onsubmit='return false;' disabled='1'":" onsubmit='document.getElementById(\"submitbutton\").disabled=true;'").">".$contents."<input type='submit' value=\""._t("Save Article")."\"".($notEditable?" disabled='1'":"")." id='submitbutton'>".($notEditable?"<div class='important'>".$notEditable."</div>":"")."<input type='hidden' name='article_hash' value='".($_POST["article_hash"]?$_POST["article_hash"]:$currentArticleHash)."'></form>";

		$gui->contents($contents);
	}


	function drawStatistics($title, $data, $translationArray = false, $chartWidth = 600, $chartHeight = 200, $maxColumns=30, $titleOn=true, $valueOn=true) {
		if(sizeof($data) == 0) return false;
		$r .= "<fieldset><h2>".$title."</h2><table style='border-collapse: collapse;'>";
		$r .= "<tr>";
		$max = 1;
		foreach($data as $key => $value)
			if($value > $max) $max = $value;

		$chartHeight = 200;

		$groupSize = ceil(sizeof($data) / $maxColumns);
		$columnWidth = ($chartWidth / ceil(sizeof($data) / $groupSize));
		$totalData = sizeof($data);

		$totalWidth = 0;
		$drawedTotalWidth = 0;
		$counter = 0;
		$totalValue = 0;
		$dataCount = 0;
		// Get highest row
		foreach($data as $key => $value) {
			$counter++;
			$dataCount++;
			$totalValue += $value;
			if($counter == $groupSize || $dataCount == $totalData) {
				$counter = 0;
				if($totalValue > $max) $max = $totalValue;
				$totalValue = 0;
			}
		
		}


		$delta = $chartHeight / $max;

		$totalWidth = 0;
		$drawedTotalWidth = 0;
		$counter = 0;
		$totalValue = 0;
		$dataCount = 0;

		foreach($data as $key => $value) {
			if($counter == 0) $startKey = (isset($translationArray[$key]) ? $translationArray[$key] : $key);
			$counter++;
			$dataCount++;
			$totalValue += $value;
			if($counter == $groupSize || $dataCount == $totalData) {
				$totalWidth += $columnWidth;
				$ceiledColumnWidth = ceil($totalWidth - $drawedTotalWidth);
				$drawedTotalWidth += $ceiledColumnWidth;
				$endKey = (isset($translationArray[$key]) ? $translationArray[$key] : $key);
				$counter = 0;
				$r .= "<td style='vertical-align: bottom;'>";
				$valueText = $totalValue;
				$titleText = ($endKey != $startKey ? $startKey."-".$endKey : $startKey);
				if($valueOn) $r .= "<div style='width: ".$ceiledColumnWidth."px; text-align: center;'>".$valueText."</div>";
				$r .= "<div title='"._t("Range").": ".$titleText."\n"._t("Value").": ".$valueText."' style='width: ".$ceiledColumnWidth."px; height: ".($totalValue*$delta + 3)."px;'><div style='width: 100%; height: 100%; border-top: 1px solid #dd0000; border-left: 1px solid #dd0000; background-color: #ff0000;'>&nbsp;</div></div>";
				$r .= "</td>";
				if($titleOn) $s .= "<td style='text-align: center;'>".$titleText."</td>";
				$totalValue = 0;
			}
		
		}

		$r .= "</tr>";
		if($titleOn) $r .= "<tr>".$s."</tr>";
		$r .= "</table></fieldset>";
		return $r;
	}

	function statisticsHours($data) {
		return drawStatistics(_t("Article impressions per hour"),$data);
	}

	function statisticsWeekdays($data) {
		return drawStatistics(_t("Article impressions per weekday"), $data, array("1" => "Monday", "2" => "Tuesday", "3" => "Wednesday", "4" => "Thursday", "5" => "Friday", "6" => "Saturday", "7" => "Sunday"));
	}

	function statisticsTimingStops($data) {
		$s = array();
		foreach($data as $d) {
			$i = floor($d/5)*5;
			$s[$i." - ".($i+5)." "._t("seconds")]++;
		}
		return drawStatistics(_t("Video playing length"), $s, false, 600, 200, 30000, false, (sizeof($s) < 20 ? true : false));
	}

	function statisticsDays($data) {
		$totDays = sizeof($data);
		$rest = $totDays % 30;
		if($totDays < 30) $rest = 0;
		end($data);
		$lastDate = key($data);
		for($i = 0; $i < $rest; $i++) {
			$lastDate = date("Y-m-d", strtotime("+1 day", strtotime($lastDate)));
			$data[$lastDate] = 0;
		}
		return drawStatistics(_t("Daily views"), $data, false, 600,200,30,false, false);
	}


        function buildCategorySelect($tree, $depth=0, $selectedId=false, $skipId=false)
        {
                $res = "";
                if($tree->isRoot())
                        $res .= "<option value=''></option>";

                $children = $tree->getChildren();

                foreach($children as $child)
                {
                        if($child->getId()!=$skipId)
                        {
                                $prefix = "&raquo;&nbsp;";

                                for($i = 0; $i < $depth; $i++)
                                        $prefix = "&nbsp;&nbsp;&nbsp;&nbsp;".$prefix;
                                $res .= "<option value='".$child->getId()."'".($child->getId()==$selectedId?" selected='1'":"").">".$prefix.htmlspecialchars($child->get("name"))."</option>";
                                $res .= buildCategorySelect($child, $depth+1, $selectedId, $skipId);
                        }
                }

                return $res;
        }


        function buildCategorySelectTree(&$selectedCategories=false, $tree=false)
        {
		if(!$tree) $tree = SERIA_ArticleCategory::getRoot();

	        $nodes = $tree->getChildren();

		if(sizeof($nodes) > 0) {
		        $menu = "<ul class='treeview'>";
	
		        foreach($nodes as $node)
		        {
				$childNodes = $node->getChildren();
				$opened=false;
				foreach($childNodes as $childNode) {
					if(isset($selectedCategories[$childNode->get("id")])) $opened = true;
				}
		                $menu .= "<li".($opened ? " class='open'" : "")."><input type='checkbox' name='categories[]' ".(isset($selectedCategories[$node->get("id")]) ? "checked=1 " : "")."value='".$node->get("id")."' id='node_".$node->get("id")."'><label for='node_".$node->get("id")."' ".$node->getContextMenu().">".htmlspecialchars($node->get("name"))."</label>";
				$menu .= buildCategorySelectTree($selectedCategories, $node);
		                $menu .= "</li>";
		        }	

		        $menu .= "</ul>";
		}

		return $menu;
        }

	echo $gui->output();
