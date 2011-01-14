<?php
	class SERIA_CsvDictionary extends SERIA_Dictionary implements IteratorAggregate
	{
		private $data = false;

		function __construct($config)
		{
			parent::__construct($config);
			if(!isset($this->config['delimiter'])) $this->config['delimiter'] = ";";
			if(!isset($this->config['newline'])) $this->config['newline'] = "\n";
			else
			{
				$this->config['newline'] = str_replace(array('\r','\n','\t'), array("\r","\n","\t"), $this->config['newline']);
			}
			if(!isset($this->config['keyColumn'])) $this->config['keyColumn'] = 0;
			else $this->config['keyColumn'] = intval($this->config['keyColumn']);
			if(!isset($this->config['charset'])) $this->config['charset'] = 'utf-8';
		}

		function load()
		{
			if($this->data !== false) return;

			$this->data = array();
			$data = explode($this->config['newline'], mb_convert_encoding(file_get_contents($this->config['file']), 'utf-8', $this->config['charset']));

			if(isset($this->config['valueColumn']))
			{ // duplicate code (see else below) is an optimization!
				foreach($data as $line)
				{
					$components = explode($this->config['delimiter'], trim($line));
					if(trim($components[$this->config['valueColumn']]))
						$this->data[$components[$this->config['keyColumn']]] = $components[$this->config['valueColumn']];
				}
			}
			else if(isset($this->config['valueColumns']))
			{
				$keeps = explode(",", $this->config['valueColumns']);
				foreach($keeps as $key=>$keep)
					$keeps[$key] = intval($keep);
				foreach($data as $line)
				{
					$components = explode($this->config['delimiter'], trim($line));
					$val = array();
					foreach($keeps as $keep)
						$val[] = $components[$keep];
					$this->data[$components[$this->config['keyColumn']]] = $val;
				}
			}
			else
			{
				foreach($data as $line)
				{
					$components = explode($this->config['delimiter'], trim($line));
					$this->data[$components[$this->config['keyColumn']]] = $components;
				}
			}

			if(isset($this->config['sortByKey']))
				ksort($this->data);
			else if(isset($this->config['sortByValue']))
				asort($this->data);
		}

		function get($key)
		{
			$this->load();
			if(isset($this->data[$key]))
				return $this->data[$key];
			return NULL;
		}

		function getIterator()
		{
			$this->load();
			return new ArrayIterator($this->data);
		}
	}
