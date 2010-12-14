<?php

	class SERIA_UserEditForm extends SERIA_Form
	{
		public static function caption()
		{
			return _t("Edit user");
		}

		public static function getFormSpec()
		{
			return array(
				'first_name' => array(),
				'last_name' => array(),
				'password' => array(),
				'email' => array(),
				'display_name' => array(),
				'username' => array(),
				'is_administrator' => array(),
				'is_guest' => array(),
			);
		}

		public function _handle($data)
		{
			$recvfields = array_keys($data);
			foreach(SERIA_UserEditForm::getFormSpec() as $fieldName => $spec)
			{
				/* This works for boolean fields that are for backw compat null/true */
				if(in_array($fieldName, $recvfields))
					$this->object->set($fieldName, $data[$fieldName]);
			}
			$this->object->save();
			return;
		}

		public function _delete()
		{
			return $this->object->delete();
		}

		public function isDeletable()
		{
			if(!$this->object)
				return false;
			
			return SERIA_Base::isAdministrator();
		}

	}
