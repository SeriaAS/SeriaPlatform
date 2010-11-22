<?php

SERIA_Base::db()->exec('ALTER TABLE `'.SERIA_PREFIX.'_widgets_poll_vote` ADD INDEX host_addr_searchindex (host_addr)');

?>