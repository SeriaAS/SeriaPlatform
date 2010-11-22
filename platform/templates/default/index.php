<?php

// this->_head must be added
//$helpButton = array("caption" => "", "onclick" => "");
//$exitButton = array("caption" => "", "onclick" => "");
//$topMenu[] = array("caption" => "", "onclick" => "");
//$sectionMenu = html for sidebar
//$contentsFrame = URL for iframe
?><!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"
            \"http://www.w3.org/TR/html4/strict.dtd\">
<html>
	<head>
		<title><?php echo htmlspecialchars($title); ?></title>

		<script type='text/javascript'>
			$(document).ready(function(){
				$(".treeview").treeview({
					animated: "fast",
					collapsed: true,
					persist: "location"
				});
				$(".tabs > ul").tabs();
				$("table.grid tbody tr:even").addClass("even");
				$("table.grid tbody tr:odd").addClass("odd");
			});
		</script>
		<style type='text/css'>
* {
	margin: 0px;
	padding: 0px;
	color: #555;
	font-size: 12px;
}
body {
	font-family: Arial, sans-serif;
}
li {
	margin-left: 20px;
}

.highlight:hover {
	background-color: #ded;
	cursor: hand;
}

table.grid {
	border-collapse: collapse;
}

table.grid {
	cursor: default;
}

table.grid thead th {
	text-align: left;
	background-color: #efe;
	border-bottom: 1px solid #ded;
	border-right: 1px solid #ded;
}

table.grid tbody tr.odd {
	background-color: #eee;
}

table.grid thead th.highlight:hover {
	background-color: #ded;
	cursor: hand;
}

table.grid tbody th {
	text-align: right;
	padding-right: 10px;
	font-weight: normal;
	vertical-align: top;
}

table.form {
	border-collapse: collapse;
}

table.form tbody th {
	text-align: left;
	vertical-align: top;
}

td {
	vertical-align: top;
}


fieldset {
	margin: 0px;
	padding: 10px;
}
h1,h2,h3,h4,h5,h6,p {
	margin-top: 10px;
	margin-bottom: 5px;
}
h1 {
	font-size: 15px;
}
h2 {
	font-size: 13px;
}
h3 {
	font-size: 12px;
}
h4,h5,h6 {
	font-size: 12px;
	font-weight: normal;
}
div.important {
	color: #f00;
	padding: 10px;
	border: 2px solid #eee;
	margin: 2px;
}
p.error {
	color: #ff0000;
}
div.fieldError {
	color: #ff0000;
}
#topbar {
	font-weight: bold;
	background-color: #aaf;
	border-bottom: 1px solid #ddf;
}
#topbar .title {
	padding: 6px;
	padding-left: 20px;
	color: #fff;
}
#topbar input {
	margin: 2px;
	padding: 1px;
}
#topmenu {
	background-color: #efe;
	border-bottom: 1px solid #ded;
	height: 25px;
	padding-left: 12px;
}
#topmenu a {
	display: block;
	float: left;
	background-color: #efe;
	padding: 2px 5px;;
	text-decoration: none;
	color: #444;
	margin: 3px;
}
#topmenu a:hover {
	background-color: #ded;
	color: #fff;
	padding: 1px 4px;
	border-top: 1px solid #efe;
	border-left: 1px solid #efe;
	border-bottom: 1px solid #cdc;
	border-right: 1px solid #cdc;
}
#contents {
	padding: 0px;
}
#contents ul.checkbox {
	list-style-type: none;
}
#contents ul.checkbox label {
	padding-left: 5px;
}
#sectionmenu {
	width: 200px; 
	float: left; 
	margin-right: 10px;
}
#sectionmenu ul {
	list-style-type: none;
}
#sectionmenu ul li {
	margin-left: 5px;
}
#sectionmenu ul li a.selected {
	font-weight: bold;
}

#footer {
	clear: both;
	text-align: center;
	color: #aaa;
}
#footer a {
	color: #999;
	text-decoration: none;
}
p.warning {
	display: block;
	padding: 20px;
	border: 1px solid red;
}
#contents {
	padding: 20px;
}

div.previewFrame {
	width: 50px;
	height: 50px;
	border: solid 1px #bbbbbb;
	float: left;
	margin-right: 5px;
}

div.previewFrame img {
}

div.fileData {
	display: inline;
}

div.filePreviewBox {
	position: relative;
	background-color: #dedede;
	display: none;
	float: left;
	clear: both;
}

table.grid {
	min-width: 500px;
	width: auto !important;
	width: 500px;
}

th {
	padding: 1px 10px 1px 1px;
}

table.grid tr td {
	padding: 1px 5px 1px 1px;
}

tr.tableNoElements td {
	text-align: center;
}

div.flashMessages p {
	border: solid 1px #999999;
	padding: 3px;
}

div.flashMessages p.flashNotice {
	background-color: #bbffbb;
}

div.flashMessages p.flashError {
	background-color: #ff9999;
}


		</style>
	</head> 
	<body>
		<div id='topbar'><?php
		
			if($exitButton)
			{
				echo "<input style='float: right' type='button' onclick=\"".htmlspecialchars($exitButton["onclick"])."\" value=\"".htmlspecialchars($exitButton["caption"])."\">";
			}

			if($helpButton)
			{
				echo "<input style='float: right' type='button' onclick=\"".htmlspecialchars($helpButton["onclick"])."\" value=\"".htmlspecialchars($helpButton["caption"])."\">";
			}

			echo "
			<div class='title'>".htmlspecialchars($title).(SERIA_INSTALL?" <span style='color:red;background-color:white;'> INSTALLATION MODE, CHECK '_config.php' </span>)":"")."</div>
		</div>";

			if($topMenu)
			{
				echo "
		<div id='topmenu'>";
				$items = array();
				foreach($topMenu as $item)
					$items[] = "<a href=\"#\" onclick=\"".htmlspecialchars($item["onclick"])."\">".htmlspecialchars($item["caption"])."</a>";
				echo implode(" ", $items);
				echo "
		</div>";
			}

			echo "
		<div id='contents'>";
			if($sectionMenu)
				echo "
			<fieldset id='sectionmenu'>".$sectionMenu."</fieldset>";

			if($contentsFrame)
			{
				echo "
<script type='text/javascript'>
	var u = document.location.hash.substring(1);
	if(!(u.substring(0,7)=='http://' || u.substring(0,8)=='https://'))
		u = \"".htmlspecialchars($contentsFrame)."\";

	document.write(\"<iframe id='main' frameborder='0' src=\\\"\" + u + \"\\\"></iframe>\");
</script>
";

				SERIA_Template::body("IFRAMECONTENTS", "<script type='text/javascript'>".'

	function SERIA_iFrameResize() {
		var f = document.getElementById("main");
		var wh = $(window).height();
		var ww = $(window).width();
		f.style.height = (wh-54)  + "px";
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

	$(window).resize(SERIA_iFrameResize);
	$(window).ready(SERIA_iFrameResize);
'."</script>
<style type='text/css'>
	html { overflow: hidden; }
	#contents { padding: 0px; }
</style>
");

			}
			else
			{
				echo "
			<fieldset id='main'>".$contents."</fieldset>";
			}
			echo "
		</div>
	</body>
</html>";

?>