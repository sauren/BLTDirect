if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.MenuItem = new Class({
	_className: 'MenuItem',
	_classOwner: 'bltdirect.ui.MenuItem',
	id: 0,
	parent:  null,
	instance:  null,
	menuClass:  null,
	icon:  '',
	label:  '',
	url:  '',
	container:  '',
	isOpen:  false,
	items:  null,
	hasChildren:  false,
	interval:  null,
	isDisabled:  false,
	
	init: function() {
		this.id = arguments[0];
		this.parent = arguments[1];
		this.label = arguments[2];
		this.url = arguments[3];
		this.icon = arguments[4];
		this.instance = arguments[6];
		this.menuClass = this.getClass(arguments[5]);

		this.container = this.instance + '_' + this.id + '_Container';
		this.items = new Array();
	},
		
	getClass: function(classId){
		var menu = eval(this.instance);
		var classObject = menu.getClass(classId);
		return classObject;
	},
	
	add: function(id, parent, label, url, icon, classId, instance){
		if(parent == this.id){
			this.items[this.items.length] = new bltdirect.ui.MenuItem(id, this, label, url, icon, classId, instance);
			return true;
		} else {
			for(var i=0; i < this.items.length; i++){
				var node = this.items[i];
				if(node.add(id, parent, label, url, icon, classId, instance)){
					node.hasChildren = true;
					return true;
				}
			}
			return false
		}
		return false;
	},
	
	build: function(){
		if(this.hasChildren){
			var div = null;
			
			if(!document.getElementById(this.container)){
				div = document.createElement('div');
				div.id = this.container;
				div.className = this.menuClass.name;
				
				document.body.appendChild(div);
			} else {
				div = document.getElementById(this.container);
			}
	
			var html = '<ul>';
			for(var i=0; i < this.items.length; i++){
				html += this.items[i].getHtml((i == (this.items.length - 1)) ? true : false);
				this.items[i].build();
			}
			html += '</ul>';
			div.innerHTML = html;
		}
	},
	
	closeChildrenExcept: function(id){
		for(var i=0; i < this.items.length; i++){
			if(this.items[i].id != id) this.items[i].close();
		}
	},
	
	open: function(id){
		if(id == this.id){
			this.rollOver();
			this.display(id);
			return true;
		} else {
			for(var i = 0; i < this.items.length; i++){
				if(this.items[i].open(id)) return true;
			}
			return false;
		}
	},
	
	rollOver: function(){
		var object = document.getElementById(this.id);
		object.className = 'over';
		if(this.parent.rollOver) this.parent.rollOver();
		object = null;
	},
	
	display: function(){
		// close all submenus of the parent.
		this.parent.closeChildrenExcept(this.id);
	
		if(this.hasChildren){
			var object = document.getElementById(this.id);
			
			if(object) {
				//object.className = 'open';
				var position = this.getPosition(object);
		
				var container = document.getElementById(this.container);
				
				if(container) {
					container.style.display = 'block';
					container.style.position = 'absolute';
					
					if(this.menuClass.direction == 'down'){
						container.style.left = position.x + 'px';
						container.style.top = position.y + object.offsetHeight + 'px';
					} else if(this.menuClass.direction == 'left'){
						container.style.left = position.x + object.offsetWidth + 'px';
						container.style.top = position.y + 'px';
					} else if(this.menuClass.direction == 'context'){
						container.style.left = Mouse._x -3 + 'px';
						container.style.top = Mouse._y -3 + 'px';
					}
					
					container = null;
				}
		
				object = null;
			}
		}
	},
	
	getPosition: function(element){
		var top = 0;
		var left = 0;
		var e = element;
	
		while(e.offsetParent){
			top += e.offsetTop;
			left += e.offsetLeft;
			e = e.offsetParent;
		}
		top += e.offsetTop;
		left += e.offsetLeft;
		return {x:left, y:top};
	},
	
	close: function(){
		var object = document.getElementById(this.id);
		
		if(object) {
			object.className = 'out';
			object = null;
		}
	
		if(this.hasChildren){
			var container = document.getElementById(this.container);
			
			if(container) {
				container.style.display = 'none';
				container = null;
			}
		}
	
		// close its children
		if(this.hasChildren){
			for(var i=0; i < this.items.length; i++){
				this.items[i].close();
			}
		}
	},
	
	getHtml: function(isLast){
		var html = '<li ' + ((isLast) ? 'class="last"' : '') + '>';
		html += '<a href="'+this.url+'" onmouseover="'+this.instance+'.onRollOver(\''+this.id+'\');" onmouseout="'+this.instance+'.onRollOut(\''+this.id+'\');" id="'+this.id+'">';
		html += '<span class="capLeft"></span>';
		html += '<span class="label">'+this.label+'</span>';
		if(this.hasChildren) html += '<span class="subMenuIcon"></span>';
		html += '<span class="capRight"></span>';
		html += '</a>';
		html += '</li>';
		return html;
	}
});