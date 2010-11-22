SERIA.editSiteMenuItem = function(menuId)
{
	top.location.href=SERIA_VARS.HTTP_ROOT + "/seria/sitemeu/?id=" + escape(menuId) + "&continue=" + escape(document.location.href);
};
SERIA.addSiteMenuItem = function(menuId)
{
	top.location.href=SERIA_VARS.HTTP_ROOT + "/seria/sitemenu/?parent=" + escape(menuId);
};
SERIA.deleteSiteMenuItem = function(menuId)
{
	alert("deleting not yet implemented");
	top.location.href=SERIA_VARS.HTTP_ROOT + "/seria/sitemenu/?id=" + escape(menuId) + "&delete=1";
};