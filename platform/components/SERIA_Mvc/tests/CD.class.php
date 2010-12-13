<?php
	class CD extends SERIA_MetaObject {
		public static function Meta($instance=NULL) {
			return array(
				'table' => 'cder',
				'fields' => array(
					'title' => array('name required unique', _t('Title')),
					'gender' => array('gender required', _t('Artist gender')),
					'createdBy' => 'createdBy',
					'createdDate' => 'createdDate',
				),
			);
		}

		public static function createAction() { return self::editAction(); }
		public static function editAction($id=NULL)
		{
			if($id)
				$cd = SERIA_Meta::load('CD', $cd);
			else
				$cd = new CD();

			$a = new SERIA_ActionForm('cd', $cd, array('title','gender'));
			if($a->hasData())
			{
				$cd->set('title', $a->get('title'));
				$cd->set('gender', $a->get('gender'));
				$a->errors = SERIA_Meta::validate($cd);
				if(!$a->errors)
					$a->success = SERIA_Meta::save($cd);
			}

			return $a;
		}
	}



