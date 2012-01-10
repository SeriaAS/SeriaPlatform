
SERIA.User = function (user_id)
{
	this.user_id = user_id;
	this.data = SERIA.Lib.SJSON(
		SERIA_VARS.HTTP_ROOT + '/seria/components/SERIA_JsonUserData/api/getUser.php',
		{
			user_id: user_id
		}
	).data;
}

SERIA.getCurrentUserId = function ()
{
	if (typeof(SERIA.cache) == 'undefined')
		SERIA.cache = {};
	if (typeof(SERIA.cache.currentUserId) == 'undefined') {
		SERIA.cache.currentUserId = SERIA.Lib.SJSON(
			SERIA_VARS.HTTP_ROOT + '/seria/components/SERIA_JsonUserData/api/getCurrentUserId.php',
			{ }
		);
	}
	return SERIA.cache.currentUserId;
}

SERIA.getUser = function (user_id)
{
	if (!SERIA.cache.users)
		SERIA.cache.users = [];
	if (!(user_id in SERIA.cache.users))
		SERIA.cache.users[user_id] = new SERIA.User(user_id);
	return SERIA.cache.users[user_id];
}

SERIA.User.prototype.getPropertyList = function(namespace)
{
	return new SERIA.UserPropertyList(this, namespace);
}

SERIA.User.getPropertyList = function(namespace)
{
	return SERIA.getUser(SERIA.getCurrentUserId()).getPropertyList(namespace);
}

SERIA.User.prototype.get = function (name)
{
	if (!('user' in SERIA.User)) {
		SERIA.User.user = new SERIA.User(SERIA.getCurrentUserId());
	}
	switch (name) {
		case 'id':
			return this.user_id;
		default:
			if (name in this.data)
				return this.data[name];
			else
				throw('Invalid key for user object (get): ' + name);
	}
}

SERIA.User.get = function (name)
{
	return SERIA.getUser(SERIA.getCurrentUserId()).get(name);
}
