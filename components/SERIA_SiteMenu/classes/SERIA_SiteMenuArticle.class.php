<?php
	class SERIA_SiteMenuArticle extends SERIA_ActiveRecord implements SERIA_EventListener {
		public $tableName = '_sitemenu_article';
		public $usePrefix = true;
		
		public $belongsTo = array(
			'SERIA_SiteMenu:SiteMenu' => 'sitemenu_id'
		);
		
		public static function createObject($id) {
			return SERIA_SiteMenuArticles::find($id);
		}
		public function getObjectId() {
			return array('SERIA_SiteMenuArticle', 'createObject', $this->id);
		}
		
		public function catchEvent(SERIA_EventDispatcher $source, $eventName) {
			if (is_subclass_of($source, 'SERIA_Article')) {
				if ($source->get('id') == $this->article_id) {
					if ($this->SiteMenu->relationtype == 'article') {
						switch ($eventName) {
							case 'DELETE':
								$this->SiteMenu->delete();
								break;
							case 'PUBLISH':
								if ($this->inheritpublishstatus) {
									$this->SiteMenu->ispublished = true;
									$this->SiteMenu->save();
								}
								break;
							case 'UNPUBLISH':
								if ($this->inheritpublishstatus) {
									$this->SiteMenu->ispublished = false;
									$this->SiteMenu->save();
								}
								break;
						}
					}
				}
			}
		}
		
		public function afterSave() {
			try {
				$article = SERIA_Article::createObjectFromId($this->article_id);
				if ($article) {
					$article->addEventListener('DELETE', $this);
					$article->addEventListener('PUBLISH', $this);
					$article->addEventListener('UNPUBLISH', $this);
				}
			} catch (Exception $exception) {
				try {
					$elementTitle = $this->SiteMenu->title;
				} catch (Exception $null) {
					$elementTitle = 'UNKNOWN';
				}
				SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, _t('Unable to attach menu element %ELEMENT% to article event: %EXCEPTION', array('EXCEPTION' => $exception->getMessage(), 'ELEMENT' => $elementTitle)), 'content');
			}
		}
		
		public function getUrl() {
			return SERIA_HTTP_ROOT . $this->url;
		}
		
	}
?>