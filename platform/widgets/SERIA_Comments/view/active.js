

var SERIA_Comments = {
		'indexFile': SERIA_VARS.HTTP_ROOT + '/seria/platform/widgets/SERIA_Comments/view/index.php',
		'editFile': SERIA_VARS.HTTP_ROOT + '/seria/platform/widgets/SERIA_Comments/view/edit.php',
		'edit': function (widget, id)
		{
			SERIA.Popup.show(SERIA_Comments.editFile+'?widget='+widget+'&edit='+id, 600, 540, function (value) {
				window.location.reload(false);
			}, "status=1,resizable=1,scrollbars");
			return false;
		},

		'deleteComment': function (widget, id)
		{
			$.post(
				SERIA_Comments.editFile,
				{
					'widget': widget,
					'id': id
				},
				function (data) {
					if (data.error) {
						alert(data.error);
						return;
					}
					window.location.reload(false);
				},
				'json'
			);
			return false;
		},

		'add': function(widget)
		{
			SERIA.Popup.show(SERIA_Comments.indexFile+'?widgetAddComment='+widget, 600, 540, function (value) {
				window.location.reload(false);
			}, "status=1,resizable=1,scrollbars");
			return false;
		},

		'addInline': function(widget)
		{
			document.getElementById('SERIA_Comments_addcom').style.display = 'block';
		}
}
