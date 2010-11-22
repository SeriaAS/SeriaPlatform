<?php

	class SERIA_URL {
		private $id;

		public static function getURLs($full = false) {

			$db = SERIA_Base::db();
			$urls = $db->query("SELECT * FROM {urls} ORDER BY url")->fetchAll(PDO::FETCH_ASSOC);
			$rURLS = array();
			$cURLS = array();
			foreach ($urls as $url) {
				$fullURL = "";
				$parentId = $url["parentId"];
				while ($parentId) {
					if (!$cURLS[$parentId]) {
						$fURL = SERIA_URL::getURL($parentId, true);
						$cURLS[$parentId] = $fURL;
					}
					$fullURL = "/".$cURLS[$parentId]["url"].$fullURL;
					$parentId = $cURLS[$parentId]["parentId"];
				}
				$fullURL .= "/".$url["url"];
				$rURLS[$url["id"]] = array("id" => $url["id"], "url" => $url["url"], "parentId" => $url["parentId"], "fullURL" => $fullURL);
			}

			uasort($rURLS, 'SERIA_URL::compare');
			return $rURLS;
		}

		private static function compare($a, $b) {
			if ($a["fullURL"] == $b["fullURL"]) return 0;
			return (($a["fullURL"] < $b["fullURL"]) ? -1 : 1);
		}

		public static function getURL($id, $full = false) {
			$db = SERIA_Base::db();
			$url = $db->query("SELECT * FROM {urls} WHERE id=:id", array("id" => $id))->fetch(PDO::FETCH_ASSOC);


			if ($url) {
				if ($full) return $url;
				else return $url["url"];
			} else {
				throw new SERIA_Exception("No such id");
			}
		}

		public static function createURL($url, $parentId=false) {
			$db = SERIA_Base::db();
			$url = strtolower($url);

			if (!$url) throw new SERIA_Exception("URL is required");

			try {
				$id = SERIA_URL::getIDFromURL($url, $parentId);
				throw new SERIA_Exception("URL is already existing");
			} catch (SERIA_Exception $e) {
			}


			if ($parentId) {
				$db->exec("INSERT INTO {urls} (url, parentId) VALUES (".$db->quote($url).", ".$db->quote($parentId).")");
			} else {
				$db->exec("INSERT INTO {urls} (url) VALUES (".$db->quote($url).")");
			}

			return SERIA_URL::getIDFromURL($url, $parentId);
		}

		public static function getIDFromURL($url, $parentId=false) {
			$db = SERIA_Base::db();
			$url = strtolower($url);
			if ($parentId) {
				$row = $db->query("SELECT id FROM {urls} WHERE url=:url AND parentId=:parentId", array("url" => $url, "parentId" => $parentId))->fetch(PDO::FETCH_ASSOC);
			} else {
				$row = $db->query("SELECT id FROM {urls} WHERE url=:url AND parentId IS NULL", array("url" => $url))->fetch(PDO::FETCH_ASSOC);
			}
			if (!$row) throw new SERIA_Exception("No such URL");
			return $row["id"];
		}


		public static function removeURL($id) {
			$db = SERIA_Base::db();

			throw new SERIA_Exception("Not implemented yet");

			// TODO: Hva skjer naar man fjerner en foreldreside?

			if (!$id) throw new SERIA_Exception("Cannot delete URL, no id set");
//			$db->exec("DELETE FROM {URLS} WHERE id=:id", array("id" => $id));	
		}

		public static function changeURL($id, $url) {
			$db = SERIA_Base::db();
			$url = strtolower($url);
			if (!$id) throw new SERIA_Exception("Cannot change URL, no id set");

			$db->exec("UPDATE {URLS} SET url=:url WHERE id=:id", array("url" => $url, "id" => $id));
		}

//		function __construct($id, $fromCreateObject) {
//			if(!$fromCreateObject)
//				throw new SERIA_Exception("Must use SERIA_URL::createObject to create objects.");
//
//			$this->id = $id;
//		}

	}

?>
