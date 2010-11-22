<?php

SERIA_Base::db()->exec('
UPDATE `' . SERIA_PREFIX . '_filetypes` SET mimetype = '.SERIA_Base::db()->quote('image/jpeg').' WHERE mimetype = '.SERIA_Base::db()->quote('image/pjpeg').'
');

?>