/**
*	This file must be included AFTER jQuery.js, since it relies on functions from jQuery
*/
if(typeof($)=='undefined') alert("jQuery not included. Must be included before including private.js");

function includeWidget(widgetName)
{
	var url = SERIA_VARS.HTTP_ROOT + "/seria/widgets/" + widgetName + "/" + widgetName + ".js";
	document.write("<script type='text/javascript' src='" + url + "'></scrip" + "t>");
}

includeWidget("datepicker");
includeWidget("fileselect");
includeWidget("tinymce");

//jQuery(function(){jQuery('.SERIA_WIDGET').each(function(){ alert('FANT!'); });});
jQuery(function(){
	jQuery('.SERIA_WIDGET').each(function(){
		if(jQuery(this).hasClass('FILESELECTOR'))
		{
			if(jQuery(this).hasClass('MULTIPLE'))
			{
				alert('MULTIPLE FILESELECTOR NOT SUPPORTED YET');
			}
			else
			{
				// Must have ID
				//if(!this.id) this.id = 'autogenerated_id_'+this.name+'_'+Math.random();
				// Insert flash with correct flashvars (elementId=this.id&fileId=this.value)

				var flashVars = {
					fieldId : (this.id ? this.id : this.name+'_id'),
					fileId : this.value,
					thumbnail : '600x300',
					sessionName : SERIA_VARS.SESSION_NAME,
					sessionId : SERIA_VARS.SESSION_ID
				}
	/*
				if(jQuery(this).hasClass('IMAGES'))
					flashVars.filetypes = new Array({ 'jpeg','jpg','gif','png' });
				else if(jQuery(this).hasClass('VIDEOS'))
					flashVars.filetypes = { 'flv','wmv','mpg','mov','avi','mpeg','m4v' };
	*/
				jQuery(this).after('<div style="height:70px;width:500px;" id="container' + (this.id ? this.id : this.name+'_id') + '">..</div>');
				flashembed('container' + (this.id ? this.id : this.name+'_id'), SERIA_VARS.HTTP_ROOT + '/seria/platform/bin/SERIA_FileUploader.swf', flashVars);
			}
		}
	});

});
