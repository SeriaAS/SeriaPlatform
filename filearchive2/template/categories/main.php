<script type='text/javascript'>
	<!--
		function mouseoverDirectoryItem(item)
		{
			if (!$(item).hasClass('seria_filedirectory_hover_recur') && !$(item).hasClass('seria_filedirectory_hover')) {
				$(item).addClass('seria_filedirectory_hover');
				var recur = false;
				$(item).children('ul').children('li').each(function (index) {
					if ($(this).hasClass('seria_filedirectory_hover_recur') || $(this).hasClass('seria_filedirectory_hover'))
						recur = true;
				});
				if (recur) {
					$(item).removeClass('seria_filedirectory_hover');
					$(item).addClass('seria_filedirectory_hover_recur');
				}
				while ((item = item.parentNode)) {
					if (item.nodeName.toLowerCase() == 'li')
						break;
				}
				if (item) {
					if ($(item).hasClass('seria_filedirectory_hover')) {
						$(item).removeClass('seria_filedirectory_hover');
						$(item).addClass('seria_filedirectory_hover_recur');
					}
				}
			}
		}
		function mouseoutDirectoryItem(item)
		{
			if ($(item).hasClass('seria_filedirectory_hover')) {
				$(item).removeClass('seria_filedirectory_hover');
				while ((item = item.parentNode)) {
					if (item.nodeName.toLowerCase() == 'li')
						break;
				}
				if (item) {
					if ($(item).hasClass('seria_filedirectory_hover_recur')) {
						$(item).removeClass('seria_filedirectory_hover_recur');
						$(item).addClass('seria_filedirectory_hover');
					}
				}
			}
			if ($(item).hasClass('seria_filedirectory_hover_recur'))
				$(item).removeClass('seria_filedirectory_hover_recur');
		}
	-->
