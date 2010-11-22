
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
	return SERIA.getUser(SERIA_VARS.USER_ID).getPropertyList(namespace);
}

SERIA.User.prototype.get = function (name)
{
	if (!('user' in SERIA.User))
		SERIA.User.user = new SERIA.User(SERIA_VARS.USER_ID);
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
	return SERIA.getUser(SERIA_VARS.USER_ID).get(name);
}
