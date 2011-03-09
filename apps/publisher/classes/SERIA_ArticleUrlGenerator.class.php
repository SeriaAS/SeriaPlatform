<?php
	class SERIA_ArticleUrlGenerator extends SERIA_ActiveRecord {
		public $tableName = '_article_url_generators';
		public $usePrefix = true;
		
		public $hasMany = array(
			'SERIA_ArticleUrlGeneratorParam:Params' => 'urlgenerator_id'
		);
		
		public function createGenerator($articleType, $key, $description, $baseUrl, $staticParams = array(), $specialParams = array()) {
			/*
			 * PHP crashes with segfault. So I have to rewrite without ActiveRecord.
			 */
			$gen = SERIA_Base::db()->query('SELECT * FROM {article_url_generators} WHERE articletype = :articletype', array('articletype' => $articleType))->fetch(PDO::FETCH_ASSOC);
			if ($gen) {
				$data = array(
					'baseurl' => $baseUrl,
					'key' => $key,
					'description' => $description
				);
				foreach ($data as $name => $value)
					$gen[$name] = $value;
				if (SERIA_Base::db()->update('{article_url_generators}', array('id' => $gen['id']), array_keys($data), $data) === false)
					throw new SERIA_Exception('Failed to update url generator!');
			} else {
				$data = array(
					'articletype' => $articleType,
					'baseurl' => $baseUrl,
					'key' => $key,
					'description' => $description
				);
				if (SERIA_Base::db()->insert('{article_url_generators}', array_keys($data), $data) === false)
					throw new SERIA_Exception('Failed to add url generator!');
				$gen = SERIA_Base::db()->query('SELECT * FROM {article_url_generators} WHERE articletype = :articletype', array('articletype' => $articleType))->fetch(PDO::FETCH_ASSOC);
				if (!$gen)
					throw new SERIA_Exception('Failed to add url generator!');
			}

			/* Delete old params */
			if (SERIA_Base::db()->exec('DELETE FROM {article_url_params} WHERE urlgenerator_id = :id', array('id' => $gen['id'])) === false)
				throw new SERIA_Exception('Failed to remove old urlgenerator params!');

			foreach ($staticParams as $key => $value) {
				$data = array(
					'urlgenerator_id' => $gen['id'],
					'name' => $key,
					'value' => $value
				);
				if (SERIA_Base::db()->insert('{article_url_params}', array_keys($data), $data) === false)
					throw new SERIA_Exception('Failed to set urlgenerator param!');
			}
			foreach ($specialParams as $key => $value) {
				$data = array(
					'urlgenerator_id' => $gen['id'],
					'name' => $key,
					'specialvalue' => $value
				);
				if (SERIA_Base::db()->insert('{article_url_params}', array_keys($data), $data) === false)
					throw new SERIA_Exception('Failed to set urlgenerator param!');
			}
			
			return true;
		}
		
		public function createUrlForArticle($article) {
			return SERIA_HTTP_ROOT . $this->createPartialUrlForArticle($article);
		}
		
		public function createPartialUrlForArticle($article) {
			$url = $this->baseurl;
			if (sizeof($this->Params)) {
				$url .= '?';
			}
			
			$first = true;
			foreach ($this->Params as $param) {
				if ($first) {
					$first = false;
				} else {
					$url .= '&amp;';
				}
				
				$url .= urlencode($param->name) . '=';
				if ($param->specialvalue) {
					$url .= urlencode($article->get($param->specialvalue));
				} else {
					$url .= urlencode($param->value);
				}
			}
			
			return $url;
		}
	}
?>