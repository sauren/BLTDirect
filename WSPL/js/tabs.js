var contentItems = new Array();

function addContent(content) {
	contentItems.push(content);
}

function setContent(content) {
	var contentElement = null;
	
	for(var i=0; i<contentItems.length; i++) {
		contentElement = document.getElementById('tab-content-item-' + contentItems[i]);
		
		if(contentElement) {
			contentElement.style.display = 'none';
		}
	}
	
	contentElement = document.getElementById('tab-content-item-' + content);
	
	if(contentElement) {
		contentElement.style.display = '';
	}
	
	for(var i=0; i<contentItems.length; i++) {
		contentElement = document.getElementById('tab-bar-item-' + contentItems[i]);
		
		if(contentElement) {
			contentElement.className = 'tab-bar-item';
		}
	}

	contentElement = document.getElementById('tab-bar-item-' + content);
	
	if(contentElement) {
		contentElement.className = 'tab-bar-item tab-bar-item-selected';
	}
}