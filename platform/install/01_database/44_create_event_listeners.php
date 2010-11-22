<?php
	/**
	 * Create table used by SERIA_EventDispatcher and SERIA_EventListener
	 */
	SERIA_Base::db()->exec('CREATE TABLE '.SERIA_PREFIX.'_event_listeners (id INTEGER NOT NULL, source VARCHAR(50) NOT NULL, target VARCHAR(50) NOT NULL, eventName VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id))');
