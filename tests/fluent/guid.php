<?php
	ini_set('memory_limit', '1000M');
	class guid {
		private $id, $last=false;
		function __construct($id) { $this->id = $id; $this->rand = str_pad(mt_rand(0,99999999), 8, '0', STR_PAD_LEFT);}

		function fetch()
		{
			$res = $this->id.$this->rand;

			if($this->last===false)
				$this->last = time();

			$res .= str_pad(substr($this->last++,1), 9, '0', STR_PAD_LEFT);

			return $res;
		}
	}

$g = new guid(0);
for($i = 0; $i < 1000; $i++)
	echo $g->fetch()."\n";
die();

	$total = 60 * 60 * 24 * 365;
	for($t = 0; $t < $total; $t++)
	{
		echo "Left: ".($total-$t)."  \r";

		$used = array();
		$p = array();
		for($i = 0; $i < 100; $i++)
		{
			$p[] = new guid(0);
		}

		for($i = 0; $i < 50; $i++)
		{
			$r = $p[mt_rand(0,99)]->fetch();
			if(isset($used[$r]))
				die("COLLISION");
			$used[$r] = true;
		}
	}
	echo "\n";
