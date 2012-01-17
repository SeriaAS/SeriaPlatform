/**
 * 
 */

SERIA.api = {
	'responseFilter': function (req)
	{
		if (req.status != 200)
			return false;
		eval('data = ' + req.responseText + ';');
		return data;
	},
	'post': function (path, data)
	{
		req = jQuery.ajax({
			'async': false,
			'data': data,
			'dataType': 'text',
			'type': 'POST',
			'url': SERIA_VARS.HTTP_ROOT + '/seria/api/'
		});
		return SERIA.api.responseFilter(req);
	},
	'get': function (path, data)
	{
		data.apiPath = path;
		req = jQuery.ajax({
			'async': false,
			'data': data,
			'dataType': 'text',
			'type': 'GET',
			'url': SERIA_VARS.HTTP_ROOT + '/seria/api/'
		});
		return SERIA.api.responseFilter(req);
	}
}