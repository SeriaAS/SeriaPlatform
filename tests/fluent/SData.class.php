<?php
	/**
	*	A representation of tabular data with an sql interface. Could represent for example a database table, a CSV file or similar.
	*/
	abstract class SData implements Iterator {
		protected $where = NULL;
		protected $args = NULL;
		protected $start = 0;
		protected $length = NULL;
		protected $orderBy = NULL;
		final public function where($where, $args = NULL) {
			if($args instanceof SFluentObject)
				$args = $args->FluentBackdoor('get_row');
			if(is_array($args))
			{
				// merge the arguments here with possibly existing arguments in $this->args
				foreach($args as $key => $val)
					$this->args[$key] = $val;
			}
			if($this->where === NULL) $this->where = $where;
			else $this->where = $where." AND (".$this->where.")";
			return $this;
		}
		final public function limit($a,$b = NULL) {
			if($b === NULL)
			{
				$this->start = 0;
				$this->length = $a;
			}
			else
			{
				$this->start = $a;
				$this->length = $b;
			}
			return $this;
		}
		final public function order($field) {
			$this->orderBy = $field;
			return $this;
		}
	}
