<?php
// this->_head must be added
//$applicationIcons = array(array('caption' => '', 'icon' => 'url', 'onclick' => ''));

SERIA_ScriptLoader::loadScript('jQuery-ui');
SERIA_ScriptLoader::loadScript('jQuery-treeview');
SERIA_ScriptLoader::loadScript('jQuery-ui-tabs');
if(SERIA_Base::user() !== false)
	SERIA_ScriptLoader::loadScript('SERIA-Platform-Private');
else
	SERIA_ScriptLoader::loadScript('SERIA-Platform-Public');
SERIA_ScriptLoader::loadScript('SERIA-Platform-Common');

$subMenu = array();
if($subMenuItems = $gui->getMenuItemsLevel(2))
	foreach($subMenuItems as $item)
		$subMenu[] = array("caption" => $item['title'], 'onclick' => 'location.href="'.$item['url'].'";');

if($sideMenuItems = $gui->getMenuItemsLevel(4))
{
	$sideMenu = '<ul class="sidemenu">';
	foreach($sideMenuItems as $item)
	{
		$sideMenu .= '<li><a href="'.$item['url'].'">'.$item['title'].'</a></li>';
	}
	$sideMenu .= '</ul>';
	$l3 = $gui->getActiveMenuItemLevel(3);
	$blocks[] = array('caption' => $l3['title'], 'contents' => $sideMenu);
}

if($sideMenuItems = $gui->getMenuItemsLevel(3))
{
	$sideMenu = '<ul class="sidemenu">';
	foreach($sideMenuItems as $item)
	{
		$sideMenu .= '<li><a href="'.$item['url'].'">'.$item['title'].'</a></li>';
	}
	$sideMenu .= '</ul>';
	$blocks[] = array('caption' => '', 'contents' => $sideMenu);
}


//$helpButton = array("caption" => "", "onclick" => "");
//$exitButton = array("caption" => "", "onclick" => "");
//$topMenu[] = array("caption" => "", "onclick" => "");
//$subMenu[] = array("caption" => "", "onclick" => "");
//$blocks[] = array('caption' => '', 'contents' => '')
//$secondaryBlocks[] = array('caption' => '', 'contents' => '')
//$sectionMenu = html for sidebar
//$contentsFrame = URL for iframe

// this template displays main blocks and secondary blocks at the same place
if(sizeof($secondaryBlocks))
	$blockContent = array_merge($blocks, $secondaryBlocks);

if(sizeof($blocks)===0)
	$blockContent = false;
