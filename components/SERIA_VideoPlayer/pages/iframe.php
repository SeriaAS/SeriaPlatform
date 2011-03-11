<?php
	$video = SERIA_NamedObjects::getInstanceByPublicId($_GET['objectKey'], 'SERIA_IVideoData');
	$vd = $video->getVideoData();
	$sources = $vd['sources'];
	$source = current($sources);
	$source = $source['src'];
	$flashVars = array(
		'httpRoot' => rawurlencode(SERIA_HTTP_ROOT),
		'objectKey' => $_GET['objectKey'],
		'debugMode' => ((SERIA_Base::isLoggedIn() && $_GET["debugMode"]) ? 'true' : ''),
	);

	function arrayToFlash($flashvarsArray)
	{
		$flashvarString = '';
		foreach($flashvarsArray as $key => $val) {
			if(!empty($val) && ($val != '') && isset($val))
				$flashvarString .= $key.'='.$val.'&';
		}
		return $flashvarString;
	}

	$flashVars = arrayToFlash($flashVars);

?><!DOCTYPE html>
<html>
	<head>
		<title>Seria WebTV Player for embedding</title>
		<link rel='stylesheet' href='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/player.css?<?php echo mt_rand();?>' type='text/css'>
		<script type='text/javascript' language='javascript'>
			window.videoData = {
				poster: <?php echo SERIA_Lib::toJSON($vd['previewImage']); ?>,
				sources: <?php echo SERIA_Lib::toJSON($vd['sources']); ?>,
				objectKey: <?php echo intval($_GET['objectKey']); ?>
			};
		</script>
		<script src='<?php echo SERIA_HTTP_ROOT; ?>/seria/platform/js/SERIA.js' type='text/javascript'></script>
		<script src='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/flash_detect.js' type='text/javascript' language='javascript'></script>
		<script src='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/player.js?<?php echo mt_rand();?>' type='text/javascript' language='javascript'></script>
		<script src='http://ajax.microsoft.com/ajax/jquery/jquery-1.5.min.js' type='text/javascript' language='javascript'></script>
	</head>
	<body><?php
		if(defined('SERIA_VIDEOPLAYER_SKIN')) require(SERIA_VIDEOPLAYER_SKIN);
		else require(SERIA_ROOT.'/seria/components/SERIA_VideoPlayer/assets/skin.php');
?>
		<object id='ieflash' classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' width='100%' height='100%'>
			<param name='movie' value='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/bin/SeriaPlayer.swf'></param>
			<param name='allowFullscreen' value='always'></param>
			<param name='wmode' value='window'></param>
			<param name='allowscriptaccess' value='always'></param>
			<param name='flashvars' value='<?php echo $flashVars; ?>'></param>
			<!--[if !IE]>-->
				<object id='nieflash' type='application/x-shockwave-flash' data='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/bin/SeriaPlayer.swf' width='100%' height='100%'>
					<param name='flashvars' value='<?php echo $flashVars; ?>'></param>
					<param name='allowscriptaccess' value='always'></param>
					<param name='wmode' value='window'></param>
					<param name='allowFullscreen' value='true'></param>
					<video controls id='video' poster='<?php echo $vd['previewImage']; ?>' src='<?php echo $source; ?>'>
						<?php foreach($sources as $source) echo "<source src='".$source['src']."'".(!empty($source['type'])?" type='".$source['type']."'":"").(!empty($source['media'])?" media='".$source['media']."'":"").">"; ?>
					</video>
				</object>
			<!--<![endif]-->
			<!--[if IE]>
				<video controls id='video' poster='<?php echo $vd['previewImage']; ?>' src='<?php echo $source; ?>'>
					<?php foreach($sources as $source) echo "<source src='".$source['src']."'".(!empty($source['type'])?" type='".$source['type']."'":"").(!empty($source['media'])?" media='".$source['media']."'":"").">"; ?>
				</video>
			<![endif]-->
		</object>");
</script>
	</body>
</html>
