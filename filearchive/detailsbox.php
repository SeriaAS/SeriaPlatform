<?php if (!defined('FROM_POPUP')) { die(); } ?>
<?php
	$previewUrl = '';
	try {
		list($previewUrl, $previewWidth, $previewHeight) = $file->getThumbnail(100, 100);
	} catch (Exception $null) {
		$previewUrl = '';
		$previewWidth = 0;
		$previewHeight = 0;
	}
	
	$filename = $file->get('filename');
	$filename = wordwrap($filename, 12, "\n", true);
	$filename = htmlspecialchars($filename);
	$filename = str_replace(array("\r", "\n"), array('', '<br />'), $filename);
?>
<script type="text/javascript">
	$( function () {
		var icon = $('#fileIcon_<?php echo $type; ?>_<?php echo $file->get('id'); ?>');
		
		if (! $('#detailsbox<?php echo $file->get('id'); ?>').attr('id')) {
			$('body').append('' +
				'<div class="fileDetailsBox" id="detailsbox<?php echo $file->get('id'); ?>">' +
				'	<p><?php if ($previewUrl) { ?>' +
				'		<img align="left" src="<?php echo $previewUrl; ?>" width="<?php echo $previewWidth; ?>" height="<?php echo $previewHeight; ?>" alt="">' +
				'	<?php } ?>' +
				'	<span class="fileText"><strong class="filename"><?php echo $filename; ?></strong><br />' +
				'   <?php echo SERIA_Format::filesize($file->get('filesize')); ?></span>' +
				' </p>' +
				'</div>');
		}
	
		var detailsbox = $('#detailsbox<?php echo $file->get('id'); ?>');
		detailsbox.hide();
		detailsbox.css('width', <?php echo $previewWidth; ?> + 100);
		
		var mousemove = function(e) {
			if (fileSelecting) {
				return;
			}
			
			if (detailsbox.css('display') == 'none') {
				detailsbox.show();
			}
			
			var x = e.pageX + 5;
			var y = e.pageY + 5;
			var body = $('body')
			
			var width = 0;
			var height = 0;
			
			var windowWidth = 0;
			var windowHeight = 0;
			if( typeof( window.innerWidth ) == 'number' ) {
				//Non-IE
				windowWidth = window.innerWidth;
				windowHeight = window.innerHeight;
			} else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
				//IE 6+ in 'standards compliant mode'
				windowWidth = document.documentElement.clientWidth;
				windowHeight = document.documentElement.clientHeight;
			} else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
				//IE 4 compatible
				windowWidth = document.body.clientWidth;
				windoeHeight = document.body.clientHeight;
			}
			
			if (x + (width = parseInt(detailsbox.css('width'))) > windowWidth) {
				x = e.pageX - width;
				x = x - 20;
			}
			if (y + (height = parseInt(detailsbox.css('height'))) > windowHeight) {
				y = e.pageY - height;
				y = y - 15;
			}
			
			detailsbox.css({top: y, left: x});
		};
		
		icon.bind('mouseout', function(e) { detailsbox.hide(); });
		detailsbox.bind('mouseout', function(e) { detailsbox.hide(); });
		icon.bind('mousemove', mousemove);
		detailsbox.bind('mousemove', mousemove);
		
		
			
	});
</script>