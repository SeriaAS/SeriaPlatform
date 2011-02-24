<?php
	SERIA_Template::cssInclude(SERIA_Filesystem::getCachedUrlFromPath(dirname(__FILE__)).'/style.css');
	$forceVersion = '1.6rc???';
	SERIA_ScriptLoader::loadScript('jQuery-ui-draggable', $forceVersion, $forceVersion, $forceVersion); /* Force this version */
	SERIA_ScriptLoader::loadScript('jQuery-ui-droppable', $forceVersion, $forceVersion, $forceVersion); /* Force this version */
	if (!isset($filelist_id)) {
		$filelist_id = 'filelist_all_icons';
		$default_display = true;
	} else
		$default_display = false;
	$javascriptObjIdent = 'js_'.$filelist_id;

	ob_start();
	$maxlen = 15;
	$maxextlen = 6;
	foreach ($files as $fileid => $file) {
		$name = $file['name'];
		if (strlen($name) > $maxlen) {
			$pi = pathinfo($name);
			$el = strlen($pi['extension']);
			if ($el < $maxextlen)
				$name = substr($pi['basename'], 0, $maxlen-2-$el).'..'.$pi['extension'];
			else
				$name = substr($name, 0, $maxlen-3).'...';
		}
		$mnu = array(
			array(
				_t('Delete'),
				$javascriptObjIdent.'.deleteFile('.$fileid.');'
			)
		);
		foreach ($mnu as &$val)
			$val = $val[0].':'.$val[1];
		unset($val);
		?>
		<div id="<?php echo 'file_icon_ided_'.$fileid; ?>" class='file_icon' mnu="<?php echo htmlspecialchars(implode('|', $mnu)); ?>" title="<?php echo htmlspecialchars($file['name']); ?>">
			<!--[if lt IE 8]>
				<table id="<?php echo htmlspecialchars(($ie7table = 'ie7table'.mt_rand())); ?>" border='0' cellspacing='0' cellpadding='0'><tr><td class='the_icon'>
			<![endif]-->
			<div>
				<div class='the_icon'>
					<a href="#" onclick="<?php echo htmlspecialchars('return '.$javascriptObjIdent.'.clickFile('.$fileid.');'); ?>" ondblclick="<?php echo htmlspecialchars('return '.$javascriptObjIdent.'.dblclickFile('.$fileid.');'); ?>"><img src="<?php echo htmlspecialchars($file['icon']); ?>" alt="[icon]" title="<?php echo htmlspecialchars($file['name']); ?>" <?php if (SERIA_XHTML) echo ' /'; ?>></a>
				</div>
			</div>
			<!--[if lt IE 8]>
				</td></tr><tr><td class='the_name'>
			<![endif]-->
			<div>
				<div class='the_name'>
					<a href="#" onclick="<?php echo htmlspecialchars('return '.$javascriptObjIdent.'.clickFile('.$fileid.');'); ?>" ondblclick="<?php echo htmlspecialchars('return '.$javascriptObjIdent.'.dblclickFile('.$fileid.');'); ?>"><?php echo htmlspecialchars($name); ?></a>
				</div>
			</div>
			<!--[if lt IE 8]>
				</td></tr></table>
			<![endif]-->
		</div>
		<?php
	}
	$icons_html = ob_get_clean();

	if (!isset($json) || !$json) {
		?>
			<script type='text/javascript'>
				<!--
					var <?php echo $javascriptObjIdent; ?> = {
						'deleteFile': false,
						'clickFile': false,
						'prevPage': false,
						'nextPage': false,
						'getCategory': false,
						'setCategory': false,
						'reload': false,
						'reloadWeak': false,
						'uploadedFile': false,
						'uploadFailure': false,
						'uploadStarts': false,
						'setNumOnEachPage': false
					};
					top.uploadedAFile = function (fileid) {
						<?php echo $javascriptObjIdent; ?>.uploadedFile(fileid);
					}
					top.uploadFailure = function (error) {
						<?php echo $javascriptObjIdent; ?>.uploadFailure(error);
					}
					top.uploadStarts = function () {
						<?php echo $javascriptObjIdent; ?>.uploadStarts();
					}
				-->
			</script>
			<div class='filelist_display_root'>
				<div class='statusbar'>
					<?php
						$uploadsys = 'uploadsys'.mt_rand();
						$uploadcounter = 'uploadcount'.mt_rand();
					?>
					<div class='uploadsys' id="<?php echo htmlspecialchars($uploadsys); ?>">
					</div>
					<div class='uploadcounter' id="<?php echo htmlspecialchars($uploadcounter); ?>">
					</div>
					<script type='text/javascript'>
						<!--
							<?php echo $javascriptObjIdent; ?>.createNewIframe = function()
							{
								var myIframe = document.createElement('iframe');
								var parent = document.getElementById(<?php echo SERIA_Lib::toJSON($uploadsys); ?>);

								myIframe.setAttribute('class', 'noborder');
								myIframe.setAttribute('frameBorder', '0');
								myIframe.setAttribute('width', '395px');
								myIframe.setAttribute('height', '30px');
								myIframe.setAttribute('scrolling', 'no');
								myIframe.setAttribute('style', 'overflow: hidden;');
								myIframe.setAttribute('src', <?php echo SERIA_Lib::toJSON(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/../../subpages/uploader.php')); ?>);
								parent.appendChild(myIframe);
								<?php echo $javascriptObjIdent; ?>.currentIframe = myIframe;
							}
							<?php echo $javascriptObjIdent; ?>.createNewIframe();
							<?php echo $javascriptObjIdent; ?>.hideIframe = function ()
							{
								<?php echo $javascriptObjIdent; ?>.currentIframe.style.display = 'none';
							}
						-->
					</script>
				</div>
				<div class='filelist_icons filelist_container' id="<?php echo htmlspecialchars($filelist_id); ?>">
		<?php
	}
?>
	<?php echo $icons_html; ?>
<?php
	if ($pagingInfo['prevAvailable'] || $pagingInfo['nextAvailable']) {
		?>
		<div>
			<div class='bottomPager'>
				<button type='button' onclick='<?php echo $javascriptObjIdent; ?>.prevPage(); return false;'<?php if (!$pagingInfo['prevAvailable']) { echo ' disabled="disabled"'; } ?>><?php echo htmlspecialchars(_t('<< Previous')); ?></button>
				<button type='button' onclick='<?php echo $javascriptObjIdent; ?>.nextPage(); return false;'<?php if (!$pagingInfo['nextAvailable']) { echo ' disabled="disabled"'; } ?>><?php echo htmlspecialchars(_t('Next >>')); ?></button>
			</div>
		</div>
		<?php
	}
	if (!isset($json) || !$json) {
		?>
				</div>
				<script type='text/javascript'>
					<!--
						$(document).ready(function () {
							var resizeContainer = function () {
								var container = document.getElementById(<?php echo SERIA_Lib::toJSON($filelist_id); ?>);
								var winHeight;
								var heightReduce = 133;
								var actualHeight;

								if (window.innerHeight)
									winHeight = window.innerHeight;
								else
									winHeight = document.documentElement.clientHeight;
								actualHeight = winHeight - heightReduce;
								container.style.height = actualHeight + 'px';
							}
							$(window).resize(function () {
								resizeContainer();
							});
							resizeContainer();
						});
					-->
				</script>
			</div>
			<script type='text/javascript'>
				<!--
					/*
					 * Global functions
					 */
					var getFilenameOfDisplayedFile;

					(function () {
						var thisSelectFile = function(fileArticleId)
						{
							var fileIconObj = document.getElementById('file_icon_ided_'+fileArticleId);
							if (fileIconObj) {
								if (!(new RegExp('\\bfile_icon_selected\\b').test(fileIconObj.className)))
									fileIconObj.className += (fileIconObj.className ? ' file_icon_selected' : 'file_icon_selected');
							}
						}
						var thisUnselectFile = function(fileArticleId)
						{
							var fileIconObj = document.getElementById('file_icon_ided_'+fileArticleId);
							if (fileIconObj) {
								if (new RegExp('\\bfile_icon_selected\\b').test(fileIconObj.className)) {
									if (fileIconObj.className.match(' file_icon_selected'))
										fileIconObj.className = fileIconObj.className.replace(' file_icon_selected', '');
									else if (fileIconObj.className.match('file_icon_selected '))
										fileIconObj.className = fileIconObj.className.replace('file_icon_selected ', '');
									else
										fileIconObj.className = fileIconObj.className.replace('file_icon_selected', '');
								}
							}
						}

						var parentSelectFile = selectFile;
						selectFile = function (fileArticleId)
						{
							thisSelectFile(fileArticleId);
							parentSelectFile(fileArticleId);
						}
						var parentUnselectFile = unselectFile;
						unselectFile = function (fileArticleId)
						{
							thisUnselectFile(fileArticleId);
							parentUnselectFile(fileArticleId);
						}

						getFilenameOfDisplayedFile = function(fileArticleId)
						{
							var obj = document.getElementById('file_icon_ided_'+fileArticleId);

							if (!obj) {
								var data = $.ajax({
									url: <?php echo SERIA_Lib::toJSON(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/../../json/filename.php')); ?>,
									data: {
										'fileArticleId': fileArticleId
									},
									async: false,
									dataType: 'text'
								}).responseText;
								data = eval("("+data+")");
								if (data && data.filename)
									return data.filename;
								else {
									return false;
								}
							}
							/* TODO - This probably does not work in IE7 */
							var match = $(obj).find('div.the_name > a');
							match = match[0];
							return match.innerHTML;
						}

						<?php echo $javascriptObjIdent; ?>.deleteFile = function (fileArticleId)
						{
							$.post(
								<?php echo SERIA_Lib::toJSON(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/../../json/deletefile.php')); ?>,
								{
									'fileArticleId': fileArticleId
								},
								function (data) {
									if (data.error) {
										alert(data.error);
										return;
									}
									if (confirm(data.confirm)) {
										$.post(
											<?php echo SERIA_Lib::toJSON(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/../../json/deletefile.php')); ?>,
											{
												'fileArticleId': fileArticleId,
												'confirmed': 1
											},
											function (data) {
												if (data.error) {
													alert(data.error);
													return;
												}
												<?php echo $javascriptObjIdent; ?>.reload();
											},
											'json'
										);
									}
								},
								'json'
							);
						}
						<?php echo $javascriptObjIdent; ?>.clickFile = function (fileArticleId)
						{
							if (!fileIsSelected(fileArticleId))
								selectFile(fileArticleId);
							else
								unselectFile(fileArticleId);
						}
						<?php echo $javascriptObjIdent; ?>.dblclickFile = function (fileArticleId)
						{
							/* Unselect all files */
							var selected = getSelectedFiles();
							for (var i = 0; i < selected.length; i++)
								unselectFile(selected[i]);
							selectFile(fileArticleId);
							SERIA.Popup.returnValue([fileArticleId]);
							return false;
						}

						var categoryId = -1;
						var page = <?php echo intval($pagingInfo['page']); ?>;
						var numEachPage = 1000; /* default */

						var afterReload = function ()
						{
							var selected = getSelectedFiles();

							for (var i = 0; i < selected.length; i++)
								thisSelectFile(selected[i]);
							$('div.file_icon').each(function (index) {
								dragobj = this;
								var article_id = this.id.replace('file_icon_ided_', '');
								var offset;

								var theDraggable = $(dragobj).draggable({
									start: function () {
										this.article_id = article_id;
										this.drop_handled = false;
									},
									stop: function () {
										$(this).remove();
										if (!this.drop_handled) {
											<?php echo $javascriptObjIdent; ?>.reload();
										}
									}
								});
								var mdown = false;
								var dragging = false;
								var tobj = dragobj;
								$(dragobj).mousedown(function (event) {
									if (event.which == 1) {
										mdown = true;
									}
								});
								$(dragobj).mousemove(function (event) {
									if (mdown && !dragging) {
										var obj = tobj;
										dragging = true;
										if (!fileIsSelected(article_id))
											selectFile(article_id);
										offset = $(obj).offset();
										if (!obj._clone) {
											var myid = obj.id;
											obj._clone = $(obj).clone();
											obj._clone.article_id = article_id;
											obj.id = 'randomid'+Math.random();
											obj._clone.id = myid;
										}
										$(obj).css('position', 'absolute');
										$(obj).css('top', offset.top+'px');
										$(obj).css('left', offset.left+'px');
										obj._clone.insertAfter(obj);
										obj.parentNode.removeChild(obj);
										document.body.appendChild(obj);
									}
								});
								$(dragobj).mouseup(function (event) {
									if (event.which == 1) {
										dragging = false;
										mdown = false;
									}
								});
							});
						}
						<?php echo $javascriptObjIdent; ?>.prevPage = function ()
						{
							page--;
							<?php echo $javascriptObjIdent; ?>.reload(function (data) {
								if (data.error) {
									page++;
									<?php echo $javascriptObjIdent; ?>.reload();
								}
							});
						}
						<?php echo $javascriptObjIdent; ?>.nextPage = function ()
						{
							page++;
							<?php echo $javascriptObjIdent; ?>.reload(function (data) {
								if (data.error) {
									page--;
									<?php echo $javascriptObjIdent; ?>.reload();
								}
							});
						}
						<?php echo $javascriptObjIdent; ?>.getCategory = function ()
						{
							return categoryId;
						}
						<?php echo $javascriptObjIdent; ?>.setCategory = function (id)
						{
							page = 0;
							categoryId = id;
						}
						var loadedDirectoryId = false;
						var latestReloader = 0;
						<?php echo $javascriptObjIdent; ?>.reload = function (callback)
						{
							var thisReloader = Math.random();

							loadedDirectoryId = categoryId;
							latestReloader = thisReloader;
							document.getElementById(<?php echo SERIA_Lib::toJSON($filelist_id); ?>).innerHTML = 'Loading...';
							$.get(
								<?php echo SERIA_Lib::toJSON(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/../../json/filepage.php')); ?>,
								{
									'page': page,
									'limit': numEachPage,
									'categoryId': categoryId
								},
								function (data) {
									if (callback)
										callback(data);
									if (latestReloader != thisReloader)
										return; /* aborted! */
									if (data.error) {
										alert(data.error);
										return;
									}
									<?php
										if (SERIA_DEBUG) {
											?>
												filepageDebugOutput = data.debug;
											<?php
										}
									?>
									document.getElementById(<?php echo SERIA_Lib::toJSON($filelist_id); ?>).innerHTML = data.html;
									afterReload();
								},
								'json'
							);
						}
						<?php echo $javascriptObjIdent; ?>.reloadWeak = function (callback)
						{
							if (loadedDirectoryId != categoryId)
								<?php echo $javascriptObjIdent; ?>.reload(callback);
							else
								callback(false);
						}
						var uploadCount = 0;
						var updatedUploadCount = function ()
						{
							var obj = document.getElementById(<?php echo SERIA_Lib::toJSON($uploadcounter); ?>);

							if (uploadCount > 0)
								obj.innerHTML = uploadCount+' uploads in progress!';
							else
								obj.innerHTML = '';
						}
						<?php echo $javascriptObjIdent; ?>.uploadedFile = function (filearticleid)
						{
							uploadCount--;
							updatedUploadCount();
							if (categoryId != -1) {
								$.post(
									<?php echo SERIA_Lib::toJSON(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/../../json/movefile.php')); ?>,
									{
										file_article_id: filearticleid,
										directory_id: categoryId
									},
									function (data) {
										if (data.error)
											alert(<?php echo SERIA_Lib::toJSON(_t('Moving uploaded file to current directory failed: ')); ?> + data.error);
										<?php echo $javascriptObjIdent; ?>.reload();
									},
									'json'
								);
							} else {
								<?php echo $javascriptObjIdent; ?>.reload();
							}
						}
						<?php echo $javascriptObjIdent; ?>.uploadFailure = function (error)
						{
							uploadCount--;
							updatedUploadCount();
							alert(error);
						}
						<?php echo $javascriptObjIdent; ?>.uploadStarts = function ()
						{
							uploadCount++;
							updatedUploadCount();
							<?php echo $javascriptObjIdent; ?>.hideIframe();
							<?php echo $javascriptObjIdent; ?>.createNewIframe();
						}
						<?php echo $javascriptObjIdent; ?>.setNumOnEachPage = function (num)
						{
							alert(num);
							numEachPage = num;
							<?php echo $javascriptObjIdent; ?>.reload();
						}
						<?php echo $javascriptObjIdent; ?>.reload();
					})();
				-->
			</script>
		<?php
	}
?>