</script>
<?php
	SERIA_Template::cssInclude(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/style3.css'));
	SERIA_ScriptLoader::loadScript('jQuery-treeview');

	if (!isset($filelist_id)) {
		$filelist_id = 'filelist_all_icons';
		$default_display = true;
	} else
		$default_display = false;
	$javascriptObjIdent = 'js_'.$filelist_id;

	$traceIndex = array();
	$stack = array();
	$children = $categories['categories'];
	while ($children)
		array_push($stack, array_pop($children));
	$mnu = array(
		array(
			_t('Create new directory'),
			'SERIA.DirectoryTree.createDirectoryGUI(false);'
		)
	);
	foreach ($mnu as &$val)
		$val = $val[0].':'.$val[1];
	unset($val);
?>
<ul id='cats_disp' mnu="<?php echo htmlspecialchars(implode('|', $mnu)); ?>">
	<li class='seria_filedirectory seria_filedirectory_open open' id='seria_filedirectory_root' onmouseover="mouseoverDirectoryItem(this);" onmouseout="mouseoutDirectoryItem(this);">
		<div class='folder'>
			<span class='name'><?php echo htmlspecialchars('/'); ?></span>
		</div>
		<ul id='seria_filedirectory_rootlist'>
			<?php
				do {
					$item =& array_pop($stack);
					if ($item === false) { /* got a boundary mark */
						echo '</ul></li>';
						if (!$stack)
							break;
						continue;
					}
					$mnu = array(
						array(
							_t('Create new directory'),
							'SERIA.DirectoryTree.createDirectoryGUI('.$item['id'].');'
						)
					);
					foreach ($mnu as &$val)
						$val = $val[0].':'.$val[1];
					?>
					<li class='seria_filedirectory' id='seria_filedirectory_<?php echo $item['id']; ?>' mnu="<?php echo htmlspecialchars('!'.implode('|', $mnu)); ?>" onmouseover="mouseoverDirectoryItem(this);" onmouseout="mouseoutDirectoryItem(this);">
						<div class='folder'><span class='name'><?php echo htmlspecialchars($item['name']); ?></span></div>
					<?php
					$terminate = true;
					if (isset($item['categories'])) {
						$children = $item['categories'];
						if ($children) {
							echo '<ul>';
							$terminate = false;
							array_push($stack, false); /* boundary */
						}
						while ($children) {
							array_push($stack, array_pop($children));
						}
					}
					if ($terminate) {
						?>
						</li>
						<?php
					}
				} while ($stack);
				?>
			</ul>
		</li>
	</ul>

	<script type='text/javascript'>
		<!--
			$(document).ready(function () {
				var resizeContainer = function () {
					var container = document.getElementById('cats_disp');
					var winHeight;
					var heightReduce = 103;
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

<script type='text/javascript'>
	<!--
		(function () {
			var prepare;
			var currentOpenDirectory = document.getElementById('seria_filedirectory_root');
			SERIA.DirectoryTree = {
				'prepareDirectoryObject': function (obj) {
					var dirid;

					if (obj.id != 'seria_filedirectory_root')
						dirid = Number(obj.id.replace('seria_filedirectory_', ''));
					else
						dirid = -1;

					obj.dirid = dirid;
					$(obj).children('div.folder').each(function (index) {
						this.onclick = function (e) {
							if(!e)
								e = window.event;
							e.cancelBubble = true;
							e.returnValue = false;
							if(e.stopPropagation)
							{
								e.stopPropagation();
								e.preventDefault();
							}
							SERIA.DirectoryTree.openDirectory(dirid);
							return false;
						}
						$(this).droppable({
							accept: '.file_icon',
							hoverClass: 'seria_filedirectory_drophover',
							tolerance: 'pointer',
							drop: function (event, ui) {
								ui.draggable.get(0).drop_handled = true;
								var files = getSelectedFiles();
								var i;

								i = 0;
								var moveFileFunc = function (article_id) {
									$.post(
										<?php echo SERIA_Lib::toJSON(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/../../json/movefile.php')); ?>,
										{
											file_article_id: article_id,
											directory_id: (dirid != -1 ? dirid : 'root')
										},
										function (data) {
											if (data.error) {
												alert(data.error);
												<?php echo $javascriptObjIdent; ?>.reload();
												return;
											}
											if (!data.loop) {
												var object = document.getElementById('file_icon_ided_'+article_id);
												if (object)
													$(object).remove();
											} else {
												<?php echo $javascriptObjIdent; ?>.reload();
											}
											if (fileIsSelected(article_id))
												unselectFile(article_id);
											i++;
											if (i < files.length)
												moveFileFunc(files[i]);
										},
										'json'
									);
								}
								if (i < files.length)
									moveFileFunc(files[i]);
								return false;
							}
						});
					});
				},
				'openDirectory': function (dirid) {
					var id = 'seria_filedirectory_'+dirid;
					if (dirid == -1)
						id = 'seria_filedirectory_root';
					var obj = document.getElementById(id);

					/*
					 * Call the file display
					 */
					 <?php echo $javascriptObjIdent; ?>.setCategory(dirid);
					 <?php echo $javascriptObjIdent; ?>.reloadWeak();

					if (currentOpenDirectory)
						$(currentOpenDirectory).removeClass('seria_filedirectory_open');
					$(obj).addClass('seria_filedirectory_open');
					currentOpenDirectory = obj;
				},
				'createDirectoryGUI': function (parent_id) {
					var dirobj = document.getElementById('create_directory_element');
					var modalCover;

					if (dirobj) {
						if (!confirm(<?php echo SERIA_Lib::toJSON(_t('Are you sure you want to abort the previous create directory operation?')); ?>))
							return;
						var parent_node = dirobj.parentNode;
						parent_node.removeChild(dirobj);
						dirobj.setAttribute('id', 'going_away'+Math.random());
						dirobj = false;
						$(modalCover).remove();
						formobj.parentNode.removeChild(formobj);
					}
					var parobj;
					if (parent_id) {
						var li_parobj = document.getElementById('seria_filedirectory_' + parent_id);

						var i = 0;
						while (i < li_parobj.childNodes.length) {
							if (li_parobj.childNodes[i].nodeName.toLowerCase() == 'ul')
								break;
							i++;
						}
						if (i < li_parobj.childNodes.length)
							parobj = li_parobj.childNodes[i];
						else {
							parobj = document.createElement('ul');
							li_parobj.appendChild(parobj);
						}
					} else
						var parobj = document.getElementById('seria_filedirectory_rootlist');
					dirobj = document.createElement('li');
					dirobj.setAttribute('id', 'create_directory_element');
					dirobj.setAttribute('class', 'seria_filedirectory last');
					dirobj.setAttribute('className', 'seria_filedirectory last');
					while (parobj.lastChild && parobj.lastChild.nodeName.toLowerCase() != 'li') {
						parobj.removeChild(parobj.lastChild);
					}
					if (parobj.lastChild) {
						parobj.lastChild.setAttribute('class', 'seria_filedirectory');
						parobj.lastChild.setAttribute('className', 'seria_filedirectory');
					}


					/*
					 * Cover the directory tree with a div that events can't penetrate
					 */
					modalCover = document.createElement('div');
					modalCover.setAttribute('class', 'cats_disp_disable');
					modalCover.setAttribute('className', 'cats_disp_disable');
					modalCover.style.position = 'absolute';
					modalCover.updateCoverage = function () {
						var disp = $('ul#cats_disp');
						var offset = disp.offset();

						modalCover.style.top = offset.top + 'px';
						modalCover.style.left = offset.left + 'px';
						modalCover.style.width = disp.outerWidth() + 'px';
						modalCover.style.height = disp.outerHeight() + 'px';
					}
					$(window).resize(function () {
						modalCover.updateCoverage();
					});
					modalCover.updateCoverage(); 
					document.body.appendChild(modalCover);

					/*
					 * The form
					 */
					var formobj = document.createElement('form');
					var topcontainer = document.createElement('div');
					topcontainer.setAttribute('class', 'dirname_edit_new_container1');
					topcontainer.setAttribute('className', 'dirname_edit_new_container1');
					var container = document.createElement('div');
					container.setAttribute('class', 'dirname_edit_new_container2');
					container.setAttribute('className', 'dirname_edit_new_container2');
					var dirnameobj = document.createElement('input');
					dirnameobj.setAttribute('type', 'text');
					dirnameobj.setAttribute('name', 'dirname');
					dirnameobj.setAttribute('class', 'dirname_edit_new');
					dirnameobj.setAttribute('className', 'dirname_edit_new');
					container.appendChild(dirnameobj);
					var submitbutton = document.createElement('button');
					submitbutton.setAttribute('type', 'submit');
					var submittext = document.createTextNode(<?php echo SERIA_Lib::toJSON(_t('Create')); ?>);
					submitbutton.appendChild(submittext);
					container.appendChild(submitbutton);
					var cancelbutton = document.createElement('button');
					cancelbutton.setAttribute('type', 'button');
					cancelbutton.onclick = function () {
						var parent_node = dirobj.parentNode;
						parent_node.removeChild(dirobj);
						dirobj.setAttribute('id', 'going_away'+Math.random());
						dirobj = false;
						$(modalCover).remove();
						formobj.parentNode.removeChild(formobj);
					}
					cancelbutton.appendChild(document.createTextNode(<?php echo SERIA_Lib::toJSON(_t('Cancel')); ?>));
					container.appendChild(cancelbutton);
					formobj.appendChild(container);
					var onsubmit = function () {
						$(modalCover).remove(); /* unlock the scrollbars and whatever */
						/*
						 * Just get rid of the element with the form.
						 * We add the new directory asynchronously with AJAX.
						 */
						dirobj.setAttribute('id', 'going_away'+Math.random());
						var parent_node = dirobj.parentNode;
						parent_node.removeChild(dirobj);
						if (parent_node.lastChild)
							parent_node.lastChild.setAttribute('class', 'seria_filedirectory last');
						formobj.parentNode.removeChild(formobj);

						var fields;
						if (parent_id) {
							fields = {
									parent_directory_id: parent_id,
									dirname: dirnameobj.value
							};
						} else {
							fields = {
								dirname: dirnameobj.value
							};
						}
						$.post(
							<?php echo SERIA_Lib::toJSON(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/../../json/createdir.php')); ?>,
							fields,
							function (data) {
								if (data.error) {
									alert(data.error);
									return;
								}
								dirobj = document.createElement('li');
								dirobj.setAttribute('id', 'seria_filedirectory_' + data.id);
								dirobj.setAttribute('class', 'seria_filedirectory last');
								dirobj.setAttribute('className', 'seria_filedirectory last');
								menuCreate = <?php echo SERIA_Lib::toJSON(_t('Create new directory')); ?>;
								menuCreate += ':SERIA.DirectoryTree.createDirectoryGUI('+data.id+')';
								dirobj.setAttribute('mnu', '!'+menuCreate);
								dirobj.onmouseover = function () {
									mouseoverDirectoryItem(dirobj);
								}
								dirobj.onmouseout = function () {
									mouseoutDirectoryItem(dirobj);
								}
								if (parent_node.lastChild)
									parent_node.lastChild.setAttribute('class', 'seria_filedirectory');

								/*
								 * Create visual items
								 */
								var odiv = document.createElement('div');
								odiv.setAttribute('class', 'folder');
								odiv.setAttribute('className', 'folder');
								var ispan = document.createElement('span');
								ispan.setAttribute('class', 'name');
								ispan.setAttribute('className', 'name');
								ispan.appendChild(document.createTextNode(data.name));
								odiv.appendChild(ispan);
								dirobj.appendChild(odiv);

								parent_node.appendChild(dirobj);
								SERIA.DirectoryTree.prepareDirectoryObject(dirobj);
							},
							'json'
						);
						return false;
					}
					formobj.onsubmit = function () {
						onsubmit();
						onsubmit = function () {
						}
						return false;
					}
					submitbutton.onclick = function () {
						onsubmit();
						onsubmit = function () {
						}
					}
					dirobj.appendChild(topcontainer);

					parobj.appendChild(dirobj);

					/*
					 * Scroll to the new object before adding the visual objects
					 */
					var position = $(topcontainer).position();
					var top = position.top;
					var height = $(topcontainer).outerHeight();
					var disp_item = document.getElementById('cats_disp');
					var avail = $(disp_item).innerHeight();
					if (top < disp_item.scrollTop)
						disp_item.scrollTop = top;
					else if ((top + height) > (disp_item.scrollTop + avail))
						disp_item.scrollTop = top + height - avail;
					var offset = $(topcontainer).offset();
					container.style.top = offset.top + 'px';
					container.style.left = offset.left + 'px';
					document.body.appendChild(formobj);
				}
			}

			prepare = function () {
				$('.seria_filedirectory').each(function (index) {
					SERIA.DirectoryTree.prepareDirectoryObject(this);
				});
			}
			$(document).ready(function () {
				$('#cats_disp').treeview({
					collapsed:true
				});
				prepare();
			});
		})();
	-->
</script>
