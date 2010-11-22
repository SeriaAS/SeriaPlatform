
SERIA.DatepickerWidgetDataStorage = {
	textBoxId: false
};

function calendarWillCallMe(textBoxId, dateval)
{
	document.getElementById(textBoxId).value = dateval;
}

function openCalendar(textBoxId)
{
	var dateVal = document.getElementById(textBoxId).value;
	SERIA.DatepickerWidgetDataStorage.textBoxId = textBoxId;
	datesel = window.open(SERIA_VARS.HTTP_ROOT + '/seria/widgets/datepicker/datesel.php?value=' + dateVal + '&id=' + textBoxId, "_blank", "menubar=1,width=280,height=310");
	datesel.textBoxId = textBoxId;
}

var ico_calendar = SERIA_VARS.HTTP_ROOT + '/seria/widgets/datepicker/Icons/Calendar.png'; 

function getDateObject(callback, sec, imin, hour, day, month, year)
{
	if (!sec)
		sec = '';
	if (!imin)
		imin = '';
	if (!hour)
		hour = '';
	if (!month)
		month = '';
	if (!day)
		day = '';
	if (!year)
		year = '';
	/*
	 * TODO - should use the SERIA_ROOT variable
	 */
	SERIA.Lib.AJSON('/seria/widgets/datepicker/datesel.php', {'datetime': 'yes', 'year': year, 'month': month, 'day': day, 'hour': hour, 'min': imin, 'sec': sec, 'ie6cacheblock': 'rval'+Math.random()}, callback);
}

function datepicker(element)
{
	$(element).after("<button type='button' onclick='openCalendar(\"" + element.id + "\")'><img src='"+ico_calendar+"'></button>");
}

function getDateObject(callback, sec, imin, hour, day, month, year)
{
	if (!sec)
		sec = '';
	if (!imin)
		imin = '';
	if (!hour)
		hour = '';
	if (!month)
		month = '';
	if (!day)
		day = '';
	if (!year)
		year = '';
	/*
	 * FIXME - should use the SERIA_ROOT variable
	 */
	SERIA.Lib.AJSON('/seria/widgets/datepicker/datesel.php', {'datetime': 'yes', 'year': year, 'month': month, 'day': day, 'hour': hour, 'min': imin, 'sec': sec}, callback);
}

function datepicker(element)
{
	$(element).after("<button type='button' onclick='openCalendar(\"" + element.id + "\")'><img src='"+ico_calendar+"'></button>");
}

$(function(){
	$('input.datepicker').each(function(){
		if(!this.id)
			this.id = "autoid" + Math.random();
		datepicker(this);
	});
})
