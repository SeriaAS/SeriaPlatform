<?php

SERIA_ScriptLoader::loadScript('Timer');

SERIA_Template::cssInclude(SERIA_Filesystem::getCachedUrlFromPath(dirname(__FILE__).'/style.css'));

if (!isset($json) || $json !== true) {
	$content_id = 'fileinfo_content'.mt_rand();
	?>
		<div id="<?php echo htmlspecialchars($content_id); ?>">
	<?php
}
$locale = SERIA_Locale::getLocale();

if ($selected) {
	/*
	 * Show file info
	 */
	if (count($selected) == 1) {
		?>
		<h2><?php echo htmlspecialchars(_t('Selected file:')); ?></h2>
		<?php
	}
	if (count($selected) > 1) {
		$myid = 'filedetails'.mt_rand();
		?>
		<div class='multiselect_info'>
			<h2 class='multiselect_info_head' onclick="var tobj = document.getElementById('<?php echo $myid; ?>'); tobj.toggleDetails();"><?php echo htmlspecialchars(_t('%NUM% files selected.', array('NUM' => count($selected)))); ?></h2>
			<div class='multiselect_details' id="<?php echo $myid; ?>">
				<div class='contentFrame'>
					<ul>
					<?php
					foreach ($selected as $file) {
						?>
						<li><?php echo htmlspecialchars($file['name']); ?></li>
						<?php
					}
					?>
					</ul>
				</div>
			</div>
		</div>
		<?php
	} else {
		$keys = array_keys($selected);
		$file = $selected[array_shift($keys)];
		?>
			<table>
				<tr>
					<th><?php echo htmlspecialchars(_t('Filename: ')); ?></th>
					<td><?php echo htmlspecialchars($file['name']); ?></td>
				</tr>
				<tr>
					<th><?php echo htmlspecialchars(_t('Filesize: ')); ?></th>
					<td><?php echo SERIA_Format::filesize($file['filesize']); ?></td>
				</tr>
				<tr>
					<th><?php echo htmlspecialchars(_t('Uploaded at:')); ?></th>
					<td><?php echo $locale->timeToString($file['createdAt'], 'datetime'); ?></td>
				</tr>
			</table>
		<?php
	}
} else {
	?>
	<h2><?php echo htmlspecialchars(_t('No files selected')); ?></h2>
	<?php
}
if (!isset($json) || $json !== true) {
	?>
	</div>
	<script type='text/javascript'>
		<!--
			(function () {
				var files = <?php echo SERIA_Lib::toJSON(serialize($selected)); ?>;

				var updateDetailDisplay = function ()
				{
					$('.multiselect_details').each(function () {
							var obj = this;
							var shown = false;
							var id = obj.id;

							obj.toggleDetails = function ()
							{
								var tobj = document.getElementById(id);
								shown = !shown;
								tobj.style.display = (shown ? 'block' : 'none');
								if (shown) {
									tobj.ref = 0;
									var showing = true;
									SERIA.Timer.setTimeout(function (arg) {
										if (showing)
											tobj.toggleDetails();
									}, 5000, false);
									var overriddenToggle = tobj.toggleDetails; 
									tobj.toggleDetails = function () {
										tobj.toggleDetails = overriddenToggle;
										if (showing) {
											showing = false;
											tobj.toggleDetails();
										}
									}
								}
							}
							obj.showDetails = function ()
							{
								var tobj = document.getElementById(id);

								if (!shown)
									tobj.toggleDetails();
							}
							obj.hideDetails = function ()
							{
								var tobj = document.getElementById(id);

								if (shown)
									tobj.toggleDetails();
							}

							obj.onmouseover = function () {
								var tobj = document.getElementById(id);
								tobj.ref++;
							}
							obj.onmouseout = function () {
								var tobj = document.getElementById(id);
								tobj.ref--;
								SERIA.Timer.setTimeout(function (arg) {
									if (tobj.ref <= 0)
										tobj.hideDetails();
								}, 1000, false);
							}
							$(obj).find().each(function () {
								this.onmouseover = obj.onmouseover;
								this.onmouseout = obj.onmouseout;
							});

							obj = false; /* unref */
					});
				}

				var parentSelectFile = selectFile;
				selectFile = function (fileArticleId)
				{
					$.post(
						"<?php echo SERIA_HTTP_ROOT ?>/seria/filearchive2/json/selectnew.php",
						{
							"files": files,
							"selectNew": fileArticleId
						},
						function (data) {
							if (data.error) {
								alert(data.error);
								return;
							}
							files = data.files;
							document.getElementById(<?php echo SERIA_Lib::toJSON($content_id); ?>).innerHTML = data.html;
							updateDetailDisplay();
						},
						'json'
					);
					parentSelectFile(fileArticleId);
					return false;
				}
				var parentUnselectFile = unselectFile;
				unselectFile = function (fileArticleId)
				{
					$.post(
						"<?php echo SERIA_HTTP_ROOT ?>/seria/filearchive2/json/selectnew.php",
						{
							"files": files,
							"unselect": fileArticleId
						},
						function (data) {
							if (data.error) {
								alert(data.error);
								return;
							}
							files = data.files;
							document.getElementById(<?php echo SERIA_Lib::toJSON($content_id); ?>).innerHTML = data.html;
							updateDetailDisplay();
						},
						'json'
					);
					parentUnselectFile(fileArticleId);
					return false;
				}
			})();
		-->
	</script>
	<?php
}
