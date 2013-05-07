function redirect(url) {
	window.self.location.href = url;
}

function confirmRequest(url, text) {
	if(confirm(text)) {
		redirect(url);
	}
}

function popUp(url, width, height){
	var features = 'status=yes,scrollbars=yes,resizable=yes,width='+width+',height='+height;
	var win = window.open(url, 'Window', features);
	
	win.focus();
}