<?php
	abstract class SFluentObject
	{
		abstract public static function Fluent($instance=NULL); 
		/* EXAMPLE IMPLEMENTATION {
			return array(
				'fields' => array(
					'name' => array('name required', _t('Name')),
					'address' => array('address', _t('Address')),
				),
			);
		} END EXAMPLE IMPLEMENTATION */

		/**
		*	Special method that makes it possible for SFluent to manipulate Fluent Objects
		*/
		public function FluentBackdoor($action, $data=NULL)
		{
			switch($action)
			{
				case 'get_row' : return $this->row; break;
				case 'set_row' : $this->row = $data; break;
				default : throw new SERIA_Exception('Unknown action "'.$action.'".');
			}
		}

		/**
		*	Methods for getting and setting row data.
		*/
		private $row = array();
		public function get($name)
		{
			$spec = SFluent::_getSpec(get_class($this));

			if(!isset($spec['fields'][$name]))
				throw new SERIA_Exception('No such field '.$name);
			if(!isset($this->row[$name]))
				return NULL;
		
			switch($spec['fields'][$name]['fieldtype'])
			{
				case 'text' : case 'hidden' : case 'select' : case 'textarea' :
					return $this->row[$name];
				default : 
					if(class_exists($spec['fields'][$name]['fieldtype']))
						return SFluent::load($spec['fields'][$name]['fieldtype'], $this->row[$name]);
			}
			return NULL;
		}

		public function set($name, $value)
		{
			if($value instanceof SFluentObject)
				$value = $value->getKey();

			$spec = SFluent::_getSpec($this);
			if(!isset($spec['fields'][$name]))
				throw new SERIA_Exception('No such field '.$name);
			$this->row[$name] = $value;

			return true;
		}

                public final function __construct($p=false)
                {
                        if($p === false)
                        {
                                $this->row = array();
                        }
                        else if(is_array($p))
                        {
                                $this->row = $p;
                        }
                        else
                        {
				$spec = SFluent::getSpec($this);
                                $this->row = SERIA_Base::db()->query('SELECT * FROM '.$spec['table'].' WHERE '.(!empty($spec['primaryKey'])?$spec['primaryKey']:'id').'=:key', array('key' => $p))->fetch(PDO::FETCH_ASSOC);

                                if(!$this->row)
                                        throw new SERIA_NotFoundException('Could not find '.get_class($this).' with id='.$p);

                                $this->row = SFluent::_prepareRowForObject($this);
                        }
                }

	}
