<?php
	SERIA_Template::cssInclude(SERIA_Filesystem::getCachedUrlFromPath(dirname(__FILE__).'/style.css'));
?><script type='text/javascript'>
	<!--
		
		var selectFile;
		var unselectFile;
		var fileIsSelected;
		var getSelectedFiles;

		(function () {
			var files = new Array();
			var isMultiselect = <?php echo (isset($_GET['multiselect']) ? 'true' : 'false'); ?>

			var getIndexOf = function(fileArticleId)
			{
				for (var i = 0; i < files.length; i++) {
					if (files[i] == fileArticleId)
						return i;
				}
				return -1;
			}

			selectFile = function (fileArticleId)
			{
				if (!isMultiselect) {
					var selectedFiles = getSelectedFiles();
					for (var i = 0; i < selectedFiles.length; i++)
						unselectFile(selectedFiles[i]);
				}
				files.push(fileArticleId);
			}
			unselectFile = function (fileArticleId)
			{
				var index = getIndexOf(fileArticleId);

				if (index >= 0)
					files.splice(index, 1);
			}
			fileIsSelected = function (fileArticleId)
			{
				if (getIndexOf(fileArticleId) >= 0)
					return true;
				else
					return false;
			}
			getSelectedFiles = function()
			{
				selfiles = new Array();
				for (var i = 0; i < files.length; i++) {
					selfiles[i] = files[i];
				}
				return selfiles;
			}
		})();
		(function () {
			return; /* DISABLED! */

			var numVisible = 5; /* default */

			var setNumVisible = function (num) {
				if (num < 5)
					num = 5;
				if (num != numVisible) {
					numVisible = num;
					js_filearchive_filelist.setNumOnEachPage(num);
				}
			}
			$(document).ready(function () {
				var obj = document.getElementById('filearchive');
				var SW; // screen width
				var SH;

				if(obj.innerWidth) {
					SW = obj.innerWidth;
					SH = obj.innerHeight;
				} else {
					SW = obj.clientWidth;
					SH = obj.clientHeight;
				}

				/*
				 * Estimate the ideal number of visible icons
				 */
				var IconWidth = 120;
				var IconHeight = 120;
				var WidthCut = 250;
				var HeightCut = 90;
				var WAvail = SW - WidthCut;
				var HAvail = SH - HeightCut;
				if (WAvail <= 0 || HAvail <= 0)
						return;
				setNumVisible(Math.floor(WAvail / IconWidth) * Math.floor(HAvail / IconHeight));

			});
		})();
		$(document).ready(function () {
			<?php
				if (isset($_GET['preselected']) && ($_GET['preselected'] || $_GET['preselected'] === 0)) {
					$preSelected = explode(',', $_GET['preselected']);
					foreach ($preSelected as $nam => &$id) {
						try {
							$file = SERIA_File::createObject($id);
							$id = $file->get('file_article_id');
							if (!$id)
								unset($preSelected[$nam]);
						} catch (SERIA_NotFoundException $e) {
							$article = SERIA_Article::createObjectFromId($id);
							$id = $article->get('id');
						}
					}
					unset($id);
					if ($preSelected) {
						?>
						/*
						 * Pre-select files..
						 */
						<?php
						$dirid = SERIA_FileDirectory::getDirectoryIdOfFileArticleId($preSelected[0]);
						if ($dirid) {
							?>
							SERIA.DirectoryTree.openDirectory(<?php echo $dirid; ?>);
							<?php
						}
						?>

						<?php
						foreach ($preSelected as $sel) {
							?>
							selectFile(<?php echo $sel; ?>);
							<?php
						}
					}
				}
			?>
		});
	-->
