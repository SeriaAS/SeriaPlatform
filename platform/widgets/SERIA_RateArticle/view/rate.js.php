var SERIA_RateArticle = {
	linkClick : function (objectId, widgetid)	{
		var divobj = document.getElementById(objectId);

		$(divobj).html('<form method=\'POST\' action=\'nonex.php\' onsubmit=\'SERIA_RateArticle.linkOK("'+objectId+'", '+widgetid+'); if (!e) var e = window.event; e.cancelBubble = true; if (e.stopPropagation) { e.stopPropagation(); } return false;\'><div><p>Rating (1-6): <input id=\''+objectId+'_rating\' type=\'text\' name=\'rating\' value=\'\'><button type=\'submit\'>OK</button></p></div></form>')
		$(divobj).css('display', 'block');
	},
	linkOK : function (objectId, widgetid) {
		var divobj = document.getElementById(objectId);
		var inputObj = document.getElementById(objectId + '_rating');

		$.post(
			SERIA_VARS.HTTP_ROOT+'/seria/platform/widgets/SERIA_RateArticle/view/link.php',
			{
				'widgetid': widgetid,
				'rating': inputObj.value
			},
			function (data) {
				$(divobj).html('');
				$(divobj).css('display', 'none');
			},
			'json'
		);
	}
};