<?php
class SERIA_UrlMetaField extends SERIA_Url implements SERIA_IMetaField
{
		public static function createFromUser($value)
		{
			return new SERIA_UrlMetaField($value);
		}
		public static function renderFormField($fieldName, $value, array $params=NULL, $hasError=false)
		{
			return SERIA_ActionForm::renderTag('input', $params, array(
				'type' => 'text',
				'name' => $fieldName,
				'id' => $fieldName,
			));
			return '<input type="'.htmlspecialchars($fieldName
		}
		public static function createFromDb($value)
		{
			return new SERIA_UrlMetaField($value);
		}
		public function toDbFieldValue()
		{
			return $this->_url;
		}
		public static function MetaField()
		{
			return array(
				'type' => 'varchar(200)',
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::URL)
				)),
			);
		}
	}
?>
