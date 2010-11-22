<?php
	class SERIA_ImageArticle extends SERIA_Article {
		static function getTypeName()
		{
			return _t("Image article");
		}

		static function getTypeDescription()
		{
			return _t("For connecting images to tags");
		}

		protected $type = "SERIA_Image";

		protected $fields = array(
			"image_id" => "file",
		);

		protected $fulltextFields = array(
		);


		// Updates the file counter on all files, then calls parent::delete
		function delete() {
			parent::delete();
		}


		function validateData() {
			parent::validateData();

			if($e = SERIA_IsInvalid::name($this->extra["image_id"], false))
				$errors["image_id"] = $e;

			if(sizeof($errors)>0)
				throw new SERIA_ValidationException(_t("Validation errors."), $errors);

			return $this;
		}

		function receiveData($data, $prefix="")
		{
			if($_FILES[$prefix."_image"] && $_FILES[$prefix."_image"]["name"]) {
				//TODO: validate file type
				$file = $_FILES[$prefix."_image"];
				$oFile = new SERIA_File($file["tmp_name"], $file["name"]);
				$this->set("image_id", $oFile->get("id"));
			}
		}

		function getAbstract()
		{
			$res = array(
				"guid" => "News:".$this->get("id"),
				"title" => $this->get("title"),
			);
			//"description" => $this->get("introduction"),
			if($img = $this->get("image_id"))
			{
				$f = SERIA_File::createObject($img);
				$res["image"] = $f->get("url");
			}
			return $res;
		}		
		/**
		*	$errors = associative array of field errors embedded in a <div class='fieldError'>
		*	$errors["introduction"] = "<div ...>Errormessage</div>"
		*/
		function getForm($prefix=false, $errors=array())
		{
			$r .= "
<input type='file' name='".$prefix."_image' style='width: 470px;'>
<input type='hidden' id='image_id' name='".$prefix."_image_id' value=\"".htmlspecialchars($this->get("image_id"))."\">
".$errors["image_id"]."</fieldset></div>";

			return $r;
		}

	}
?>
