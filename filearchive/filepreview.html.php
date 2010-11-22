<?php if ($file) { ?>		
	<?php if ($file->isImage()) { ?>
		<img src="<?php echo $file->getThumbnailUrl(390, 290); ?>" alt="">
	<?php } elseif (strtolower($file->getExtension()) == 'flv') { ?>
		<object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' codebase='https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0' width='480' height='270' id='mainFrame' align='middle'>
		        <param name='allowScriptAccess' value='sameDomain' />
		        <param name='allowFullScreen' value='false' />
		        <param name='movie' value='<?php echo $src = SERIA_HTTP_ROOT . '/seria/platform/bin/seriaVideoPlayer.swf?videoSource=' . $file->get('url', array('rtmp', 'http(s)')) . '&autoRewind=true'; ?>' />
		        <param name='quality' value='high' /><param name='bgcolor' value='#ffffff' />
		        <embed src='<?php echo $src; ?>' quality='high' bgcolor='#ffffff' width='480' height='270' name='mainFrame' align='middle' allowScriptAccess='sameDomain' allowFullScreen='false' type='application/x-shockwave-flash' pluginspage='https://www.macromedia.com/go/getflashplayer' />
		</object>
	<?php } else { ?>
		<p><?php echo _t('No preview available for this content'); ?></p>
	<?php } ?>
<?php } ?>	