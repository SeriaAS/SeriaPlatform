<?php
        SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_payment_payex` (
                `id` int(10) unsigned NOT NULL auto_increment,
                `currency` varchar(100) NOT NULL,
                `language` varchar(100) NOT NULL,
                `shippingcost` TEXT,
                `transactionid` varchar(32) NOT NULL,
		`shippingaddress` TEXT,
		`productlines` TEXT,
		`billingaddress` TEXT,
		`orderstatus` varchar(32) NOT NULL,
                PRIMARY KEY  (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;');
?>
