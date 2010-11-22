<?php
	require_once(dirname(__FILE__).'/../../main.php');
	SERIA_Template::disable();
?>
SERIA.Filearchive2 = {
	open: function(callback, multiselect, preSelected)
	{
		var url = <?php echo SERIA_Lib::toJSON(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/../index.php').'?'); ?>;

		if (multiselect)
			url += 'multiselect=yes';
		if (multiselect && preSelected)
			url += '&';
		if (preSelected)
			url += 'preselected='+preSelected.join(',');
		SERIA.Popup.show(url, 950, 600, callback, false);
	},
	openSingleSelect: function(callback, preSelected)
	{
		SERIA.Filearchive2.open(function (value) {
			if (value)
				callback(value[0]);
			else
				callback(false);
		}, false, [preSelected]);
	},
	openMultiSelect: function(callback, preSelected)
	{
		SERIA.Filearchive2.open(callback, true, preSelected);
	},

	showSingleFileSelect: function(parentElement, fileId, changeCallback)
	{
		var lastRequestedUpdate = 0;

		var invokeButton = document.createElement('button');
		invokeButton.setAttribute('class', 'Filearchive2SingleInvoke');
		invokeButton.setAttribute('type', 'button');
		invokeButton.innerHTML = <?php echo SERIA_Lib::toJSON(_t('Select a file')); ?>;
		invokeButton.onclick = function () {
			SERIA.Filearchive2.openSingleSelect(function (value) {
				if (value) {
					lastRequestedUpdate++;
					var updateRequest = lastRequestedUpdate;
					fileId = value;

					changeCallback(value);
					$.get(
						<?php echo SERIA_Lib::toJSON(dirname(__FILE__).'/../specialTemplates/thumbnailer.php'); ?>,
						{
							'multi': 'no',
							'fileId': value
						},
						function (data) {
							if (updateRequest == lastRequestedUpdate)
								thumbnailArea.innerHTML = data;
						}
					);
				}
			}, fileId);
		}
		parentElement.appendChild(invokeButton);
		var clearButton = document.createElement('button');
		clearButton.setAttribute('class', 'Filearchive2SingleClear');
		clearButton.setAttribute('type', 'button');
		clearButton.innerHTML = <?php echo SERIA_Lib::toJSON(_t('Clear')); ?>;
		parentElement.appendChild(clearButton);
		var thumbnailArea = document.createElement('div');
		thumbnailArea.setAttribute('class', 'Filearchive2SingleThumbnailArea');
		parentElement.appendChild(thumbnailArea);
		if (fileId) {
			(function () {
				lastRequestedUpdate++;
				var updateRequest = lastRequestedUpdate;

				$.get(
					<?php echo SERIA_Lib::toJSON(dirname(__FILE__).'/../specialTemplates/thumbnailer.php'); ?>,
					{
						'multi': 'no',
						'fileId': fileId
					},
					function (data) {
						if (updateRequest == lastRequestedUpdate)
							thumbnailArea.innerHTML = data;
					}
				);
			})();
		}
	},
	hookSingleSelectOnInput: function (inputObj)
	{
		var parent = inputObj.parentNode;

		var divobj = document.createElement('div');
		divobj.setAttribute('class', 'Filearchive2SingleHostObject');
		parent.insertBefore(divobj, inputObj);
		SERIA.Filearchive2.showSingleFileSelect(divobj, inputObj.value, function (newValue) {
			inputObj.value = newValue;
		});
	}
};
