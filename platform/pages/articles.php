<?php
	SERIA_Base::viewMode("admin");

	$gui->topMenu(_t("Create article"), "location.href='".SERIA_Base::url(array("qt" => $_GET["qt"], "qtitle" => $_GET["qtitle"], "qauthor" => $_GET["qauthor"], "qcat" => $_GET["qcat"], "qstatus" => $_GET["qstatus"], "id" => ""))."'");
	$sectionMenu = "
		<h1>Filter</h1>
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
				"._("Published status:")."<br>
				<input type='radio' name='qstatus' value='' ".(!isset($_GET["qstatus"])||$_GET["qstatus"]==""?"checked='1'":"")." id='qPublishedNoFilter'><label for='qPublishedNoFilter'>"._t("Ignore")."</label><br>
				<input type='radio' name='qstatus' value='1' ".($_GET["qstatus"]=="1"?"checked='1'":"")." id='qPublishedYes'><label for='qPublishedYes'>"._t("Only published")."</label><br>
				<input type='radio' name='qstatus' value='0' ".($_GET["qstatus"]=="0"?"checked='1'":"")." id='qPublishedNo'><label for='qPublishedNo'>"._t("Only not published")."</label><br>
			</div>
			<div>
				<input type='submit' value=\""._t("Search")."\">
			</div>
		</form>";


	$gui->sectionMenu($sectionMenu);


	if(!isset($_GET["id"]))
	{
		$articles = new SERIA_ArticleQuery();

		if(isset($_GET["qt"]))
			$articles->where($_GET["qt"]);

		if(isset($_GET["qtitle"]))
			$articles->where($_GET["qtitle"], "@title:");

		if(isset($_GET["qauthor"]))
			$articles->where($_GET["qauthor"], "@author_id:");

		if(isset($_GET["qcat"]))
			$articles->where($_GET["qcat"], "@category_id:");

		if(isset($_GET["qstatus"]))
		{
			if($_GET["qstatus"]==="")
				;
			else if($_GET["qstatus"])
				$articles->isPublished();
			else
				$articles->isNotPublished();
		}

		$articles->order("altered_date DESC");
		$res = $articles->page(0,100);

		$contents = "
		<h1>"._t("Articles")."</h1><table class='grid' style='width: 100%'>
		<thead>
			<tr>
				<th style='width: 16px'>&nbsp;</th>
				<th style='width: 50px;text-align: right;padding-right:10px;'>"._t("#")."</th>
				<th>"._t("Title")."</th>
				<th style='width: 150px;'>"._t("Author")."</th>
				<th style='width: 50px;'>"._t("Type")."</th>
				<th style='width: 130px;'>"._t("Created")."</th>
				<th style='width: 130px;'>"._t("Altered")."</th>
			</tr>
		</thead><tbody>";

		foreach($res as $article)
		{
			$abstract = $article->getAbstract();
			$contents .= "
			<tr class='highlight' onclick=\"location.href='".SERIA_Base::url(array("qt" => $_GET["qt"], "qtitle" => $_GET["qtitle"], "qauthor" => $_GET["qauthor"], "qcat" => $_GET["qcat"], "qstatus" => $_GET["qstatus"], "id" => $article->get("id")))."'\">
				<td style='text-align:center;vertical-align:middle'>x</td>
				<td style='text-align:right;padding-right:10px;'>".$article->get("id")."</td>
				<td>".htmlspecialchars($abstract["title"])."</td>
				<td>".$article->get("author_name")."</td>
				<td>".$article->get("type")."</td>
				<td>"._datetime($article->get("created_date"))."</td>
				<td>".($article->get("created_date")!=$article->get("altered_date")?_datetime($article->get("altered_date")):"")."</td>
			</tr>";
		}

		$contents .= "
		</tbody></table>";
		$gui->contents($contents);
	}
	else if(!$_GET["id"] && !$_GET["articleType"]) 
	{ // User choose article type to create
		$types = array("News", "Video");
		$contents = "<h1>"._t("Choose article type")."</h1>";
		foreach($types as $type) 
		{
//			$o = SERIA_Article::createObject($type);

			$contents .= "
<p>
	<h2><a href='".SERIA_Base::url(array("id"=>"","articleType"=>$type))."'>".$type."</a></h2>
	Beskrivelse...
</p>";
		}
		$gui->contents($contents);
	}
	else
	{ // view/edit article

		$gui->exitButton(_t("< Article list"), "location.href='".SERIA_Base::url(array("qt" => $_GET["qt"], "qtitle" => $_GET["qtitle"], "qauthor" => $_GET["qauthor"], "qcat" => $_GET["qcat"], "qstatus" => $_GET["qstatus"]))."'");

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

		$article->writable();

		$errors = array();

		if(sizeof($_POST))
		{ // receive data and set all values that are common for the forms, and then call receiveData and validate
			// common fields for all article types:
			$article->set("title", $_POST["title"]);
			$article->set("is_published", ($_POST["is_published"]?1:0));
			$article->set("author_name", $_POST["author_name"]);
			$article->set("author_email", $_POST["author_email"]);
//TODO: Do not remove from categories, foreign keys might be deleted
			$article->removeAllCategories();
			foreach($_POST["categories"] as $category) {
			        $oCategory = SERIA_ArticleCategory::createObject($category);
			        $article->addToCategory($oCategory);
			}

			$article->receiveData($_POST, "articleEditor");

			try
			{
				$article->validateData();
				$article->save();
				if($_GET["continue"])
				{
					die("<html><head></head><body><script type='text/javascript'>top.location.href=\"".htmlspecialchars($_GET["continue"])."\";</script></body></html>");
				}
				else
					header("Location: ".SERIA_Base::url(array("qt" => $_GET["qt"], "qtitle" => $_GET["qtitle"], "qauthor" => $_GET["qauthor"], "qcat" => $_GET["qcat"], "qstatus" => $_GET["qstatus"], "id" => $article->get("id"))));

			}
			catch (SERIA_ValidationException $e)
			{
				$errors = $e->getValidationMessages();
			}
		}


		$contents = "
		<div class='articleEditor'>";

		if($article)
			$contents .= "
			<h1>".htmlspecialchars($article->get("title"))."</h1>";
		else
			$contents .= "
			<h1>"._t("New article")."</h1>";
			
		$contents .= "
			<div class='tabs'>
				<ul>
					<li><a href='#article_overview'><span>"._t("Article")."</span></a></li>
					<li><a href='#article_notes'><span>"._t("Notes")."</span></a></li>
					<li><a href='#article_statistics'><span>"._t("Statistics")."</span></a></li>
					<li><a href='#article_delete'><span>"._t("Delete")."</span></a></li>
				</ul>
				<div id='article_overview'>
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
		$contents .= "

						<h2>"._t("Publishing")."</h2>
						<p><input type='checkbox' name='is_published' ".($article->get("is_published")?"checked='1'":"")." id='is_published'><label for='is_published'>Check this box to make this article publicly available.</label></p>
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
								<td>".htmlspecialchars($article->getAuthor()->get("display_name"))."</td>
							</tr>
							</tbody>
						</table>

					</td></tr>
					</table>
				</div>
				<div id='article_notes'>
c
				</div>
				<div id='article_statistics'>
c
				</div>
				<div id='article_delete'>
d
				</div>
			</div>
";

		$contents .= "
		</div>";

		$contents = "<form enctype='multipart/form-data' method='post'>".$contents."<input type='submit' value=\""._t("Save")."\"></form>";

		$gui->contents($contents);
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
		                $menu .= "<li".($opened ? " class='open'" : "")."><input type='checkbox' name='categories[]' ".(isset($selectedCategories[$node->get("id")]) ? "checked=1 " : "")."value='".$node->get("id")."' id='node_".$node->get("id")."'><label for='node_".$node->get("id")."'>".htmlspecialchars($node->get("name"))."</label>";
				$menu .= buildCategorySelectTree($selectedCategories, $node);
		                $menu .= "</li>";
		        }	

		        $menu .= "</ul>";
		}

		return $menu;
        }


