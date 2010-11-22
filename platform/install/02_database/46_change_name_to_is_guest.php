<?php

SERIA_Base::db()->exec('ALTER TABLE {users} CHANGE guestAccount is_guest TINYINT(1) DEFAULT 0 NOT NULL');
