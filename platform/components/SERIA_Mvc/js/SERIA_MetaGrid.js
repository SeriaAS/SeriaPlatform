
SERIA.MetaGrid = function (tableObject)
{
	this.tableObject = tableObject;
}

SERIA.MetaGrid.prototype.getSerializedKey = function()
{
	if (typeof(SERIA_MetaGrid_serialized) != 'undefined' &&
	    typeof(SERIA_MetaGrid_serialized[this.tableObject.id]) != 'undefined' &&
	    SERIA_MetaGrid_serialized[this.tableObject.id])
		return SERIA_MetaGrid_serialized[this.tableObject.id];
	else
		return false;
}

SERIA.MetaGrid.prototype.setSerializedKey = function (key)
{
	if (typeof(SERIA_MetaGrid_serialized) != 'undefined')
		SERIA_MetaGrid_serialized[this.tableObject.id] = key;
	else
		alert('Can\'t set the serialized-key because the storage is not defined.');
}

SERIA.MetaGrid.prototype.sortBy = function(key)
{
	result = SERIA.Lib.SJSON(
		SERIA_VARS.HTTP_ROOT + '/seria/platform/components/SERIA_Mvc/api/SERIA_MetaGrid.php',
		{
			op: 'tbody-content',
			metaGridKey: this.getSerializedKey(),
			sortBy: key
		}
	);
	if (result)
		result = eval(result);
	else {
		alert('No data received!');
		return;
	}
	if (result.errorMsg) {
		alert(result.errorMsg);
		return;
	}
	$(this.tableObject).find('tbody').first().html(result.data);
	var even = true;
	$(this.tableObject).find('tbody').first().children('tr').each(function () {
		if (even)
			$(this).addClass('even');
		else
			$(this).addClass('odd');
		even = !even;
	});
	if (result.serializeKey)
		this.setSerializedKey(result.serializeKey);
}

SERIA.MetaGrid.sortTableBy = function(tableObject, key)
{
	var grid = new SERIA.MetaGrid(tableObject);
	grid.sortBy(key);
}