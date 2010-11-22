<?php
	SERIA_Base::viewMode("admin");

	$gui->topMenu(_t("Create Category"), "location.href='".SERIA_Base::url()."'");

	// build tree structure

	$tree = SERIA_ArticleCategory::getRoot();

	if(isset($_GET["id"]))
		$currentCategory = $tree->getNodeById($_GET["id"]);
	else
		$currentCategory = false;


	if(isset($_POST["name"]))
	{
		if(isset($_POST["delete"]))
		{
			if($currentCategory->delete())
			{
				if($_REQUEST["parent_id"])
					header("Location: ".SERIA_Base::url(array("id"=>$_REQUEST["parent_id"])));
				else
					header("Location: ".SERIA_Base::url());
				die();
			}
		}



		$errors = array();

		if($e = SERIA_IsInvalid::name($_POST["name"], true))
			$errors["name"] = $e;

		try
		{
			if($_REQUEST["parent_id"])
			{
				$parent = SERIA_ArticleCategory::createObject($_REQUEST["parent_id"]);
			}
			else
			{
				$parent = $tree;
			}
		}
		catch(SERIA_Exception $e)
		{
			$errors["parent_id"] = "Invalid parent id";
		}

		if(sizeof($errors)==0)
		{ // all okay, save changes
			if($currentCategory)
			{ // update an existing node
				$currentCategory->set("name", $_POST["name"]);
				$currentCategory->set("short_description", $_POST["short_description"]);
				$currentCategory->set("long_description", $_POST["long_description"]);
				$currentCategory->set("notes", $_POST["notes"]);
				$currentCategory->set("is_published", $_POST["is_published"]?1:0);
				if(!$parent->is($currentCategory->getParent()))
					$parent->appendChild($currentCategory);
				$currentCategory->save();
			}
			else
			{ // create a new node
				$currentCategory = SERIA_ArticleCategory::create($parent, $_POST["name"]);
			}

			header("Location: ".SERIA_Base::url(array("id"=>$currentCategory->getId())));
			die();
		}
		else
		{
			foreach($errors as $k => $t)
				$errors[$k] = "<div class='fieldError'>".htmlspecialchars($t)."</div>";
		}
	}

	$rootNodes = $tree->getChildren();

	$menu = "<ul class='treeview'>";

	foreach($rootNodes as $node)
	{
		$menu .= "<li><a href='".SERIA_Base::url(array("id"=>$node->get("id")))."'>".htmlspecialchars($node->get("name"))."</a>";
		$childLevel1 = $node->getChildren();
		if(sizeof($childLevel1)>0)
		{
			$menu .= "<ul>";
			foreach($childLevel1 as $child1)
			{
				$menu .= "<li><a href='".SERIA_Base::url(array("id"=>$child1->get("id")))."'>".htmlspecialchars($child1->get("name"))."</a>";
				$childLevel2 = $child1->getChildren();
				if(sizeof($childLevel2)>0)
				{
					$menu .= "<ul>";
					foreach($childLevel2 as $child2)
					{
						$menu .= "<li><a href='".SERIA_Base::url(array("id"=>$child2->get("id")))."'>".htmlspecialchars($child2->get("name"))."</a></li>";
					}

					$menu .= "</ul>";
				}
				$menu .= "</li>";
			}
			$menu .= "</ul>";
		}
		$menu .= "</li>";
	}

	$menu .= "</ul>";

	$gui->sectionMenu($menu);

	if(isset($_POST["name"]))
		$data = $_POST;
	else if($currentCategory)
		$data = $currentCategory->getArray();
	else
		$data = array();

	$contents = "<h1>".($currentCategory?htmlspecialchars($currentCategory->get("name")):_t("Create new category"))."</h1>";
	$contents .= "<form method='post' action='".SERIA_Base::url($currentCategory?array("id"=>$currentCategory->getId()):false)."'>";

	if(sizeof($errors)>0)
	{
		$contents .= "<p class='error'>"._t("There were some validation errors. Please check the form and try again.")."</p>";
	}

	$contents .= "<div class='tabs'>
	<ul>
		<li><a href='#publishing'><span>"._t("Publishing")."</span></a></li>
		<li><a href='#notes'><span>"._t("Notes")."</span></a></li>
		<li><a href='#move'><span>"._t("Move")."</span></a></li>
		<li><a href='#delete'><span>"._t("Delete")."</span></a></li>
	</ul>
	<div id='publishing'>
		<h2>"._t("Title")."</h2>
		<p><input type='text' style='width: 100%' name='name' value=\"".htmlspecialchars($data["name"])."\">".$errors["name"]."</p>
		<h2>"._t("Published status")."</h2>
		<p><input type='checkbox' name='is_published' ".($data["is_published"]?"checked='1'":"")."id='is_published'><label for='is_published'>"._t("Check this box to make this category publicly available.")."</label></p>
		<h2>"._t("Nice ID")."</h2>
		<p class='description'>"._t("Nice ID using only characters a-z, numbers, - and _. Used for creating nice URLs.")."</p>
		<p><input type='text' style='width: 100%' name='niceID' value=\"".htmlspecialchars($data["niceID"])."\">".$errors["niceID"]."</p>
		<h2>"._t("Short description")."</h2>
		<p class='description'>"._t("Write a short description targeted at your audience.")."</p>
		<p><textarea name='short_description' style='width: 100%; height: 60px;'>".htmlspecialchars($data["short_description"])."</textarea>".$errors["short_description"]."</p>
		<h2>"._t("Long description")."</h2>
		<p class='description'>"._t("Write a longer description that will help search engines describe the category.")."</p>
		<p><textarea name='long_description' style='width: 100%; height: 190px;'>".htmlspecialchars($data["long_description"])."</textarea>".$errors["long_description"]."</p>
	</div>
	<div id='notes'>
		<h2>"._t("Internal notes about this category")."</h2>
		<p><textarea name='notes' style='width: 100%; height: 250px;'>".htmlspecialchars($data["notes"])."</textarea>".$errors["notes"]."</p>
	</div>
	<div id='move'>
		<h2>"._t("Containing category")."</h2>
		<p class='description'>"._t("This category is currently contained inside:")."</p>
		<select name='parent_id'>
";

	$contents .= buildCategorySelect(SERIA_ArticleCategory::createObject(), 0, $data["parent_id"], $data["id"]);

	

	$contents .= "</select>".$errors["parent_id"]."
	</div>
	<div id='delete'>
		<h2>"._t("Delete category")."</h2>
		<p class='description warning'>"._t("If you delete this category, all categories contained within it will also be deleted. Articles placed inside these categories will not be deleted - but articles that are only located within this category will only be available trough search.")."</p>
		<input type='checkbox' name='delete' id='deleteCategory'><label for='deleteCategory'>"._t("Check this box and click save to delete this category.")."</label>
	</div>

</div>";

	$contents .= "<input type='submit' value=\""._t("Save")."\">";

	$contents .= "</form>";

	$gui->contents($contents);

	function buildCategorySelect($tree, $depth=0, $selectedId=false, $skipId=false)
	{
		$res = "";
		if($tree->isRoot())
			$res .= "<option value=''>"._t("This is a top level category")."</option>";

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