else
{
	$blockContent = "";;
	foreach($blocks as $block)
	{
		$blockContent .= '<div class="block">
	<h4 class="legend">'.$block['caption'].'</h4>
	<div class="blockContents">'.$block['contents'].'</div>
</div>';
	}
}

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title><?php echo htmlspecialchars($title); ?></title>

		<script type='text/javascript'>
			SERIA.Template = {
				lockNavigation: function(message) {
					var m = '' + message;
					window.onbeforeunload = function(e) {
						var e = e || window.event;
						if(e)
							e.returnValue = m; 
						return m;
					}
				},
				unlockNavigation: function(message) {
					window.onbeforeunload = function() { };
				},
				reloadGUIElements: function() {
					jQuery("div.widget").each(function() {
						if(jQuery(this).hasClass('slider'))
						{
							var s = jQuery(this);
							var i = s.find('input');
							i.insertAfter(this);
							var settings = jQuery.parseJSON(i.attr('alt'));
							settings.value = i.val() ? i.val() : 0;
							settings.animate = true;
							settings.slide = settings.change = function(event,ui) {
								i.val(ui.value);
							}
							i.change(function(){
								s.slider('value', i.val());
							})
							s.slider(settings);
						}
					});
					jQuery(".treeview").treeview({
						animated: "fast",
						collapsed: true,
						persist: "location"
					});
					jQuery("div.tabs").tabs();
					// This code supports multiple tables per page resetting even/odd counter for each table
					jQuery('table.grid tbody').each(function(i) {
						var even = false;
						jQuery(this).children().each(function(i2) {
							if (even) {
								even = false;
								jQuery(this).addClass('even');
							} else {
								even = true;
								jQuery(this).addClass('odd');
							}
						});
					});

					// Adds rounded corners to input elements
/*
					jQuery('input:not(.ui-corner-all))').each(function(){
						jQuery(this).addClass('ui-corner-all');
					});
					jQuery('textarea:not(.ui-corner-all))').each(function(){
						jQuery(this).addClass('ui-corner-all');
					});
*/
					// Add ajax loading image to all containers
					jQuery('#ajaxAction').bind(
						"ajaxSend", function() { jQuery(this).fadeIn(250); }
					).bind(
						"ajaxComplete", function() { jQuery(this).fadeOut(250); }
					);

					jQuery('.develHelpMessageTitle').each(function(){
						jQuery(this).toggle(function(){
							jQuery(this).addClass('on').next().slideDown();
						}, function(){
							jQuery(this).removeClass('on').next().slideUp();
						});
					});
				}
			}
		
			jQuery(function(){
				SERIA.Template.reloadGUIElements();
				jQuery('.ui-state-error:first').clone().effect('scale', {percent:125}, 500).effect('scale',{percent:80},500).focus();
			});
		</script>
		<link rel="stylesheet" type="text/css" href="<?php echo SERIA_HTTP_ROOT; ?>/seria/platform/templates/seria/default.css?<?php echo time(); ?>" />
		<style type='text/css'>
			body {
			<?php if(!$isPopup) echo "background-image: url(".SERIA_HTTP_ROOT."/seria/platform/templates/seria/top-bg.gif);"; ?>
			}
		</style>
		<script type="text/javascript">
			var autoId = 0;
			jQuery(function(){

				var msgIn = function(o){
					if(!jQuery(o).data('actionFormError')) return;

					if(!jQuery(o).data('autoId'))
						jQuery(o).data('autoId', 'auto' + (autoId++));

					if(!document.getElementById(jQuery(o).data('autoId') + 'errorbox'))
					{
						jQuery('body').prepend('<div id="' + jQuery(o).data('autoId') + 'errorbox" class="ui-state-error ui-corner-all" style="padding: 0 0em; position: absolute; padding:0px 4px"><p style="line-height: 1em;margin:0px;padding:5px; padding-bottom:5px;"><span class="ui-icon ui-icon-alert" style="float:left; margin-right: .3em;"></span><span style="line-height: 1.1em">' + jQuery(o).data('actionFormError') + '</span></p></div>');
						var p = jQuery(o).offset();
						var w = jQuery(o).outerWidth();
						jQuery("#" + jQuery(o).data('autoId')+'errorbox').css({
							top: p.top,
							left: (p.left + w + 60),
							display: "none"
						});
					}

					var e = jQuery("#" + jQuery(o).data('autoId')+'errorbox');
					e.animate({left: '-=50', opacity: "show"}, 200);
				}
				var msgOut = function(o){
					if(!jQuery(o).data('actionFormError')) return;
					var e = jQuery("#" + jQuery(o).data('autoId')+'errorbox');
					e.animate({left: '+=50', opacity: "hide"}, 200);
				}

				jQuery('input.ui-state-error').hover(function(){
					if(!jQuery(this).data('has-focus')) msgIn(this); 
				}, function(){
					if(!jQuery(this).data('has-focus')) msgOut(this); 
				});
				jQuery('input.ui-state-error').focus(function(){
					jQuery(this).data('has-focus',true); 
					msgIn(this);
				}).blur(function(){
					jQuery(this).data('has-focus',false); 
					msgOut(this);
				});
			});
		</script>
	</head> 
	<body mnu="<?php 

$parts = array();
$items = $gui->getMenuItemsLevel();
foreach($items as $item)
	$parts[] = htmlspecialchars($item['title'].":top.location.href='".$item['url']."'");

echo implode("|", $parts);

