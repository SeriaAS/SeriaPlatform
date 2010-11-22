
SERIA.FileAttachments = {
	'expand': function (id, idkey) {
		var obj = document.getElementById('file_attachments_'+id+'_'+idkey);
		obj.style.display = 'block';
	},
	'close': function (id, idkey) {
		var obj = document.getElementById('file_attachments_'+id+'_'+idkey);
		obj.style.display = 'none';
	},
	'toggleVisible': function (id, idkey) {
		var obj = document.getElementById('file_attachments_'+id+'_'+idkey);
		if (obj.style.display == 'none')
			SERIA.FileAttachments.expand(id, idkey);
		else
			SERIA.FileAttachments.close(id, idkey);
	}
}