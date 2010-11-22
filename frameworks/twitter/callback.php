<?php

require_once(dirname(__FILE__).'/../../main.php');

SERIA_Template::disable();

SERIA_Base::addFramework('twitter');

$twitter = new SERIA_TwitterSys(null);

$twitter->handleReturnFromTwitter();

?>