/**
*	Deprectated. This should be implemented as a jQuery plugin.
*/
function resizeToObject(parent, object)
{
	var iw = $(parent).innerWidth();
	var ih = $(parent).innerHeight();
	var ppos = $(parent).position();
	var pos = $(object).position();
	var tow = $(object).outerWidth(true);
	var toh = $(object).outerHeight(true);
	var dw = iw-tow-pos.left+ppos.left;
	var dh = ih-toh-pos.top+ppos.top;
	var cw = $(object).width()+dw;
	var ch = $(object).height()+dh;

	$(object).width(cw);
	$(object).height(ch);
}

function resizeToObjectWidth(parent, object)
{
	var iw = $(parent).innerWidth();
	var ih = $(parent).innerHeight();
	var ppos = $(parent).position();
	var pos = $(object).position();
	var tow = $(object).outerWidth(true);
	var toh = $(object).outerHeight(true);
	var dw = iw-tow-pos.left+ppos.left;
	var dh = ih-toh-pos.top+ppos.top;
	var cw = $(object).width()+dw;
	var ch = $(object).height()+dh;

	$(object).width(cw);
}

function resizeToObjectHeight(parent, object)
{
	var iw = $(parent).innerWidth();
	var ih = $(parent).innerHeight();
	var ppos = $(parent).position();
	var pos = $(object).position();
	var tow = $(object).outerWidth(true);
	var toh = $(object).outerHeight(true);
	var dw = iw-tow-pos.left+ppos.left;
	var dh = ih-toh-pos.top+ppos.top;
	var cw = $(object).width()+dw;
	var ch = $(object).height()+dh;

	$(object).height(ch);
}

function resizeToParent(object)
{
	resizeToObject($(object).parent(), object);
}

function resizeToParentWidth(object)
{
	resizeToObjectWidth($(object).parent(), object);
}

function resizeToParentHeight(object)
{
	resizeToObjectHeight($(object).parent(), object);
}
