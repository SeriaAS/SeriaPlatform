<?php

$stateID = $_GET['state'];

$state = SimpleSAML_Auth_State::loadState($stateID, sspmod_authfacebook_Auth_Source_Facebook::STAGE_INIT);
sspmod_simpleurl_UrlGenerator::runPageView($state, $_GET['id']);
