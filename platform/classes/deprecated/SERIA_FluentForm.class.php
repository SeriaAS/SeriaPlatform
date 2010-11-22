<?php
	class SERIA_FluentForm extends SERIA_Form
	{
		// $className is provided because late static binding is not supported before
		/**
		*	Provided for backward compatability. Can also be overridden to provide different
		*	forms. Should use TheClass::Fluent.
		*/
		public static function getFormSpec($className=false)
		{
			if($className===false) throw new Exception('Since PHP versions before 5.3 does not support Late Static Bindings, we need the class name.');
			if(SERIA_DEBUG) SERIA_Base::debug('Using deprecated SERIA_FluentForm::getFormSpec in '.$className);
			$spec = SERIA_Fluent::getFluent($className);
			return $spec['fields'];
		}

		/**
		*	Provided for backward compatability. Should use TheClass::Fluent.
		*/
		public static function caption($className=false)
		{
			if($className===false) throw new Exception('Since PHP versions before 5.3 does not support Late Static Bindings, we need the class name.');
			if(SERIA_DEBUG) SERIA_Base::debug('Using deprecated SERIA_FluentForm::caption in '.$className);
			$spec = SERIA_Fluent::getFluent($className);
			if(!isset($spec['caption']))
				return 'No caption declared in SERIA_FluentObject::Fluent';
			return $spec['caption'];
		}

		public function _handle($data)
		{
			$spec = SERIA_Fluent::getFluent(get_class($this));
			foreach($spec['fields'] as $fieldName => $spec)
			{
				if(isset($data[$fieldName]))
					$this->object->set($fieldName, $data[$fieldName]);
			}
			$this->object->save();
			return;
		}

		public function delete()
		{
			if($this->isDeletable())
				return $this->object->delete();
			return false;
		}

		public function isDeletable()
		{
			return $this->object->isDeletable();
		}
	}
