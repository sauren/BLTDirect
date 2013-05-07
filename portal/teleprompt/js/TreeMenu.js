/*
	Tree Menu
*/
function TreeMenu(instance){
	this.instance = instance;
	this.nodes = new Array();
	this.classes = new Array();
	this.url = null;
	this.httpRequest = new HttpRequest();
	this.httpRequest.setCallee(this);
	this.httpRequest.setTimeout(0);
	this.requests = new Array();
}
TreeMenu.prototype.openNode = function(id){
	var found = false;
	for(var i=0; i < this.nodes.length; i++){
		if(this.nodes[i].openNode(id)) return;
	}
}
TreeMenu.prototype.isChild = function(id){
	for(var i=0; i < this.nodes.length; i++){
		if(this.nodes[i].id == id) return true;
	}
	return false;
}
TreeMenu.prototype.addNode = function(id, parent, label, className, hasKids, url, target){
	if(parent == null){
		if(!this.isChild(id)){
		for(var i=0; i < this.nodes.length; i++){
			this.nodes[i].isLast = false;
		}
		this.nodes[this.nodes.length] = new TreeMenuNode(id, parent, label, this, 0, className, hasKids, url, target);
		}
	} else {
		this.addChildNode(id, parent, label, 1, className, hasKids, url, target);
	}
}
TreeMenu.prototype.addClass = function(id, icon, folder, openfolder){
	this.classes[this.classes.length] = new TreeMenuClass(id, icon, folder, openfolder);
}
TreeMenu.prototype.getClass = function(id){
	for(var i=0; i < this.classes.length; i++){
		if(this.classes[i].id == id) return this.classes[i];
	}
}
TreeMenu.prototype.addChildNode = function(id, parent, label, level, className, hasKids, url, target){
	for(var i=0; i < this.nodes.length; i++){
		var node = this.nodes[i];
		if(node.addNode(id, parent, label, level, className, hasKids, url, target)){
			node.hasChildren = true;
			return true;
		}
	}
	return false;
}
TreeMenu.prototype.build = function(container){
	var element = document.getElementById(container);
	var html = '<div class="treeMenu" id="'+this.instance+'_treeMenu">';
	html += this.getNodeHtml();
	html += '</div>';

	// output the html
	element.innerHTML = html;
	element = null;
}
TreeMenu.prototype.getNodeHtml = function(){
	var html = '';
	for(var i=0; i < this.nodes.length; i++){
		html += this.nodes[i].getHtml();
	}
	return html;
}
TreeMenu.prototype.display = function(id){
	for(var i=0; i < this.nodes.length; i++){
		this.nodes[i].display(id);
	}
}
TreeMenu.prototype.getJoin = function(id){
	var html = 'none';
	return html;
}
TreeMenu.prototype.getLoading = function(){
	var html = this.loading;
	return html;
}
TreeMenu.prototype.onHttpRequestError = function(response, code, status){
	alert('httpRequest Error: (Response = "'+response+'" Code = "'+code+'", Status = "'+status+'")');
}
TreeMenu.prototype.onHttpRequestResponse = function(response, code, status){
	eval(response);
}
TreeMenu.prototype.load = function(node){
	var obj = new Object();
	obj.node = node;
	obj.id = node.id;
	obj.status = 'loading';
	var num = this.requests.length;
	this.requests[num] = obj;
	this.httpRequest.get(this.url, 'instance='+this.instance+'&id='+node.id);
}
TreeMenu.prototype.loaded = function (id){
	// go through request stack and remove old requests
	var stackId = null;
	for(var i=0; i < this.requests.length; i++){
		if(this.requests[i].id == id){
			stackId = i;
			this.requests[i].node.loadedChildren();
		}
	}
	if(stackId != null){
	this.requests.splice(stackId, 1);
}
}
TreeMenu.prototype.getNode = function(id){
	for(var i=0; i < this.nodes.length; i++){
		var findNode = this.nodes[i].getNode(id);
		if(findNode !=  false) return findNode;
	}
	return false;
}
/*
	Tree Menu Node
*/
function TreeMenuNode(id, parent, label, instance, level, className, hasKids, url, target){
	this.id = id;
	this.parent = parent;
	this.className = className;
	this.label = label;
	this.instance = instance;
	this.hasChildren = hasKids;
	this.isLast = true;
	this.nodes = new Array();
	this.level = level;
	this.isOpen = false;
	this.targetHeight = 0;
	this.interval;
	this.isLoaded = false;
	this.status = null;
	this.url = url;
	this.target = target;
}
TreeMenuNode.prototype.isChild = function(id){
	for(var i=0; i < this.nodes.length; i++){
		if(this.nodes[i].id == id) return true;
	}
	return false;
}
TreeMenuNode.prototype.openNode = function(id){
	if(id == this.id){
		if(!this.isOpen) this.display(id);
		return true;
	} else {
		for(var i = 0; i < this.nodes.length; i++){
			if(this.nodes[i].openNode(id)) return true;
		}
		return false;
	}
}
TreeMenuNode.prototype.getHtml = function(){
	var html = '<div class="treeNode nodeLevel'+this.level+'" id="'+this.instance.instance+'_node_'+this.id+'">';

	if(this.hasChildren && this.nodes.length > 0){
		this.isOpen = true;
	}

	var sign = (this.isOpen)?'minus':'plus';
	var bottom = (this.isLast)?'Bottom':'';

	if(this.parent != null){
		html += this.parent.getJoin();
	}

	// get join information
	if(this.hasChildren){
		html +='<a href="javascript:'+this.instance.instance+'.display(\''+this.id+'\');" class="'+sign+bottom+'" id="'+this.instance.instance+'node_'+this.id+'_expander"><span></span></a>';
	} else {
		html += '<span class="join'+bottom+'"></span>';
	}

	// add Icon
	var classObj = this.instance.getClass(this.className);
	var icon = classObj.getIcon(this.hasChildren, this.isOpen);
	html += '<img src="'+icon+'" alt="" name="'+this.instance.instance+'_node_'+this.id+'_image" id="'+this.instance.instance+'_node_'+this.id+'_image" />';

	// add link
	var target = '';
	if(this.target != null) target = ' target="'+this.target+'"';
	if(this.hasChildren){
		var onclick = '';
		var url = this.url;
		if(this.url != null){
			onclick = ' onclick="'+this.instance.instance+'.openNode(\''+this.id+'\');"';
		} else if(this.url == null && this.hasChildren){
			url = 'javascript:'+this.instance.instance+'.openNode(\''+this.id+'\');';
		}
		html += '<a href="'+url+'" class="treeNodeLink" '+target+'>'+this.label+'</a>';
	} else if (!this.hasChildren && this.url == null){
		html += '<span class="treeNodeLink">' + this.label + '</a>';
	} else if (!this.hasChildren && this.url != null){
		html += '<a href="'+this.url+'" class="treeNodeLink" '+target+'>'+this.label+'</a>';
	}
	html += '</div>';

	if(this.hasChildren){
		var block = 'none';
		if(this.nodes.length > 0 ) {
			this.isLoaded = true;
			block = 'block';
		}

		html += '<div class="treeNodeContainer containerLevel'+this.level+'" style="display:'+block+';" id="'+this.instance.instance+'node_'+this.id+'_children">';

		if(this.instance.url != null && !this.isLoaded){
			html += this.instance.getLoading();
		} else {
			for(var i=0; i < this.nodes.length; i++){
				html += this.nodes[i].getHtml();
			}
		}
		html += '</div>';
	}

	return html;
}
TreeMenuNode.prototype.loadedChildren = function (){
	this.isLoaded = true;

	var html = '';
	for(var i=0; i < this.nodes.length; i++){
		html += this.nodes[i].getHtml();
	}
	// get object
	var id = this.instance.instance+'node_'+this.id+'_children';
	var content = document.getElementById(id);
	if(content) {
	content.style.height = content.offsetHeight + 'px';
	content.innerHTML = html;
	}
	content = null;
	this.isOpen = false;
	this.display(this.id);
}
TreeMenuNode.prototype.switchImage = function (){
	var imgName = this.instance.instance+'_node_'+this.id+'_image';
	var classObj = this.instance.getClass(this.className);
	var imgSrc = classObj.getIcon(this.hasChildren, this.isOpen);
	if (document.images && imgSrc != 'none'){
	  document.images[imgName].src = imgSrc;
	}
}
TreeMenuNode.prototype.getJoin = function(id){
	var html = '';
	if(this.parent != null) html += this.parent.getJoin();
	if(this.isLast){
		html += '<span class="blankLevel'+this.level+'"></span>'
	} else {
		html += '<span class="line lineLevel'+this.level+'"></span>';
	}
	return html;
}
TreeMenuNode.prototype.animate = function(){
	var children = document.getElementById(this.instance.instance+'node_'+this.id+'_children');
	var isDone = false;
	var difference = this.targetHeight - children.offsetHeight;
	var jumpBy = difference/3;
	var positionJumpTo = null;

	if(difference != 0){
		if((jumpBy != 0)){
			if(jumpBy < 1 && jumpBy > 0){
				jumpBy = 1;
			} else if (jumpBy < 0 && jumpBy > -1){
				jumpBy = -1;
			}
			positionJumpTo = jumpBy+children.offsetHeight;
			children.style.height = positionJumpTo + 'px';
		}
	} else if (difference == 0){
		isDone = true;
	}


	if(isDone) {
		clearInterval(this.interval);
		if(children.offsetHeight == 0){
			children.style.display = 'none';
		}
		children.style.height = 'auto';
		this.onAnimationComplete();
	}
	children = null;
}
TreeMenuNode.prototype.onAnimationComplete = function(){
	if(!this.isLoaded && this.instance.url != null){
		this.instance.load(this);
	}
	/*else if(this.status == 'reloaded'){
		var html = '';
		for(var i=0; i < this.nodes.length; i++){
			html += this.nodes[i].getHtml();
		}
		// get object
		var id = this.instance.instance+'node_'+this.id+'_children';
		var content = document.getElementById(id);
		content.innerHTML = html;
		this.status = null;
		this.display(this.id);
	}*/
}
TreeMenuNode.prototype.display = function(id){
	if(id == this.id){
		var id = this.instance.instance+'node_'+this.id;

		var object = document.getElementById(id);
		var children = document.getElementById(id+'_children');
		var expander = document.getElementById(id+'_expander');
		var bottom = (this.isLast)?'Bottom':'';

		if(!this.isOpen){
			children.style.visibility = 'hidden';
			children.style.display = 'block';
			children.style.height = 'auto';
			this.targetHeight = children.offsetHeight;
			children.style.height = '0px';
			children.style.visibility = 'visible';
			expander.className = 'minus'+bottom;
			this.isOpen = true;
			this.switchImage();
			var nodeRef = this;
			clearInterval(this.interval);
			this.interval = setInterval(function(){nodeRef.animate();}, 20);
		} else {
			expander.className = 'plus'+bottom;
			this.isOpen = false;
			this.switchImage();
			var nodeRef = this;
			clearInterval(this.interval);
			this.targetHeight = 0;
			this.interval = setInterval(function(){nodeRef.animate();}, 20);
		}

		// tidy up
		object = null;
		children = null;
		expander = null;
		return true;
	} else {
		for(var i=0; i < this.nodes.length; i++){
			if(this.nodes[i].display(id)){
				return true;
			}
		}
		return false;
	}
}
TreeMenuNode.prototype.getHeight = function(){
	var element = document.getElementById(this.instance.instance+'_node_'+this.id);
	var h = element.offsetHeight
	element = null;
	return h;
}
TreeMenuNode.prototype.addNode = function(id, parent, label, level, className, hasKids, url, target){
	if(parent == this.id){
		if(!this.isChild(id)){
		for(var i=0; i < this.nodes.length; i++){
			this.nodes[i].isLast = false;
		}
		this.nodes[this.nodes.length] = new TreeMenuNode(id, this, label, this.instance, level, className, hasKids, url, target);
		}
		return true;
	} else {
		return this.addChildNode(id, parent, label, ++level, className, hasKids, url, target);
	}
}
TreeMenuNode.prototype.addChildNode = function(id, parent, label, level, className, hasKids, url, target){
	for(var i=0; i < this.nodes.length; i++){
		var node = this.nodes[i];
		if(node.addNode(id, parent, label, level, className, hasKids, url, target)){
			node.hasChildren = true;
			return true;
		}
	}
	return false
}

TreeMenuNode.prototype.getNode = function(id){
	if(id == this.id){
		return this;
	}
	if(this.hasChildren){
		for(var i=0; i < this.nodes.length; i++){
			return this.nodes[i].getNode(id);
		}
	} else {
		return false;
	}
}

/*
	Tree Menu Class
*/
function TreeMenuClass(id, icon, folder, openfolder){
	this.id = id;
	this.icon = icon;
	this.folder = folder;
	this.folderOpen = openfolder;
}
TreeMenuClass.prototype.getIcon = function(hasKids, isOpen){
	if(hasKids){
		if(this.folder != null && this.folderOpen != null){
			if(isOpen && this.folderOpen != null){
				return this.folderOpen;
			} else if (isOpen){
				return this.folder;
			} else {
				return this.folder;
			}
		} else {
			return this.icon;
		}
	} else {
		return this.icon;
	}
}