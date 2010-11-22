<?php
	SERIA_Template::cssInclude(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/style.css'));
	if (!isset($filelist_id)) {
		$filelist_id = 'filelist_all_icons';
		$default_display = true;
	} else
		$default_display = false;
	$javascriptObjIdent = 'js_'.$filelist_id;

?><div id='filearchive2_container'><span id='filearchive2_path_desc'><?php echo htmlspecialchars(_t('You are here:')); ?></span><span id='filearchive2_path'></span></div>
<script type='text/javascript'>
	<!--
		$(document).ready(function () {
			var dirseparator_path = <?php echo SERIA_Lib::toJSON(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/dirseparator.png')); ?>;
			var dirpath_obj = document.getElementById('filearchive2_path');

			var setCurrentCategory_serial = 0;
			var setCurrentCategory = function (id) {
				var serial = ++setCurrentCategory_serial;
				/*
				 * Get full path
				 */
				var clearPathComponents = function () {
					for (var i = dirpath_obj.childNodes.length; i > 0; i--) {
						dirpath_obj.removeChild(dirpath_obj.childNodes[i-1]);
					}
					var root = document.createElement('span');
					root.setAttribute('class', 'directoryPathComponent');
					root.innerHTML = '/';
					root.onclick = function () {
						SERIA.DirectoryTree.openDirectory(-1);
					}
					dirpath_obj.appendChild(root);
				}
				if (id == -1) {
					/* Root directory */
					clearPathComponents();
					return;
				}
				$.post(
					<?php echo SERIA_Lib::toJSON(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/../../json/pathinfo.php')); ?>,
					{
						'directory_id': id
					},
					function (data) {
						if (setCurrentCategory_serial != serial)
							return; /* Cancelled */
						if (data.error) {
							alert(data.error);
							return;
						}
						clearPathComponents();
						for (var i = 0; i < data.path.length; i++) {
							var separator = document.createElement('img');
							separator.setAttribute('class', 'directoryPathSeparator');
							separator.setAttribute('className', 'directoryPathSeparator');
							separator.setAttribute('src', dirseparator_path);
							separator.setAttribute('alt', '>');
							dirpath_obj.appendChild(separator);
							var dirname = document.createElement('span');
							dirname.setAttribute('class', 'directoryPathComponent');
							dirname.innerHTML = data.path[i].name;
							(function (thedirid) {
								var dirid = thedirid;
								dirname.onclick = function () {
									SERIA.DirectoryTree.openDirectory(dirid);
								}
							})(data.path[i].id);
							dirpath_obj.appendChild(dirname);
						}
					},
					'json'
				);
			}

			/*
			 * Eavesdrop on the setCategory
			 */
			var setCategory = <?php echo $javascriptObjIdent; ?>.setCategory;
			<?php echo $javascriptObjIdent; ?>.setCategory = function (id) {
				setCategory(id); /* super */
				setCurrentCategory(id);
			}
			setCurrentCategory(<?php echo $javascriptObjIdent; ?>.getCategory());
		});
	-->
</script>