?>">
<div id='ajaxAction'>
<img src="<?php echo SERIA_HTTP_ROOT; ?>/seria/platform/templates/seria/ajax-loader.gif" alt="" style='padding: 16px; vertical-align: middle;'>
</div>

	
	<?php if($isPopup) { ?>
	
	<div id='popupMain'><?php echo $contents; ?></div>
	
	<?php } else { ?>
	
	<script type='text/javascript'>
	
		$(function(){
			var seria_active = $("#topbar a.active,#topbar a.logoactive");
			$("#topbar a").hover(function(){
				var j = $(this);
				seria_makeActive(j);
			}, function() {
				seria_makeActive(seria_active);
			});
			$("#topbar a").click(function(){
				seria_active = $(this);
			});
		});
		function seria_makeActive(j)
		{
				$("#topbar a.prev, #topbar a.next, #topbar a.active").removeClass("prev").removeClass("next").removeClass("active").addClass("inactive");
				$("#topbar a.logoactive, #topbar a.logonext").removeClass("logonext").removeClass("logoactive").addClass("logo");
				$("#topbar span.end").removeClass('prev');
				if(j.hasClass('inactive') || j.hasClass('active') || j.hasClass('prev') || j.hasClass('next'))
				{ // j is a normal tab
					j.removeClass('inactive').addClass("active");
					j.prev(':not(.logo,.logoactive,.logonext)').removeClass('inactive').addClass('next');
					j.prev('.logo,.logoactive,.logonext').removeClass('logo').removeClass('logoactive').addClass('logonext');
				}
				else
				{ // j is logo tab
					j.removeClass('logo').removeClass("logonext").addClass("logoactive");
				}
				j.next(':not(.end)').removeClass('active').removeClass('inactive').addClass('prev');
				j.next('.end').addClass('prev');	
		}
	
	</script>
		<div id='topbar'>
<?php

$topMenu = array();

$activeApp = $gui->getActiveMenuItemLevel(0);
$topMenu = $gui->getMenuItemsLevel(1);

if($gui->getActiveMenuItemLevel(1))
	unset($activeApp['active']);
array_unshift($topMenu, $activeApp);

$topMenu = array_values($topMenu);

if($topMenu && sizeof($topMenu))
{
	$active = false;
	foreach($topMenu as $index => $item)
	{
		$topMenu[$index]['caption'] = $item['title'];
		$topMenu[$index]['onclick'] = 'top.location.href="'.$item['url'].'";';
		if(isset($item["active"]) && $item["active"])
			$active = $index;
	}
			
	if($active===false)
		$topMenu[0]["active"] = true;
	foreach($topMenu as $index => $item)
	{
		if($index===0)
		{
			if(isset($topMenu[$index]["active"]) && $topMenu[$index]["active"])
			{ // this is active
				echo "<a href='#' onclick=\"".htmlspecialchars($item["onclick"])."\" class='logoactive'>";
			}
			else if(isset($topMenu[$index+1]) && isset($topMenu[$index+1]["active"]) && $topMenu[$index+1]["active"])
			{ // next is active
				echo "<a href='#' onclick=\"".htmlspecialchars($item["onclick"])."\" class='logonext'>";
			}
			else
			{
				echo "<a href='#' onclick=\"".htmlspecialchars($item["onclick"])."\" class='logo'>";
			}
			echo "<span class='left'></span><img src='".SERIA_CACHED_HTTP_ROOT."/seria/platform/templates/seria/heading-logo.png' alt='' %XHTML_CLOSE_TAG%><span class='right'></span></a>";
		}
		else
		{
			if(isset($topMenu[$index]["active"]) && $topMenu[$index]["active"])
			{ // this is active
				echo "<a href='#' onclick=\"".htmlspecialchars($item["onclick"])."\" class='active'>";
			}
			else if(isset($topMenu[$index+1]) && isset($topMenu[$index+1]["active"]) && $topMenu[$index+1]["active"])
			{ // next is active
				echo "<a href='#' onclick=\"".htmlspecialchars($item["onclick"])."\" class='next'>";
			}
			else if(isset($topMenu[$index-1]) && isset($topMenu[$index-1]["active"]) && $topMenu[$index-1]["active"])
			{ // prev is active
				echo "<a href='#' onclick=\"".htmlspecialchars($item["onclick"])."\" class='prev'>";
			}
			else
			{
				echo "<a href='#' onclick=\"".htmlspecialchars($item["onclick"])."\" class='inactive'>";
			}
			echo "<span class='left'></span><span class='caption'>".htmlspecialchars($item["caption"])."</span><span class='right'></span></a>";
		}
	}
	echo "<span class='end".($item["active"]?" prev":"")."'></span>";
}

?>
<?php
/**FRODE
			if($exitButton)
			{
				echo "<input style='float: right' type='button' onclick=\"".htmlspecialchars($exitButton["onclick"])."\" value=\"".htmlspecialchars($exitButton["caption"])."\">";
			}

			if($helpButton)
			{
				echo "<input style='float: right' type='button' onclick=\"".htmlspecialchars($helpButton["onclick"])."\" value=\"".htmlspecialchars($helpButton["caption"])."\">";
			}

			echo "
			<div class='title'>".htmlspecialchars($title).(SERIA_INSTALL?" <div class='important'> INSTALLATION MODE, CHECK '_config.php' </div>)":"")."</div>";
*/?>
		</div><?php


			echo "
	<div id='submenu'>";

if(sizeof($subMenu))
{
	echo '<ul>';
	foreach($subMenu as $item)
		echo "<li><a href='#' onclick=\"".htmlspecialchars($item["onclick"])."\">".htmlspecialchars($item['caption']).'</a></li>';
	echo '</ul>';
}
			echo "
	</div>
	<div id='contents' class='".($blockContent?"hasBlocks ":"").($contentsFrame?"contentsFrame":"")."'>
		<div id='contentsWrapper'>
";
			if($blockContent)
				echo "
			<div id='blocks'>".$blockContent."</div>";
			
			if($contentsFrame)
			{
// must use javascript to output iframe since we want to use the url hash to get the correct url
				echo "
<script type='text/javascript'>
	var u = document.location.hash.substring(1);
	if(!(u.substring(0,7)=='http://' || u.substring(0,8)=='https://'))
		u = \"".htmlspecialchars($contentsFrame)."\";

	document.write(\"<iframe id='main' frameborder='0' src=\\\"\" + u + \"\\\"></iframe>\");
</script>
";
SERIA_Template::body("IFRAMECONTENTS", "<script type='text/javascript'>".'

	function SERIA_IFrameResize() {
		var f = document.getElementById("main");
		var wh = $(window).height();
		var ww = $(window).width();
		f.style.height = (wh-121)  + "px";
		f.style.width = ww + "px";
	}

	SERIA_VARS.adminView = {
		currentURL : false
	}

	$("#main").load(function() {
		var c = $(this).contents()[0];
		try
		{
			var url = c.location.href;
			if(url != SERIA_VARS.adminView.currentURL)
			{
				SERIA_VARS.adminView.currentURL = url;
				location.href="#" + url;
				document.title = c.title;
				window.title = c.title;
			}
		}
		catch(e)
		{
			if(SERIA_VARS.adminView.currentURL!==false)
			{
				SERIA_VARS.adminView.currentURL = false;
				// navigated off site
				alert("You have left the site editable by Seria Publisher. To return, click the Seria Publisher logo.");
			}
		}
	});

	$(window).resize(SERIA_IFrameResize);
	$(window).ready(SERIA_IFrameResize);
'."</script>
<style type='text/css'>
	html { overflow: hidden; }
	#contents { padding: 0px; }
</style>
");

			}
			else
			{
				$messages = SERIA_HtmlFlash::getMessages();
				if(sizeof($messages)>0)
				{
					echo "<div style='position:absolute'><div id='ui-notices' style='display:none;'>";
					foreach($messages as $class => $text)
					{
						if($class=="flashNotice")
						{
							echo '<div class="ui-widget"><div class="ui-state-highlight ui-corner-all" style=""><p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>'.$text.'</p></div></div>';
						}
						else if($class=="flashError")
						{
							echo '<div class="ui-widget"><div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"><p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>'.$text.'</p></div></div>';
						}
					}
					echo "</div><script type='text/javascript'>jQuery(function(){jQuery('#main h1:first').after(jQuery('#ui-notices').show('drop', {a:123}, 500));});</script></div>";
				}

				echo "<div id='main' style='min-height: 600px'>";
				echo $contents;

				if(SERIA_DEBUG && isset($debugMessages) && sizeof($debugMessages))
				{
					$develHelp = SERIA_Base::getDevelHelp();
					if(sizeof($develHelp))
					{
						$develHelpHtml = "<div class='develHelpMessages'><h1>"._t("Developer aid")."</h1>";
						foreach($develHelp as $help)
						{
							$develHelpHtml .= "<div class='develHelpMessage'><h2 class='develHelpMessageTitle'>".$help['title']."</h2><div class='develHelpContents'>".$help['html']."</div></div>";
						}
						$develHelpHtml .= "</div>";
					}
					else $develHelpHtml = "";
					echo "<pre class='debugMessages' style='margin-top: 30px'>";
					echo $develHelpHtml;
					foreach($debugMessages as $debugMessage)
						echo str_pad(round($debugMessage['time'],4), 6, '0')."/".floor($debugMessage['memory']/1024)."KB: ".$debugMessage['message']."\n";
					echo round(microtime(true)-$GLOBALS['seria']['microtime'],4).": FINISHED";
					echo "</pre>";
				}

				SERIA_Template::head("flashHide","<script type='text/javascript'>

					jQuery(function(){
						$('.flashMessages').fadeIn(1000);

						jQuery('.flashMessages p').each(function(){
							jQuery(this).prepend('<a href=\"#\" class=\"close\" onclick=\"jQuery(this).parent().slideUp(400);\">x</a>');
						});

						var hideThem = function(){jQuery('.flashMessages').fadeOut(400);}

						jQuery(':input').change(hideThem).keydown(hideThem);

					});
</script>");
				echo "</div>";
			}
			echo "
			<div style='clear:both'></div>
		</div>
	</div>";?>
		<?php } ?>

<style type='text/css'>
	/*
	 * We need to reserve space at the bottom for the position fixed
	 * toolbar that otherwise would cover the lower 47 pixels of
	 * content
	 */
	#toolbarwrapper_reserved_space {
		overflow: hidden;
		width: 20px;
		height: 47px;
	}
	#toolbarwrapper {
		position: fixed; 
		bottom: 0px; 
		left: 0px; 
		right: 0px; 
		background-color: #555; 
		height: 47px; /* Update the reserved space above also (#toolbarwrapper_reserved_space) */
		padding: 1px; 
		border-top: 1px solid #444;
	}
	#toolbar {
		position: fixed; 
		bottom: 0px; 
		left: 0px; 
		right: 0px; 
		height: 47px; 
		padding: 1px; 
	        width: 980px;
	        font-weight: bold;
	        margin: auto;
	}
	#toolbar .container {
		background-color: blue;
		float: left;
		height: 40px;
		padding: 2px;
		position: relative;
		border: 1px solid #444;
		background-color: #333;
		vertical-align: middle;
		-ms-border-radius: 6px;
		-moz-border-radius: 6px;
		-webkit-border-radius: 6px;
		border-radius: 6px;
	}
	#toolbar .container.text {
		line-height: 40px;
		font-size: 15px;
		color: #ccc;
		font-weight: bold;
		padding-left: 10px;
		padding-right: 10px;
	}
	#toolbar .container.text.warning {
		background-color: #772222;
	}
	#toolbar #applicationicons img {
		cursor: pointer;
		filter:alpha(opacity=50)
		-moz-opacity: 0.5;
		-ms-opacity: 0.5;
		-webkit-opacity: 0.5;
		opacity: 0.5;
		height: 40px; 
		border: 0px solid black;
		-ms-border-radius: 4px;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
		border-radius: 4px;
		background-color: #444;
		margin-right: 1px;
	}
	#toolbar #applicationicons img.active {
		filter:alpha(opacity=100)
		-moz-opacity: 1;
		-ms-opacity: 1;
		-webkit-opacity: 1;
		opacity: 1;
		background-color: #666;
	}
