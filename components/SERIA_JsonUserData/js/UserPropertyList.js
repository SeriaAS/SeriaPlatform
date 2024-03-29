/*
 * This file depends on JSON.stringify
 */
var JSON;
if (!JSON) {
    JSON = {};
}
if (typeof JSON.stringify !== 'function') {
	(function () {
		var head = document.getElementsByTagName('head')[0];
		var myscript = document.createElement('script');

		myscript.type = 'text/javascript';
		myscript.src = SERIA_VARS.HTTP_ROOT + '/seria/components/SERIA_JsonUserData/js/lib/json2.js';
		head.appendChild(myscript);
	})();
}

SERIA.UserPropertyList = function (user, namespace)
{
	this.user = user;
	this.namespace = namespace;
}

SERIA.UserPropertyList.prototype._load = function ()
{
	try {
		var data = SERIA.Lib.SJSON(
			SERIA_VARS.HTTP_ROOT + '/seria/components/SERIA_JsonUserData/api/getProperties.php',
			{
				user_id: this.user.get('id'),
				namespace: this.namespace
			}
		);
		if (!data) {
			alert('Response data is empty from JSON UserPropertyList._load');
			return;
		}
		this.values = data.properties;
	} catch (e) {
		alert('Invalid response returned to UserPropertyList._load');
	}
}

SERIA.UserPropertyList.prototype.getAll = function ()
{
	if (!('values' in this))
		this._load();
	return this.values;
}

SERIA.UserPropertyList.prototype.get = function (name)
{
	if (!('values' in this))
		this._load();
	if (name in this.values)
		return this.values[name];
	else
		return null;
}

SERIA.UserPropertyList.prototype.set = function (name, value)
{
	if (!('values' in this))
		this._load();
	this.values[name] = value;
	var postData = function (url, params) {
		var r = jQuery.ajax({
			"type" : 'POST',
			"async" : false,
			"data" : params,
			"dataType" : "json",
			"timeout" : 3,
			"url" : url
		}).responseText;

		try {
			eval("r = " + r + ";");
		} catch (e) {
			return false;
		}

		if(r.error)
			throw r.error;

		return r;
	}
	postData(
		SERIA_VARS.HTTP_ROOT + '/seria/components/SERIA_JsonUserData/api/setProperty.php',
		{
			user_id: this.user.get('id'),
			namespace: this.namespace,
			name: name,
			value: value
		}
	);
}

SERIA.UserPropertyList.prototype.setBatch = function (batch)
{
	var data = JSON.stringify(batch);

	if (!('values' in this))
		this._load();
	for (name in batch) {
		var value = batch[name];
		this.values[name] = value;
	}
	jQuery.ajax({
		type: 'post',
		url: SERIA_VARS.HTTP_ROOT + '/seria/components/SERIA_JsonUserData/api/setProperty.php',
		data: {
			'user_id': this.user.get('id'),
			'namespace': this.namespace,
			'batch': data
		},
		async: false,
		success: function (data, textStatus, jqXHR) {
		}
	});
}

SERIA.UserPropertyList.prototype.unset = function (name)
{
	if (!('values' in this))
		this._load();
	if (name in this.values) {
		values = [];
		for (key in this.values) {
			if (key != name)
				values[key] = this.values[key];
		}
		this.values = values;
		SERIA.Lib.SJSON(
			SERIA_VARS.HTTP_ROOT + '/seria/components/SERIA_JsonUserData/api/setProperty.php',
			{
				user_id: this.user.get('id'),
				namespace: this.namespace,
				name: name
			}
		);
	}
}
