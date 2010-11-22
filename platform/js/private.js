/**
*	This file must be included AFTER jQuery.js, since it relies on functions from jQuery
*/
var SERIA = {
	mode : "private"
	,
	editArticle : function(articleId)
	{
		top.location.href=SERIA_VARS.HTTP_ROOT + "/seria/apps/publisher/articles.php?id=" + escape(articleId) + "&continue=" + escape(document.location.href);
	}
	,
	editArticleCategory : function(categoryId)
	{
		top.location.href=SERIA_VARS.HTTP_ROOT + "/seria/apps/publisher/categories.php?id=" + escape(categoryId) + "&continue=" + escape(document.location.href);
	}
	,
	editUser : function(userId)
	{
		top.location.href=SERIA_VARS.HTTP_ROOT + "/seria/apps/controlpanel/users/users.php?user_id=" + escape(userId) + "&continue=" + escape(document.location.href);
	}

}