</style>

<?php
if(!$isPopup)
{
	echo '<div id="toolbarwrapper_reserved_space"></div><div id="toolbarwrapper"><div id="toolbar">';
	$icons = $gui->getMenuItems();
	if(sizeof($icons))
	{
		echo '<div class="container" id="applicationicons">';
		foreach($icons as $icon)
		{
			$contextMenu = array();
			$contextItems = $gui->getMenuItems($icon['id']);
			foreach($contextItems as $contextItem)
				$contextMenu[] = $contextItem['title'].":top.location.href='".$contextItem["url"]."'";
			$contextMenu = implode("|", $contextMenu);
			echo '<img mnu="'.$contextMenu.'" '.(isset($icon['active'])&&$icon['active']?'class="active" ':'').'onclick=\'location.href="'.$icon['url'].'";\' src="'.$icon['icon'].'" alt="'.$icon['title'].'">';
		}
		echo '<span id="applicationCaption"></span></div>';
		SERIA_Template::head('applicationCaptionEffects', '
<script type="text/javascript">
$(function(){
	$("#applicationicons img").hover(function(){
		if($(this).hasClass("active"))
			$(this).stop().animate({opacity: 1, backgroundColor: "#777777"}, 400);
		else
			$(this).stop().animate({opacity: 1, backgroundColor: "#666666"}, 400);
	}, function(){
		if($(this).hasClass("active"))
			$(this).stop().animate({opacity: 1, backgroundColor: "#666666"}, 400);
		else
			$(this).stop().animate({opacity: 0.5, backgroundColor: "#444444"}, 400);
	});
});
</script>
');
	}
	if($user = SERIA_Base::user())
	{
		echo '<div class="container text" style="cursor:pointer">'.$user->get('display_name').'</div>';
		echo '<div class="container text" style="cursor:pointer" onclick="top.location.href=\''.SERIA_HTTP_ROOT.'/seria/logout.php\'">'._t("Logout").'</div>';
	}


	if(SERIA_DEBUG || SERIA_INSTALL)
	{
		echo '<div class="container text warning">Seria Platform is operating in install mode</div>';
	}
	echo '</div></div>';

}
?>
</body>
</html>
