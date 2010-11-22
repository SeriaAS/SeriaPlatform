<?php
	SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_FileAttachments/css/style.css');
?>
<div id='attachments'>
	<h2><?php echo htmlspecialchars(_t('List of attachments')); ?></h2>
	<ul id='attachment_list'>
	</ul>
	<script type='text/javascript'>
		<!--
			var refreshAttachments;
			var deleteAttachment;
			var addAttachment = (function ()
			{
				/*
				 * My private global space.. :)
				 */

				var my_refreshAttachments = function ()
				{
					$.getJSON(
						'<?php echo SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_FileAttachments/ajax/attachment_list.php'; ?>',
						{
							'widget_id': '<?php echo urlencode($this->getId()); ?>'
						},
						function (data) {
							if (data.error) {
								alert(data.error);
								return;
							}
							$('#attachment_list').html(data.list);
						}
					);
				}
				var my_addAttachment = function()
				{
					openFileSelectWindowScripted('no_element_id', function (result) {
						if (result.element) {
							$.post(
								'<?php echo SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_FileAttachments/ajax/attachment_add.php'; ?>',
								{
									'widget_id': '<?php echo urlencode($this->getId()); ?>',
									'file_id': result.id
								},
								function (data) {
									if (data.error)
										alert(data.error);
									my_refreshAttachments();
								},
								'json'
							);
						}
					});
				}
				var my_deleteAttachment = function (id)
				{
					$.post(
						'<?php echo SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_FileAttachments/ajax/attachment_delete.php'; ?>',
						{
							'widget_id': '<?php echo urlencode($this->getId()); ?>',
							'attachment_id': id
						},
						function (data) {
							if (data.error)
								alert(data.error);
							my_refreshAttachments();
						},
						'json'
					);
				}
				/* init */
				return function ()
				{
					addAttachment = my_addAttachment;
					refreshAttachments = my_refreshAttachments;
					deleteAttachment = my_deleteAttachment;
				}
			})();
			addAttachment(); /* Just the init function */

			refreshAttachments();
		-->
	</script>
	<button type='button' onclick='addAttachment();'><?php echo htmlspecialchars(_t('Add attachment')); ?></button>
</div>