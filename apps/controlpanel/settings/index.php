<?php
	require('common.php');
	SERIA_Base::pageRequires('admin');
	
	$menu->addLink(_t('Users'), SERIA_HTTP_ROOT . '/seria/settings/users.php');
	$menu->addLink(_t('User groups'), SERIA_HTTP_ROOT . '/seria/settings/usergroups/');
	
	$menu->addLink(_t('Memcached servers'), SERIA_HTTP_ROOT . '/seria/settings/memcached/');
	
	if (SERIA_STATIC_FTP) {
		$menu->addLink(_t('Mirror FTP servers'), SERIA_HTTP_ROOT . '/seria/settings/ftpservers/');
	}
	$menu->addLink(_t('Incoming FTP servers'), SERIA_HTTP_ROOT . '/seria/settings/incomingftpservers/');
	
	ob_start();
?>
	<h1 class="legend"><?php echo _t('Settings'); ?></h1>
	
	<p>
		<?php echo _t('Various settings for Seria Publisher. Select setting category in left menu.'); ?>
	</p>
	
	<h2><?php echo _t('Descriptions for setting categories'); ?></h2>
	<dl>
		<dt><a href="<?php echo SERIA_HTTP_ROOT . '/seria/settings/users.php'; ?>"><?php echo _t('Users'); ?></a></dt>
		<dd><?php echo _t('Create, delete, modify and set permissions for users.'); ?></dd>
		
		<dt><a href="<?php echo SERIA_HTTP_ROOT . '/seria/settings/usergroups/'; ?>"><?php echo _t('User groups'); ?></a></dt>
		<dd><?php echo _t('Set up user groups for grouping permissions. Each group can have permissions applied to all users in the group.') ?>
		
		<dt><a href="<?php echo SERIA_HTTP_ROOT . '/seria/settings/memcached/'; ?>"><?php echo _t('Memcached servers'); ?></a></dt>
		<dd><?php echo _t('Set up memcached servers for high performance caching and session handling.'); ?></dd>
		
		<?php if (defined('SERIA_STATIC_FTP') && SERIA_STATIC_FTP) { ?>
			<dt><a href="<?php echo SERIA_HTTP_ROOT . '/seria/settings/ftpservers/'; ?>"><?php echo _t('Mirror FTP servers'); ?></a></dt>
			<dd><?php echo _t('Set up FTP servers for file mirroring and streaming servers. Files uploaded is automatically mirrored on FTP servers for high performance access.'); ?></dd>
		<?php } ?>
		
		<dt><a href="<?php echo SERIA_HTTP_ROOT . '/seria/settings/incomingftpservers/'; ?>"><?php echo _t('Incoming FTP servers'); ?></a></dt>
		<dd><?php echo _t('Incoming FTP servers allows you to add direct access to files on a FTP server from file select window. Files selected is automatically downloaded to web server before usage.'); ?>
	</dl>
<?php
	$contents = ob_get_clean();
	$gui->contents($contents);
	
	
	$gui->output();
?>