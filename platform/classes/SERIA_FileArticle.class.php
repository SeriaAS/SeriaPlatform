<?php
	class SERIA_FileArticle extends SERIA_Article {
		static function getTypeName() {
			return _t("File article");
		}
		
		static function getTypeDescription() {
			return _t("Built in article for the file system");
		}

		protected $type = "SERIA_File";

		protected $fields = array(
			"file_id" => "file",
		);

		protected $fulltextFields = array(
		);

		function validateData() {
			return $this;
		}

		function receiveData($data, $prefix="") {
		}

		function getAbstract() {
			$res = array(
				"guid" => "Product:".$this->get("id"),
				"title" => $this->get("title"),
			);
			return $res;
		}

		function getForm($prefix=false, $errors=array()) {
			$resultForm .= "";
			return $resultForm;
		}

		static function getLastUploaded($numberOfFiles = 20) {
			
		}

		function getFile() {
			$file = SERIA_File::createObject($this->get("file_id"));
			return $file;
		}

	}
?>
