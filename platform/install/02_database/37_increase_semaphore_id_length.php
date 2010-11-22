<?php

SERIA_Base::db()->exec('ALTER TABLE {semaphores} MODIFY id VARCHAR(128)');
