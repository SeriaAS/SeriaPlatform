
/*
 * init script for this widget
 */

function includeJSFile(widgetName, filename) {
	var url = SERIA_VARS.HTTP_ROOT + "/seria/widgets/" + widgetName + "/" + filename;
	document.write("<script type='text/javascript' src='" + url + "'></scrip" + "t>");
}

includeJSFile("tinymce", "tiny_mce.js");
includeJSFile("tinymce", "tinyinit.js");
