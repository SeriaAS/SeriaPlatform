<?php
	$seria_options = array(
		"skip_session" => true,
		'skip_authentication' => true,
		"cache_limiter" => "public",
		"cache_expire" => 180
	);
	require('../../main.php');
	header('Content-Type: text/javascript; charset=UTF-8');
	SERIA_Template::disable();
?>

function filearchiveDeleteFile(element, elementName) {
	if (confirm('<?php echo _t('Are you sure you want to delete this file?'); ?>')) {
		idElement = $('#' + elementName);
		idElement.attr('value', '');
		
		hideFileData(elementName);
		// Find _files element containing all hidden elements with file ids
		reconstructFileSelectValue(element.parent().parent().parent());
	}
	return false;
}

function reconstructFileSelectValue(element) {
	var elementValue = '';
	
	element.children().each(function() {
		var child = $(this);
		if (child.hasClass('fileselect_file_id')) {
			if (child.val()) {
				if (elementValue.length) {
					elementValue += ',';
				}
				elementValue += child.val();
			}
		}
	});
	
	element.parent().children().each(function() {
		var child = $(this);
		if (child.hasClass('fileselect')) {
			child.val(elementValue);
		}
	});
}

function hideFileData(id) {
	$('div#' + id + '_filedata').hide();
	$('img#' + id + '_previewimage').parent().hide();
}

function showFileData(id) {
	$('#' + id + '_filedata').show();
	$('img#' + id + '_previewimage').parent().show();
}

function filePreview(elementId) {
	var fileId = $('input#' + elementId).attr('value');
	
	if ((fileId = parseInt(fileId)) > 0) {
		var previewBox = $('#' + elementId + '_preview');
		
		if (previewBox.css('display') != 'block') {
			var url = SERIA_VARS.HTTP_ROOT + '/seria/filearchive/filepreview.php?id=' + fileId;
			previewBox.load(url, false, function(){
				if(($(this).find("img").length)>0)
				{
					$(this).find("img").load(function(){
						previewBox.show(500);
					});
				}
				else
				{
					previewBox.show(500);
				}
			});
		}
		else
		{
			previewBox.hide(500);
		}
	}
}

/*
 * START:
 *  Get url of file and create a holder article.
 *  This is needed to use images in systems which
 *  are not based on the article system.
 *  The following two functions does this.
 */ 
function updateFileElementUrl(result) {
	if (typeof(result) == 'object') {
		var elementName = result.element;
		var fileid = result.id;
		
		var fileElement = $('#' + elementName);

		fileElement.attr('value', 'Wait...');

		data = SERIA.Lib.SJSON(SERIA_VARS.HTTP_ROOT + '/seria/platform/json/filearchive/fileinfo2.php', {'id': fileid});
		fileElement.attr('value', data.url);
	}
}

function openFileSelectWindowUrl(elementId) {
	element = $('#' + elementId);
	if (element.hasClass('fs_images')) {
		/* Filearchive should only display images */
	}
	if (element.hasClass('fs_videos')) {
		/* Filearchive should only display videos */
	}
	
	SERIA.Filearchive2.openSingleSelect(function (value) {
		if (value) {
			var obj = new Object();
			obj.element = elementId;
			obj.id = value;
			updateFileElementUrl(obj);
		}
	});
}
/*
 * END of automatic url generation functions.
 */
 
function downloadFile(fileid) {
	window.location = SERIA_VARS.HTTP_ROOT + '/seria/filearchive/download.php?file_id=' + fileid;
}

