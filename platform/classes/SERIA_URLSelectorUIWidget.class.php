<?php
	class SERIA_URLSelectorUIWidget extends SERIA_UIWidget {
		private $mode;
		function output() {
			$urls = SERIA_URL::getURLs(true);
			$urlList = array();
			$urlList[""] = "/";
			foreach ($urls as $url) {
				$urlList[$url["id"]] = $url["fullURL"];
			}

			$r = "<select name=\"".$this->name."_parentId\"".($this->mode == "add" ? " onchange=\"document.getElementById('".$this->name."_name').focus();\"" : "").">\n";

			foreach ($urlList as $id => $url) {
				$r .= "<option value='".$id."'".($_POST[$this->name."_parentId"] == $id ? " selected='selected'" : "").">".$url."</option>\n";
			}

			$r .= "</select>";

			if ($this->mode == "add") $r .= "<input type='text' name='".$this->name."_url' id='".$this->name."_name'>\n";
			return $r;
		}

		// $mode = select | add
		function __construct($name, $mode="select") {
			if ($mode != "add" && $mode != "select") throw new SERIA_Exception("Unknown mode '".$mode."'");
			$this->mode = $mode;
			$this->name = $name;
			$this->error = false;

			if (isset($_POST[$this->name."_parentId"])) {
				if ($this->validateData($_POST[$this->name."_parentId"], $_POST[$this->name."_url"])) {
					if ($this->mode == "select") {
						$this->value = array("id" => $_POST[$this->name."_parentId"], "url" => false);
					} else if ($this->mode == "add") {
						$this->value = array("id" => $_POST[$this->name."_parentId"], "url" => $_POST[$this->name."_url"]);
					}
				} else {
					$this->value = false;
				}
			}
		}

		private function validateData($parentId, $url) {
			if (!$parentId) $parentId = false; // Just to make sure $parentId is a boolean false

			if ($parentId) {
				try {
					$parentURL = SERIA_URL::getURL($parentId);
				} catch (SERIA_Exception $e) {

					$this->error = "Parent URL does not exist (id=".$parentId.")";
					return false;
				}
			}

			if ($this->mode == "add") {
				if ($url == "") {
					$this->error = "URL is required";
					return false;
				} else {
					try {
						$id = SERIA_URL::getIDFromURL($url, $parentId);
						$this->error = "URL is already used";
						return false;
					} catch (SERIA_Exception $e) {

					}
				}
			}
			return true;
		}
	}
?>
