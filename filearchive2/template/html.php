<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title><?php echo htmlspecialchars($title); ?></title>
	</head>
	<?php
		$menu = array();
		if (SERIA_DEBUG) {
			?>
				<script type='text/javascript'>
					<!--
						var filepageDebugOutput = "";

						var showFilepageDebug = function() {
							return function () {
								wind = window.open('','Debug output','height=600,width=800,left=10,top=10,resizable=yes,scrollbars=yes,status=yes');
								wind.document.body.innerHTML = '<table><tr><td>' + filepageDebugOutput.replace(/\n/g, '</td></tr><tr><td>').replace(/\t/g, '</td><td>') + '</td></tr></table>';
							}
						}();
					-->
				</script>
			<?php
			$menu[] = _t('Show filepage debug output').':showFilepageDebug();';
		}
		$menu = implode('|', $menu);
	?>
	<body mnu="<?php echo htmlspecialchars($menu); ?>">
		<?php
			echo $contents;
			if (SERIA_DEBUG) {
				?>
					<!-- DEBUG LOG:
						<?php
							$debug = SERIA_Template::get('debugMessages');
							foreach ($debug as $msg) {
								echo $msg['time'].': '.$msg['message']."\n";
							}
						?>
					-->
				<?php
			}
		?>
	</body>
</html>