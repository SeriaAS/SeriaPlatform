<?php
	class SERIA_ArticleUrlGenerator extends SERIA_ActiveRecord {
		public $tableName = '_article_url_generators';
		public $usePrefix = true;
		
		public $hasMany = array(
			'SERIA_ArticleUrlGeneratorParam:Params' => 'urlgenerator_id'
		);
		
		public function createGenerator($articleType, $key, $description, $baseUrl, $staticParams = array(), $specialParams = array()) {
			if (!$urlGenerator = SERIA_ArticleUrlGenerators::find_first_by_key($key, array('criterias' => array('articletype' => $articleType)))) {
				$urlGenerator = new SERIA_ArticleUrlGenerator();
				$urlGenerator->articletype = $articleType;
			}
			
			$urlGenerator->baseurl = $baseUrl;
			$urlGenerator->key = $key;
			$urlGenerator->description = $description;
			$urlGenerator->save();
			
			$params = $urlGenerator->Params;
			foreach ($params as $param) {
				$param->delete();
			}
			
			foreach ($staticParams as $key => $value) {
				$param = new SERIA_ArticleUrlGeneratorParam();
				$param->urlgenerator_id = $urlGenerator->id;
				$param->name = $key;
				$param->value = $value;
				$param->save();
			}
			foreach ($specialParams as $key => $value) {
				$param = new SERIA_ArticleUrlGeneratorParam();
				$param->urlgenerator_id = $urlGenerator->id;
				$param->name = $key;
				$param->specialvalue = $value;
				$param->save();
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