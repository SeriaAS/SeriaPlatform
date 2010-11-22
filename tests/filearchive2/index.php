<?php
	require_once(dirname(__FILE__).'/../../main.php');

	SERIA_ScriptLoader::loadScript('platform-widgets');
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">

<html>
	<head>
		<title>Test filearchive 2</title>
	</head>
	<body>
		<script type='text/javascript'>
			<!--
				function openSingleSelect()
				{
					SERIA.Filearchive2.openSingleSelect(function (value) {
						if (value)
							alert(value);
						else
							alert('Cancelled');
					});
				}
				function openMultiSelect()
				{
					SERIA.Filearchive2.openMultiSelect(function (value) {
						if (value) {
							for (var i = 0; i < value.length; i++)
								alert(value[i]);
						} else
							alert('Cancelled');
					});
				}
			-->
		</script>
		<div>
			<button onclick='openSingleSelect();' type='button'>Single-select</button>
		</div>
		<div>
			<button onclick='openMultiSelect();' type='button'>Multi-select</button>
		</div>
		<div>
			<input id='oops1' class='fileselect' type='hidden' name='test_fs' value=''>
		</div>
		<div>
			<input id='oops2' class='fileselect_url' type='text' name='test_fs_url' value=''>
		</div>
		<div>
			<input id='oops3' class='fileselect multiselect' type='hidden' name='test_fs_ms' value=''>
		</div>
	</body>
</html>