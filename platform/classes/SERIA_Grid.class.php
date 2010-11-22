<?php
	class SERIA_Grid {
		private $settings, $prefix;

		private $templates = array(
			"varchar" => array(
				"caption" => "VARCHAR TEMPLATE",
				"validation" => 'return false;',
				"textAlign" => 'left',
				"gridWidth" => 200,
				"outputAsString" => 'return htmlspecialchars($value);',
				"outputAsFormField" => 'return "<input type=\"text\" name=\"".$fieldName."\" value=\"".htmlspecialchars($value)."\" style=\"width: 200px;\">";',
			),
			"text" => array(
				"caption" => "TEXT TEMPLATE",
				"validation" => 'return false;',
				"textAlign" => 'left',
				"gridWidth" => 200,
				"outputAsString" => 'return nl2br(htmlspecialchars($value));',
				"outputAsFormField" => 'return "<textarea name=\"".$fieldName."\" style=\"width: 200px;\">".htmlspecialchars($value)."</textarea>";',
			),
			"date" => array(
				"caption" => "DATE TEMPLATE",
				"validation" => 'return SERIA_IsInvalid::isoDate($value, false);',
				"textAlign" => 'left',
				"gridWidth" => 200,
				"outputAsString" => 'return nl2br(htmlspecialchars($value));',
				"outputAsFormField" => 'return "<input type=\"text\" name=\"".$fieldName."\" style=\"width: 200px;\" value=\"".htmlspecialchars($value)."\"> (yyyy-mm-dd)";',
			),
		);

		function __construct($settings, $prefix="") {
			$this->settings = $settings;

			foreach($this->settings["fields"] as $name => $field)
			{
				if($field["template"])
				{
					if(!$this->templates[$field["template"]])
						throw new SERIA_Exception("No such template '".$field["template"]."'.");
					$this->settings["fields"][$name] = SERIA_Lib::array_merge_recursive_unique($this->templates[$field["template"]], $field);
				}
			}

			if ($prefix) $this->prefix = $prefix;
			else $this->prefix = "grid";
		}

		function getCaption()
		{
			return $this->settings["caption"];
		}

		function getDescription()
		{
			return $this->settings["description"];
		}

		function makeURL($state)
		{
			$parts = $_GET;

			foreach($parts as $k => $v)
				if(strpos($k, $this->prefix."_")===0 || strpos($k, $this->prefix."-")===0)
					unset($parts[$k]);

			foreach($state as $k => $v)
				$parts[$k] = $v;

			$urlParts = array();
			foreach($parts as $k => $v)
				$urlParts[] = rawurlencode($k)."=".rawurlencode($v);

			$l = strpos($_SERVER["REQUEST_URI"], "?");
			if(($l = strpos($_SERVER["REQUEST_URI"], "?"))===false)
				$l = strlen($_SERVER["REQUEST_URI"]);


			$url = substr($_SERVER["REQUEST_URI"], 0, $l)."?".implode("&", $urlParts);

			return $url;
		}

		function output() {
			$res = "";
			if(isset($_GET[$this->prefix."-id"]))
			{
				if($this->settings["formIntroduction"])
					$res = $this->settings["formIntroduction"];
				$res .= $this->outputForm($_GET[$this->prefix."-id"]);
			}
			else
			{
				if($this->settings["gridIntroduction"])
					$res = $this->settings["gridIntroduction"];
				$res .= $this->outputGrid();
			}
			return $res;
		}

		function outputGrid() {
			$table = new SERIA_GUI_Table($this->prefix."_grid");

			$sort = $this->settings["sort"];
			if(isset($_GET[$this->prefix."-sort"]))
				$sort = $_GET[$this->prefix."-sort"];
			$sortDesc = isset($_GET[$this->prefix."-desc"]);

			$fields = array();
			$totFields = 0;
			foreach ($this->settings["fields"] as $name => $data) {
				if(!$data["gridWidth"])
					continue;
				$style = "";
				if($tmp = $data["gridWidth"])
					$style .= "width:$tmp"."px;";
				if($tmp = $data["textAlign"])
					$style .= "text-align:$tmp;";

				if($data["sortable"])
				{ // this field can be sorted by
					if($sort == $name)
					{ // sorting on this field already
						if($sortDesc)
							$onclick = "onclick='location.href=\"".$this->makeURL(array($this->prefix."-sort" => $name))."\";'";
						else
							$onclick = "onclick='location.href=\"".$this->makeURL(array($this->prefix."-sort" => $name, $this->prefix."-desc" => 1))."\";'";
					}
					else
					{
						$onclick = "onclick='location.href=\"".$this->makeURL(array($this->prefix."-sort" => $name))."\";'";
					}
				}
				else
					$onclick="";

				$h .= "<th style='$style' $onclick>".$data["caption"]."</th>";
				$fields[$totFields++] = $name;
			}

			$table->head($h);

			$key = 0;
			$sqlFields = array($this->settings["primaryKey"] => $key++);
			foreach($this->settings["fields"] as $name => $data)
			{
				$sqlFields[$name] = $key++;
			}
			$sqlFields = array_flip($sqlFields);

			$sql = "SELECT ".implode(",",$sqlFields)." FROM ".$this->settings["table"];

			$whereParts = array();
			foreach($this->settings["fields"] as $fieldName => $data)
			{
				if(isset($_GET[$this->prefix."_".$fieldName]) && trim($_GET[$this->prefix."_".$fieldName])!="")
				{
					if(isset($_GET[$this->prefix."-not-".$fieldName]))
						$whereParts[] = $fieldName." NOT LIKE ".SERIA_Base::db()->quote(str_replace("*","%", $_GET[$this->prefix."_".$fieldName]));
					else
						$whereParts[] = $fieldName." LIKE ".SERIA_Base::db()->quote(str_replace("*","%", $_GET[$this->prefix."_".$fieldName]));
				}
			}

			if(sizeof($whereParts)>0)
				$sql .= " WHERE ".implode(" AND ", $whereParts);

			if($sort && $this->settings["fields"][$sort])
			{
				$sql .= " ORDER BY $sort";
				if($sortDesc)
					$sql .= " DESC";
			}

			$rows = SERIA_Base::db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);

			$b = "";		
			foreach($rows as $row) {	
				$b .= "<tr onclick='document.location.href=\"".$this->makeURL(array($this->prefix."-id" => $row[$this->settings["primaryKey"]]))."\";' style='cursor: pointer;'>";
				$style = "";
				for ($fieldNo = 0; $fieldNo < $totFields; $fieldNo++) {
					$data = $this->settings["fields"][$fields[$fieldNo]];

					if($tmp = $data["textAlign"])
						$style .= "text-align: $tmp;";
				
					$b .= "<td style='$style'>".$this->outputAsString($fields[$fieldNo], $row[$fields[$fieldNo]])."</td>";
				}
				$b .= "</tr>";
			}

			$table->body($b);

			$showSearch = false;
			foreach($this->settings["fields"] as $fieldName => $data)
				if(isset($_GET[$this->prefix."_".$fieldName]))
					$showSearch = true;

			$r = ($showSearch?"":"<input type='button' value=\"".htmlspecialchars(_t("Search..."))."\" onclick='this.style.display=\"none\"; document.getElementById(\"".htmlspecialchars($this->prefix)."-searchform\").style.display=\"block\";'>")."<form id=\"".htmlspecialchars($this->prefix)."-searchform\" method='get' style='display: ".($showSearch?"block":"none")."'><fieldset><legend>"._t("Search")."</legend>";

			$r .= "<table class='gridForm'>";
			// Add active search fields (search => "active")
			foreach($this->settings["fields"] as $fieldName => $data) 
			{
				if($data["outputAsFormField"])
					$r .= "<tr><td style='width: 100px;'>".htmlspecialchars($data["caption"]).":</td><td>".$this->outputAsFormField($fieldName, $_GET[$this->prefix."_".$fieldName], true)."</td><td><input type='checkbox' name=\"".$this->prefix."-not-".$fieldName."\" ".(isset($_GET[$this->prefix."-not-".$fieldName])?"checked='1'":"")." id=\"".$this->prefix."-not-".$fieldName."\"> <label for=\"".$this->prefix."-not-".$fieldName."\">"._t("NOT")."</label></tr>";
			}
			$r .= "</table>
<input type='submit' value=\"".htmlspecialchars("Search")."\"> "._t("Tip: You can use * as a wildcard when searching!")."";

			$r .= "</fieldset></form>";

			$r .= $table->output();

			if($this->settings["allowAdd"])
				$r .= "<p><input type='button' onclick='location.href=\"".$this->makeURL(array($this->prefix."-id" => ""))."\"' value=\"".htmlspecialchars(_t("Add"))."\"></p>";

			return $r;

		}

		function outputForm($id=false) {
			$errors = array();
			$db = SERIA_Base::db();
			$isNew = $id ? false : true;

			$formValues = array();
			// is deleting?
			if($this->settings["allowDelete"] && isset($_GET[$this->prefix."-delete"]))
			{
				// pre delete event
				if($this->settings["beforeDelete"])
				{
					$globalError = eval($this->settings["beforeDelete"]);
				}
				if(!$globalError)
				{
					try
					{
						$db->exec("DELETE FROM ".$this->settings["table"]." WHERE ".$this->settings["primaryKey"]."=".$db->quote($id));
//						$db->exec("COMMIT");

					}
					catch(PDOException $e)
					{
						$globalError = _t("Unable to delete this row. Got '%ERRORMESSAGE%'.", array("ERRORMESSAGE" => $e->getMessage()));
					}
					if(!$globalError)
					{
						if($this->settings["afterDelete"])
							eval($this->settings["afterDelete"]);
						header("Location: ".$this->makeURL());
						die();
					}
				}
			}

			if($_POST) 
			{ // has post data?

				$row = array();

				// postHandlers
				foreach($this->settings["fields"] as $name => $field) {
					if($field["postHandler"])
					{
						$value = $_POST[$this->prefix."_".$name];
						$_POST[$this->prefix."_".$name] = eval($field["postHandler"]);
					}
				}

				// validate
				foreach ($this->settings["fields"] as $name => $field) {
					$value = $_POST[$this->prefix."_".$name];

					if($field["validation"])
						if($e = eval($field["validation"]))
							$errors[$name] = $e;

					$formValues[$name] = $value;
				}

				if (sizeof($errors) == 0) {

					// create rowdata
					foreach ($this->settings["fields"] as $name => $field)
						$row[$name] = $_POST[$this->prefix."_".$name];

					// allow for updates to the values
					if($this->settings["dbPrepare"])
						eval($this->settings["dbPrepare"]);

					// prepare for database
					foreach ($this->settings["fields"] as $name => $field) {
						$value = $row[$name];

						if($field["dbPrepare"])
							$dbValue = eval($field["dbPrepare"]);
						else
							$dbValue = $db->quote($value);

						$row[$name] = $dbValue;
					}


					if ($id) {
						$sql = array();

						foreach($row as $field => $value)
							$sql[] = $field."=".$value;

						$query = "UPDATE ".$this->settings["table"]." SET ".implode(",", $sql)." WHERE id=".$db->quote($id);
					} else {
						$sqlFields = array();
						$sqlValues = array();

						foreach($row as $field => $value)
						{
							$sqlFields[] = $field;
							$sqlValues[] = $value;
						}

						$query = "INSERT INTO ".$this->settings["table"]." (".implode($sqlFields,",").") VALUES (".implode($sqlValues,",").")";
					}
					$db->exec($query);
					if($this->settings["afterSave"])
					{
						if(!$id) $id = $db->lastInsertId();
						eval($this->settings["afterSave"]);
					}
					header("Location: ".$this->makeURL(array()));
					die();
				}
			} 
			else if($id) 
			{
				$row = $db->query("SELECT * FROM ".$this->settings["table"]." WHERE ".$this->settings["primaryKey"]."=".$db->quote($id))->fetch(PDO::FETCH_ASSOC);

				foreach ($this->settings["fields"] as $name => $data) {
					$formValues[$name] = $row[$name];
				}
			}
			else
			{
				foreach($this->settings["fields"] as $name => $data)
				{
					if(isset($data["default"]))
						$formValues[$name] = $data["default"];
					else
						$formValues[$name] = "";
				}
			}

			if($globalError)
				$r .= "<p class='error'>$globalError</p>";

			if($this->settings["form"])
			{
				$form = $this->settings["form"];

				$find = array();
				$replace = array();

				$missingFields = array();
				$multipleFields = array();

				foreach($this->settings["fields"] as $name => $data)
				{
					if($data["outputAsFormField"])
					{
						if(($pos = strpos($form, "%".$name."%"))!==false)
						{
							if(strpos($form, "%".$name."%", $pos+1)!==false)
							{
								$multipleFields[] = $name;
							}
							else
							{
								$find[] = "%".$name."%";
								$replace[] = $this->outputAsFormField($name, $formValues[$name], $id);
								$find[] = "%".$name.".value%";
								$replace[] = $this->outputAsString($name, $formValues[$name]);
								$find[] = "%".$name.".error%";
								$replace[] = ($errors[$name] ? "<div class='error'>".htmlspecialchars($errors[$name])."</div>" : "");
								$find[] = "%".$name.".caption%";
								$replace[] = htmlspecialchars($data["caption"]);
							}
						}
						else
							$missingFields[] = $name;
					}
				}

				if(sizeof($missingFields)>0)
					throw new SERIA_Exception("Missing fields in form: ".implode(", ", $missingFields));

				if(sizeof($multipleFields)>0)
					throw new SERIA_Exception("Field used multiple times in form: ".implode(", ", $multipleFields));

				$form = str_replace($find, $replace, $form);
				ob_start();
				eval("?".">".$form);
				$form = ob_get_contents();
				ob_end_clean();
				$r .= $form;
			}
			else
			{
				$r .= "<table class='gridForm'>";
				foreach ($this->settings["fields"] as $name => $data) {
					if($data["outputAsFormField"])
						$r .= "<tr><td style='width: 100px;'>".htmlspecialchars($data["caption"]).":</td><td>".$this->outputAsFormField($name, $formValues[$name], $id).($errors[$name] ? "<div class='error'>".htmlspecialchars($errors[$name])."</div>" : "")."</td></tr>";
				}
				$r .= "</table>";
			}
			$r .= "
	<input type='submit' value=\""._t("Save")."\">
	<input type='button' class='greenButton' value=\"".htmlspecialchars(_t("Abort"))."\" onclick='return document.location.href=\"".$this->makeURL(array())."\"'>
	".($id && $this->settings["allowDelete"] ? "<input type='button' class='redButton' value=\"".htmlspecialchars(_t("Delete"))."\" onclick='if(!confirm(\""._t("Are you sure that you want to delete this record?")."\")) return false; location.href=\"".$this->makeURL(array($this->prefix."-id" => $_GET[$this->prefix."-id"], $this->prefix."-delete" => 1))."\";'>" : "")."
";

			return "<form method='post'>".$r."</form>";
		}

		function outputAsFormField($name, $value, $id) {
			$fieldName = $this->prefix."_".$name;
			$isNew = $id ? false : true;
			return eval($this->settings["fields"][$name]["outputAsFormField"]);
		}

		function outputAsString($name, $value) {
			return eval($this->settings["fields"][$name]["outputAsString"]);
		}
	}
?>