function updateFileElement(result) {
	if (typeof(result) == 'object') {
		if (result.element) {
			var fileid = result.id;
			if (result.filename) {
				var elementName = result.element;
				var filename = result.filename;
				var preview = result.preview;
				var imageWidth = result.previewWidth;
				var imageHeight = result.previewHeight;
				
				var nameElementId = elementName + '_filename';
				var operationsElementId = elementName + '_operations';
				var filenameElement = $('span#' + nameElementId);
				var operationsElement = $('span#' + operationsElementId);
				var fileidElement = $('#' + elementName);
				
				fileidElement.attr('value', fileid);
				
				showFileData(elementName);
				
				var imageId = elementName + '_previewimage';
				var image = $('img#' + imageId);
				if (preview != '') {
					var button = $('input#' + elementName + '_button');
					if (image.attr('id') == undefined) {
						imageHtml = '<div class="previewFrame"><img width="' + imageWidth + '" height="' + imageHeight + '" src="' + preview + '" alt="" align="left" class="fileselectPreviewImage" id="' + imageId + '"></div>';
						filenameElement.before(imageHtml);
						var image = $('img#' + imageId);
					} else {
						image.attr('src', preview);
						image.attr('width', imageWidth);
						image.attr('height', imageHeight);
						image.parent().show();
					}
					
					// Center image.
					image.css({'margin-left': ((50 - image.attr('width')) / 2), 'margin-top': (50 - image.attr('height')) / 2});
				} else {
					image.parent().hide();
				}
				
				filenameElement.html('<br /><strong><?php echo _t('Filename: '); ?></strong>' + filename);
				operationsElement.html('<a href="" onclick="return filearchiveDeleteFile($(this), \'' + elementName + '\');"><?php echo _t('Delete'); ?></a> | <a href="" onclick="filePreview(\'' + elementName + '\'); return false"><?php echo _t('Preview'); ?></a> | <a href="" onclick="downloadFile(' + fileid + '); return false"><?php echo _t('Download'); ?></a>');
			} else {
				// Returned from popup. Partial object, must fetch file data, and return to this method with complete object
				
				// Don't use AJSON, as it will not work in Internet Explorer because original caller (popup window) is closed before
				// answer returns from HTTP call.
				data = SERIA.Lib.SJSON(SERIA_VARS.HTTP_ROOT + '/seria/platform/json/filearchive/fileinfo2.php', {'id': fileid});
				var id = data.id;
				data.element = result.element;
				updateFileElement(data);
			}
		} else {
			// Multiselect
			elementId = result[0];
			ids = result[1];
			
			var elementContainer = $('#files_' + elementId);
			elementContainer.html('');
			var elementValue = '';
			for (var key in ids) {
				var id = ids[key];
				if (id) {
					if (elementValue.length)
						elementValue += ',';
					elementValue += id;

					elementContainer.append('<input class="fileselect_file_id" type="hidden" name="' + elementId + '_' + id + '" id ="' + elementId + '_' + id + '" value="+ id + " />');

					SERIA.Lib.AJSON(SERIA_VARS.HTTP_ROOT + '/seria/platform/json/filearchive/fileinfo2.php', {'id': id}, function (data) {
						var id = data.id;
						var elementContent = createFileInfoElement(elementId + '_' + id);
						elementContainer.append(elementContent);
						
						data.element = elementId + '_' + id;
						updateFileElement(data);
					});
				}
			}

			var resp = SERIA.Lib.SJSON(SERIA_VARS.HTTP_ROOT + '/seria/platform/json/filearchive/fileinfo2.php', {'articlesToFiles': elementValue});
			elementValue = resp.ids;
			$('#' + elementId).val(elementValue);
		}
	}
}

function initMultiSelect(id) {
	var values = $('#' + id).val().split(',');
	var data = [id, values];
	updateFileElement(data);
}

function createFileInfoElement(id) {
	var content = '';
	content += '<div class="fileData" id="' + id + '_filedata"><span class="filename" id="' + id + '_filename"></span>';
	content += '<br /><span class="fileOperations" id="' + id + '_operations"></span>';
	content += '<div class="filePreviewBox" id="' + id + '_preview"></div>';
	content += '</div>';
	return content;
}

function openFileSelectWindowScripted(elementId, callback) {
	var multiselect;

	element = $('#' + elementId);
	if (element.hasClass('fs_images')) {
		/* TODO - Only display images */
	}
	if (element.hasClass('fs_videos')) {
		/* TODO - Only display videos */
	}
	
	multiselect = element.hasClass('multiselect');

	if (multiselect) {
		SERIA.Filearchive2.openMultiSelect(function (value) {
			if (value) {
				callback([elementId, value]);
			}
		}, element.val().split(','));
	} else {
		SERIA.Filearchive2.openSingleSelect(function (value) {
			if (value) {
				data = SERIA.Lib.SJSON(SERIA_VARS.HTTP_ROOT + '/seria/platform/json/filearchive/fileinfo2.php', {'id': value});
				var id = data.id;
				data.element = elementId;
				callback(data);
			}
		}, element.val());
	}	
}
function openFileSelectWindow(elementId) {
	callback = function(result) {
		updateFileElement(result);
	};

	openFileSelectWindowScripted(elementId, callback);
}
	
$(function() {
	$('input.fileselect').each(function() {

		if(!$(this).attr('id')) {
			$(this).attr('id',  "autogenerated" + Math.random());
		}

		var id = $(this).attr('id');
		
		$(this).after('<div style="clear: both"></div>');
		$(this).wrap("<div id='" + id + "_fileselect' style=''></div>");

		var content = '<input id="' + id + '_button" type="button" onclick="openFileSelectWindow(\'' + id + '\');" value="<?php echo _t('Browse or upload file'); ?>">';
		var multiselect = false;
		if (!$(this).hasClass('multiselect')) {
			content += createFileInfoElement(id);
			// Get file data from server
			SERIA.Lib.AJSON(SERIA_VARS.HTTP_ROOT + '/seria/platform/json/filearchive/fileinfo.php', {'id': $(this).attr('value')}, function (data) {
				data.element = id;
				updateFileElement(data);
			});
			multiselect = false;
		} else {
			content += '<div id="files_' + id + '"></div>';
			multiselect = true;
		}
		$(this).after(content);
		
		if (multiselect) {
			initMultiSelect(id);
		}
		
	});
	$('input.fileselect_url').each(function() {

		if(!$(this).attr('id')) {
			$(this).attr('id',  "autogenerated" + Math.floor(Math.random()*100000000));
		}

		var id = $(this).attr('id');
		
		$(this).wrap("<div id='" + id + "_fileselect'></div>");
		
		var content = '<input id="' + id + '_button" type="button" onclick="openFileSelectWindowUrl(\'' + id + '\');" value="<?php echo _t('Browse or upload file'); ?>">';
		$(this).after(content);
	});
});