</script>
<!-- <form id='filearchive_top_form'> -->
	<!--[if lt IE 8]>
		<table id="<?php echo htmlspecialchars(($window_ie7table = 'filearchive_window_ie7table')); ?>" border='0' cellspacing='0' cellpadding='0'><tr class='filearchive_window_row'><td class='filearchive_window_cell'>
	<![endif]-->
	<div class='disptable' id='filearchive_window'>
		<div class='disptable'>
			<div class='disptable'>
				<!--[if lt IE 8]>
					</div></div></div>
				<![endif]-->
				<div id='filearchive2_navbar'>
					<?php
						echo SERIA_Template::parseToString(dirname(__FILE__).'/navbar/main.php', array(
							'categories' => $categories,
							'filelist_id' => 'filearchive_filelist'
						));
					?>
				</div>
				<!--[if lt IE 8]>
					<div class='disptable'><div class='disptable'>
				<![endif]-->
			</div>
		</div>
		<!--[if lt IE 8]>
			</td></tr><tr id='filearchive_expanding_row_ie' class='filearchive_window_row'><td id='filearchive_expanding_cell_ie' class='filearchive_window_cell'>
		<![endif]-->
		<div class='disptable' id='filearchive_expanding_row'>
			<div class='disptable'>
				<!--[if lt IE 8]>
					</div></div>
					<table id="<?php echo htmlspecialchars(($ie7table = 'ie7table_cols')); ?>" border='0' cellspacing='0' cellpadding='0'><tr><td id='td_filecategories'>
				<![endif]-->
				<div class='disptable' id='filearchive'>
					<div class='disptable'>
						<div class='disptable' id='filecategories'>
							<!--[if lt IE 8]>
								</div></div></div>
							<![endif]-->
							<?php
								echo SERIA_Template::parseToString(dirname(__FILE__).'/categories/main.php', array(
									'categories' => $categories,
									'filelist_id' => 'filearchive_filelist'
								));
							?>
						<!--[if lt IE 8]>
							<div class='disptable'>
						<![endif]-->
						</div>
						<!--[if lt IE 8]>
							</td><td id='td_disp_table_void'>
						<![endif]-->
						<div id='disp_table_void'><span>&nbsp;</span></div>
						<div class='disptable' id='allfiles_display'>
							<!--[if lt IE 8]>
								</div>
								</td><td id='td_allfiles_display'>
							<![endif]-->
							<?php
								echo SERIA_Template::parseToString(dirname(__FILE__).'/fileicons/main.php', array(
									'files' => $files,
									'selected' => array(),
									'pagingInfo' => $pagingInfo,
									'json' => false,
									'filelist_id' => 'filearchive_filelist'
								));
							?>
						<!--[if lt IE 8]>
							<div class='disptable'>
						<![endif]-->
						</div>
						<!--[if lt IE 8]>
							<div class='disptable'><div class='disptable'>
						<![endif]-->
					</div>
				</div>
				<!--[if lt IE 8]>
					</td></tr></table>
					<div class='disptable'><div class='disptable'>
				<![endif]-->
			</div>
		</div>
		<!--[if lt IE 8]>
			</td></tr><tr class='filearchive_window_row'><td class='filearchive_window_cell'>
		<![endif]-->
		<div class='disptable'>
			<div class='disptable'>
				<!--[if lt IE 8]>
					</div></div>
				<![endif]-->
				<div id='filearchive2_selectbar'>
					<?php
						echo SERIA_Template::parseToString(dirname(__FILE__).'/selectbar/main.php', array(
							'categories' => $categories,
							'filelist_id' => 'filearchive_filelist'
						));
					?>
				</div>
				<!--[if lt IE 8]>
					<div class='disptable'><div class='disptable'><div class='disptable'>
				<![endif]-->
			</div>
		</div>
	</div>
	<!--[if lt IE 8]>
		</td></tr></table>
	<![endif]-->
<!--  </form> -->
<!--[if lt IE 8]>
	<script type='text/javascript'>
		/*
		 * Ugly IE7 patch.. Remove the divs inside the table cells
		 */
		$(document).ready(function () {
			/*
			 * Hide divs
			 */
			var c = 0;
			var disptables = $('div.disptable');
			disptables.each(function (index) {
				var dt = disptables[index];

				dt.style.display = 'none';
				c++;
			});

			var masterTable = document.getElementById('filearchive_window_ie7table');
			var expandingRow = document.getElementById('filearchive_expanding_cell_ie');
			var expandingTable = document.getElementById('ie7table_cols');
			/* */
			var topRowH = 50;
			var minMidRowH = 200;
			var bottomRowH = 50;

			var resizeTables = function () {
				var fullHeight = document.documentElement.clientHeight;
				var midRowH = masterTable.clientHeight - topRowH - bottomRowH;

				if (midRowH < minMidRowH)
					midRowH = minMidRowH;
				expandingRow.style.height = (midRowH) + 'px';
				expandingTable.style.height = (midRowH) + 'px';
			}
			$(window).resize(function () {
				resizeTables();
			});
			resizeTables();
		});
	</script>
<![endif]-->