<?php
	/**
	*	Represents one slide attached to a presentation
	*/
	class Slide extends SERIA_MetaObject {
		public static function Meta($instance=NULL) {
			return array(
				'table' => 'live_slides',
				'fields' => array(
					'num' => array('integer required', _t("Slide number")),
					'width' => array('integer required', _t("Slide width")),
					'slideFile' => array('SlideFile required', _t("Slide file")),
					'path' => array('filepath', _t("Local file path")),
					'createdBy' => 'createdBy',
					'createdDate' => 'createdDate',
					'modifiedBy' => 'modifiedBy',
					'modifiedDate' => 'modifiedDate',
				),
			);
		}

		public function getHttpUrl() {
			return SERIA_FILES_HTTP_ROOT.'/'.$this->get('path');
		}
	}
