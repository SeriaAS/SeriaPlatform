<?php
	/**
	*	@author Frode Børli (www.seria.no)
	*	@version 1.0
	*	@package SERIA_Counter
	*
	*	This component makes generating statistics easier than ever before. It is based on maintaining several counters, incrementing
	*	them each. For example one counter for all hits, another counter for all hits in 2010, a third counter for all hits in November 2010 etcetera.
	*
	*	Although the current implementation does not scale very well, it should be suitable for most websites and it can be heavily optimized by
	*	using memcached or sharding.
	*
	*	The primary benefit of this method is that statistics are instantly available and updated to the second.
	*	The largest drawback is that it is not based on using log files, so it is difficult to go back in time and
	*	regenerate counters that you forget to use.
	*
	*	Usage example:
	*
	*	$counter = new SERIA_Counter('my_namespace');
	*	// Count the event "view" for the year 2010, the month november 2010, hour 16, category 3123 etc.
	*	$counter->count(array('y=2010','y=2010&m=11','month=11','total','h=16','category=3123'));
	*
	*	echo "Total views in 2010: ".$counter->get("y=2010")."<br>";
	*	echo "At 16 o'clock every day since this article was published, it have been viewed ".$counter->get("h=16")." times<br>";
	*
	*	Optimization:
	*
	*	If you keep the object instance, updates will be batched together in highly optimized queries.
	*/
	class SERIA_CounterManifest {
		const SERIAL = 4;

		const LEGAL_HOOK = 'SERIA_CounterManifest::LEGAL_HOOK';

		public static $database = array(
			'creates' => array(
				"CREATE TABLE {counters_memory} (id VARCHAR(100), counter BIGINT, PRIMARY KEY(id)) ENGINE = MEMORY DEFAULT CHARSET utf8",
				"CREATE TABLE {counters} (id VARCHAR(100), counter BIGINT, PRIMARY KEY(id)) ENGINE = InnoDB DEFAULT CHARSET utf8",
			),
		);
	}

	SERIA_Base::addClassPath(SERIA_ROOT.'/seria/components/SERIA_Counter/classes/*.class.php');
	SERIA_Hooks::listen(SERIA_MAINTAIN_5_MINUTES_HOOK, array('SERIA_Counter', 'commitMemory'));
