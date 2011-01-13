<?php
	if(!SERIA_Multisite::isMaster())
		throw new SERIA_Exception('Access denied', SERIA_Exception::ACCESS_DENIED);
