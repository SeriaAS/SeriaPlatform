top.contextMenuElement = false;

function seriaContextMenuEscapeString(str)
{
	var divel = document.createElement('div');
	var textel = document.createTextNode(str);
	divel.appendChild(textel);
	var escaped = divel.innerHTML;
	escaped = escaped.replace(/\"/g, '&quot;');
	return escaped;
}

function seriaContextMenuFadeout(el, opacity)
{
	if(!el || !el.style)
	{
		return;
	}

        el.style.opacity = opacity / 100;
        el.style.MoxOpacity = opacity / 100;
        el.style.KhtmlOpacity = opacity / 100;
        el.style.filter = 'alpha(opacity=' + opacity + ')';

	if(opacity<=0)
	{
		if(!el.parentNode) alert('feilen');
		el.parentNode.removeChild(el);
		return;
	}
	else
	{
		opacity -= 25;
		setTimeout(function(){seriaContextMenuFadeout(el, opacity);}, 50);
	}
}

function seriaContextMenuFadein(el, opacity)
{
	if(!el || !el.style)
		return;

	if(opacity>100) opacity = 100;

        el.style.opacity = opacity / 100;
        el.style.MoxOpacity = opacity / 100;
        el.style.KhtmlOpacity = opacity / 100;
        el.style.filter = 'alpha(opacity=' + opacity + ')';

	if(opacity<100)
	{
		opacity += 33;
		setTimeout(function(){seriaContextMenuFadein(el, opacity);}, 50);
	}
}

function seriaContextMenuClose()
{
	self.document.onmousedown = top.contextMenuMouseDown;
	self.document.onkeydown = top.contextMenuKeyDown;

	if(top && top.contextMenuElement)
		seriaContextMenuFadeout(top.contextMenuElement, 100);

//	if(top.contextMenuElement)
//		top.contextMenuElement.parentNode.removeChild(top.contextMenuElement);


	if(top)
		top.contextMenuElement = false;
}

function seriaContextMenu(e)
{
	if(!e) e = window.event;
	if(e && e.ctrlKey)
		return;

	top.contextMenuMouseDown = self.document.onmousedown;
	top.contextMenuKeyDown = self.document.onkeydown;
	self.document.onmousedown = seriaContextMenuClose;
	self.document.onkeydown = seriaContextMenuClose;

	if(top.contextMenuElement) seriaContextMenuClose();

	if(!e) var e = window.event;

	if (e.ctrlKey) return true;

	if(e.srcElement)
		var element = e.srcElement;
	else
		var element = e.target;



	var mnu;
	var menus = new Array();
	window.status = 13;
	var regx = new RegExp("(<img(.*)\>)","gi");
	var stopInheritance = false;
	do
	{
		nodeName = element.nodeName.toLowerCase();
		/*
		 * Cancel inheritance for input and textarea elements (Allow copy,paste,etc (normal right click menu)).
		 */
		if ((nodeName == 'input' || nodeName == 'textarea') && !element.getAttribute('mnu'))
			stopInheritance = true;
		if(element && element.getAttribute && (mnu = element.getAttribute('mnu')))
		{
			/*
			 * Starting the mnu with ! means no inheritance from parents, escape it with starting with a | then !
			 */
			if (mnu == '!') {
				stopInheritance = true;
				continue;
			}
			if (mnu.substring(0, 1) == '!') {
				stopInheritance = true;
				mnu = mnu.substring(1);
			}
			if (mnu.substring(0, 1) == '|')
				mnu = mnu.substring(1);

			var menuPart = new Object();
			menuPart.string = mnu;
			menuPart.element = element;
			var menuPartItems = new Array();
			var mp1 = mnu.split("|");


			for(var i1 = 0; i1 < mp1.length; i1++)
			{
				var mp2 = mp1[i1].split(":");
				var menuPartItem = new Object();
				if(mp2[0]=="---")
				{
					menuPartItem.isSplit = true;
				}
				else
				{
					menuPartItem.disabled = mp2[0].charAt(0) == "-";
					if(menuPartItem.disabled)
						menuPartItem.text = mp2[0].substr(1,mp2[0].length-1);
					else
						menuPartItem.text = mp2[0];

					var imgTag = menuPartItem.text.match(regx);
					if(imgTag)
					{
						menuPartItem.text = imgTag + menuPartItem.text.replace(regx, "");
						menuPartItem.hasImage = true;
					}
					else
						menuPartItem.hasImage = false;

					if(mp2[1]=="javascript")
						menuPartItem.script = mp2[2];
					else {
						/*
						 * Autodetect WIPS/Platform style..
						 */
						if (mp2[1] == '/') /* This must be an URL (WIPS)? */
							menuPartItem.script = 'location.href=\\"'+mp2[1]+'\\"';
						else {
							/*
							 * Platform mode: Javascript is the default.
							 */
							var separatorPos = mp1[i1].indexOf(":");
							var javascriptPart = mp1[i1].substring(separatorPos+1);
							/*
							 * New feature: Autodetect http:// and https://
							 */
							if ((mp2[1] == 'http' || mp2[1] == 'https') && mp2[2][0] == '/' && mp2[2][1] == '/')
								menuPartItem.script = 'location.href=\\"'+javascriptPart+'\\"';
							else
								menuPartItem.script = javascriptPart;
						}
					}
				}
				menuPartItems[menuPartItems.length] = menuPartItem;
			}
			menuPart.items = menuPartItems;
			menus[menus.length] = menuPart;
		}
	} while((element) && (element = element.parentNode) && !stopInheritance);

	if (menus.length > 0) {
		e.cancelBubble = true;
		e.returnValue = false;
		if(e.stopPropagation)
		{
			e.stopPropagation();
			e.preventDefault();
		}
	} else
		return;

	var scrollLeft = 0;
	var scrollTop = 0;
	if (document.body && typeof(document.body.scrollLeft) != 'undefined') {
		scrollLeft = document.body.scrollLeft;
		scrollTop = document.body.scrollTop;
	}
	if (document.documentElement && typeof(document.documentElement.scrollLeft) != 'undefined') {
		if (scrollLeft < document.documentElement.scrollLeft)
			scrollLeft = document.documentElement.scrollLeft;
		if (scrollTop < document.documentElement.scrollTop)
			scrollTop = document.documentElement.scrollTop;
	}
	if (typeof(window.pageXOffset) != 'undefined') {
		if (window.pageXOffset > scrollLeft)
			scrollLeft = window.pageXOffset;
		if (window.pageYOffset > scrollTop)
			scrollTop = window.pageYOffset;
	}
	if (typeof(self.pageXOffset) != 'undefined') {
		if (self.pageXOffset > scrollLeft)
			scrollLeft = self.pageXOffset;
		if (self.pageYOffset > scrollTop)
			scrollTop = self.pageYOffset;
	}
	if(e.clientX)
	{
		var X = e.clientX;
		var Y = e.clientY;
		X += scrollLeft;
		Y += scrollTop;
	}
	else
	{
		var X = e.pageX;
		var Y = e.pageY;
	}

	var SW; // screen width
	var SH; // screen height

	if(self.innerWidth)
	{
		SW = self.innerWidth;
		SH = self.innerHeight;
	}
	else if(document.documentElement && document.documentElement.clientWidth)
	{
		SW = document.documentElement.clientWidth;
		SH = document.documentElement.clientHeight;
	}
	else
	{
		SW = document.body.clientWidth;
		SH = document.body.clientHeight;
	}

	var menuPart = new Object();
	menuPart.string = "--custom--";
	menuPartItem = new Object();
	menuPartItem.disabled = true;
	menuPartItem.text = "2007 &copy; Seria.no";
	menuPartItem.script = 'void(0)';
	menuPart.items = new Array(menuPartItem);
	menus[menus.length] = menuPart;


	var i, ii, html = '', isFirst = true;
	for(i = 0; i < menus.length; i++)
	{
		if(!isFirst)
			html = html + "<div style='margin: 2px; padding: 0px; overflow: hidden; height: 1px; border-top: 1px dashed #cccccc;'></div>";

		for(ii = 0; ii < menus[i].items.length; ii++)
		{
			var item = menus[i].items[ii];
			var imgClassText = (item.hasImage?"Image":"");
			if(item.isSplit)
				html = html + "<div class='contextMenuSplit'></div>";
			else if(item.disabled)
				html = html + "<a href='#' class='contextMenuDisabled"+imgClassText+"'>" + item.text + "</a>";
			else
				html = html + "<a href='#' onclick=\"" + seriaContextMenuEscapeString(item.script) + "\" class='contextMenu"+imgClassText+"'>" + item.text + "</a>";
		}
		isFirst = false;			
	}

	var width = 180;
	var height = 200;

	if(SW < X + width)
		X = SW - width;


	var menu = document.createElement('DIV');


	menu.oncontextmenu = function(){return false;}
	menu.innerHTML = html;

	setTimeout(function() {
		if(SH + scrollTop < Y + menu.offsetHeight)
			Y = SH + scrollTop - menu.offsetHeight;
		menu.style.top = Y + "px";
	}, 0);



	menu.className = 'contextMenu';
	menu.style.position = 'absolute';
	menu.style.left = X + 'px';
	menu.style.top = Y + 'px';
	menu.style.width = width + 'px';
	menu.style.zIndex = 32000;

	var opacity = 0;

        menu.style.opacity = opacity / 100;
        menu.style.MoxOpacity = opacity / 100;
        menu.style.KhtmlOpacity = opacity / 100;
        menu.style.filter = 'alpha(opacity=' + opacity + ')';

	if(window.onfocus)
		window.onfocus = seriaContextMenuClose;
	else if(self.onfocus)
		self.onfocus = seriaContextMenuClose;

	top.contextMenuElement = menu;

	document.body.appendChild(top.contextMenuElement);

	seriaContextMenuFadein(top.contextMenuElement, opacity);
}

self.document.oncontextmenu = seriaContextMenu;
