<?php
	require_once(dirname(__FILE__).'/../../../../main.php');

	SERIA_Template::disable();
?>
var SERIA_RecommendByEMail = {
	linkClick : function (objectId, widgetid, url)	{
		var divobj = document.getElementById(objectId);

		$(divobj).html('<form onsubmit=\'SERIA_RecommendByEMail.linkOK("'+objectId+'", "'+widgetid+'", "'+url+'"); return false;\'><div><label for=\''+objectId+'_name\'><?php echo _t('Your name:');?></label><input type=\'text\' id=\''+objectId+'_name\' name=\'name\' value=\'\' /><label for=\''+objectId+'_email\'><?php echo _t('Send to email:'); ?></label><input type=\'text\' id=\''+objectId+'_email\' name=\'email\' value=\'\' /><div class=\'buttons\'><button onclick=\'SERIA_RecommendByEMail.linkCancel("'+objectId+'"); return false;\' type=\'button\'><?php echo _t('Cancel'); ?></button><button type=\'submit\'><?php echo _t('Send'); ?></button></div></div></form>')
		$(divobj).css('display', 'block');
	},
	linkOK : function (objectId, widgetid, url) {
		var divobj = document.getElementById(objectId);
		var nameObj = document.getElementById(objectId + '_name');
		var emailObj = document.getElementById(objectId + '_email');

		$.post(
			SERIA_VARS.HTTP_ROOT+'/seria/platform/widgets/SERIA_RecommendByEMail/view/link.php',
			{
				'widgetid': widgetid,
				'name': nameObj.value,
				'email': emailObj.value,
				'url': url
			},
			function (data) {
				if (data.error)
					alert(data.error);
				else {
					$(divobj).html('');
					$(divobj).css('display', 'none');
				}
			},
			'json'
		);
	},
	linkCancel : function (objectId) {
		var divobj = document.getElementById(objectId);

		$(divobj).html('');
		$(divobj).css('display', 'none');
	}
};
